<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Front\Catalog\ProductAction;
use App\Models\Front\Catalog\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 *
 */
class Special
{

    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected $user;

    protected $user_group;

    /**
     * @var Product|null
     */
    protected $product;

    /**
     * @var float|int|null
     */
    protected $product_option_price;

    /**
     * @var ProductAction|null
     */
    protected $action;

    /**
     * @var ProductAction|null
     */
    protected $user_group_action;

    /**
     * @var Collection|null
     */
    private $active_actions;


    /**
     * @param Product|null       $product
     * @param ProductAction|null $product_action
     */
    public function __construct(?Product $product = null, ?float $option_price = null, bool $publicPricing = false)
    {
        $this->user    = $publicPricing ? null : Auth::user();
        $this->product = $product;
        $this->product_option_price = $option_price ?: 0;

        if ($this->user && $this->user->details) {
            $this->user_group = $this->user->details->group ? $this->user->details->group->id : 0;
        }
    }


    /**
     * @return mixed
     */
    public function getUserGroupAction(): ?ProductAction
    {
        return $this->user_group_action;
    }


    /**
     * @return ProductAction|null
     */
    public function getAction(): ?ProductAction
    {
        return $this->action;
    }


    /**
     * @return ProductAction|null
     */
    public function resolveAction(): ?ProductAction
    {
        $this->active_actions = $this->eligibleActions();

        $locked_action = $this->bestActionFrom($this->active_actions->where('lock', 1));

        if ($locked_action) {
            $this->action = $locked_action;

            return $locked_action;
        }

        if ($this->user_group) {
            $this->user_group_action = $this->bestActionFrom(
                $this->active_actions->where('user_group_id', $this->user_group)
            );

            if ($this->user_group_action) {
                return $this->user_group_action;
            }
        }

        $this->action = $this->bestActionFrom($this->active_actions->whereNull('user_group_id'));

        return $this->action;
    }


    /**
     * @return bool
     */
    public function userHasGroupDiscount(): bool
    {
        if ( ! $this->user_group) {
            return false;
        }

        $actions = $this->eligibleActions()->where('user_group_id', $this->user_group);
        $this->user_group_action = $this->bestActionFrom($actions);

        return (bool) $this->user_group_action;
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return float|int
     */
    public function getDiscountPrice(ProductAction|null $product_action = null): float|int
    {
        $action = $this->resolveRealAction($product_action);
        $product_price = $this->product->price + $this->product_option_price;

        if ( ! $action) {
            return $product_price;
        }

        if ($this->isProductOnAction($action)) {
            return Helper::calculateDiscountPrice($product_price, $action->discount, $action->type);

        } else {
            return $product_price;
        }
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return bool
     */
    public function checkCoupon(ProductAction|null $product_action = null): bool
    {
        $action             = $this->resolveRealAction($product_action);
        $coupon_session_key = config('session.cart') . '_coupon';
        $coupon             = false;

        if ( ! $action || ($action && ! $action->coupon)) {
            $coupon = true;
        }

        if (isset($action->status) && $action->status) {
            if ((isset($action->coupon) && $action->coupon) && session()->has($coupon_session_key) && session($coupon_session_key) == $action->coupon) {
                $coupon = true;
            }
        }

        return $coupon;
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return bool
     */
    public function checkDates(ProductAction|null $product_action = null): bool
    {
        $action = $this->resolveRealAction($product_action);

        if ( ! $action) {
            return false;
        }

        $from = now()->subDay();
        $to   = now()->addDay();

        if ($action->date_start && $action->date_start != '0000-00-00 00:00:00') {
            $from = Carbon::make($action->date_start);
        }
        if ($action->date_end && $action->date_end != '0000-00-00 00:00:00') {
            $to = Carbon::make($action->date_end);
        }

        if ($from <= now() && now() <= $to) {
            return true;
        }

        return false;
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return bool
     */
    public function isProductOnAction(ProductAction|null $product_action = null): bool
    {
        $action = $this->resolveRealAction($product_action);

        if ( ! $action || ! $this->product || $action->group === 'total') {
            return false;
        }

        if ($this->isActionOnAllProducts($action)) {
            return true;
        }

        $ids = array_map('strval', json_decode($action->links, true) ?: []);

        if ($action->group === 'product') {
            return in_array((string) $this->product->id, $ids, true);
        }

        if ($action->group === 'brand') {
            return in_array((string) $this->product->brand_id, $ids, true);
        }

        if ($action->group === 'category') {
            return ProductCategory::query()
                ->where('product_id', $this->product->id)
                ->whereIn('category_id', $ids)
                ->exists();
        }

        return false;
    }


    /**
     * @return bool
     */
    public function isActionOnAllProducts(ProductAction|null $product_action = null): bool
    {
        return $product_action && $product_action->group === 'all';
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return array
     */
    public function getActionProductsList(ProductAction|null $product_action = null): array
    {
        $action = $this->resolveRealAction($product_action);

        if ( ! $action || $action->group === 'total') {
            return [];
        }

        $ids = json_decode($action->links, true) ?: [];

        if ($action->group == 'product') {
            return $ids;
        }

        if ($action->group == 'category') {
            return ProductCategory::query()->whereIn('category_id', $ids)->pluck('product_id')->unique()->toArray();
        }

        if ($action->group == 'brand') {
            return Product::query()->whereIn('brand_id', $ids)->pluck('id')->unique()->toArray();
        }

        return [];
    }


    /**
     * @return mixed
     */
    public function getBestAction()
    {
        return $this->bestActionFrom($this->active_actions ?: collect());
    }


    /**
     * @return Special
     */
    private function setupAvailableActions(): Special
    {
        $this->active_actions = $this->eligibleActions()->whereNull('user_group_id');
        $this->action = $this->getBestAction();

        return $this;
    }


    /**
     * Active actions that apply to the current product and customer.
     */
    private function eligibleActions(): Collection
    {
        $query = ProductAction::query()
            ->active()
            ->where('group', '!=', 'total');

        $query->where(function ($query) {
            $query->whereNull('user_group_id');

            if ($this->user_group) {
                $query->orWhere('user_group_id', $this->user_group);
            }
        });

        return $query->get()->filter(function (ProductAction $action) {
            return $this->checkDates($action)
                && $this->checkCoupon($action)
                && $this->isProductOnAction($action);
        })->values();
    }


    /**
     * Select the action that produces the lowest final price.
     */
    private function bestActionFrom(Collection $actions): ?ProductAction
    {
        $best_action = null;
        $best_price = null;

        foreach ($actions as $action) {
            $price = $this->getDiscountPrice($action);

            if ($best_action === null || $price < $best_price) {
                $best_action = $action;
                $best_price = $price;
            }
        }

        return $best_action;
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return ProductAction|null
     */
    private function resolveRealAction(ProductAction|null $product_action = null)
    {
        if ($product_action) {
            return $product_action;
        }

        if ($this->user_group) {
            if ($this->user_group_action) {
                return $this->user_group_action;
            }
        }

        return $this->action;
    }
}
