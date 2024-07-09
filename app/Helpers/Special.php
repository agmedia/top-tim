<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Front\Catalog\ProductAction;
use App\Models\Front\Catalog\Product;
use Carbon\Carbon;
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

    /**
     * @var Product|null
     */
    protected $product;

    /**
     * @var ProductAction|null
     */
    protected $action;

    /**
     * @var
     */
    protected $user_group_action;


    /**
     * @param Product|null       $product
     * @param ProductAction|null $product_action
     */
    public function __construct(Product $product = null, ProductAction $product_action = null)
    {
        $this->user    = Auth::user();
        $this->product = $product;
        $this->action  = $product_action;
    }


    /**
     * @return mixed
     */
    public function getUserGroupAction(): ?ProductAction
    {
        return $this->user_group_action;
    }


    /**
     * @return bool
     */
    public function userHasGroupDiscount(): bool
    {
        $group_id = $this->user->details->group ? $this->user->details->group->id : null;

        if ($group_id) {
            $this->user_group_action = ProductAction::query()->where('user_group_id', $group_id)->active()->first();

            if ($this->user_group_action) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return float|int
     */
    public function getUUserGroupDiscount(ProductAction $product_action = null): float|int
    {
        $action = $product_action ?: $this->action;

        if ( ! $action) {
            return $this->product->price;
        }

        if ($this->isProductOnAction($action)) {
            return Helper::calculateDiscountPrice($this->product->price, $action->discount, $action->type);
        }

        return $this->product->price;
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return bool
     */
    public function checkCoupon(ProductAction $product_action = null): bool
    {
        $action             = $product_action ?: $this->action;
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
    public function checkDates(ProductAction $product_action = null): bool
    {
        $action = $product_action ?: $this->action;

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
    public function isProductOnAction(ProductAction $product_action = null): bool
    {
        $action = $product_action ?: $this->action;

        if ($this->isActionOnAllProducts($action)) {
            return true;
        }

        $ids = $this->getActionProductsList($action);

        if (in_array($this->product->id, $ids)) {
            return true;
        }

        return false;
    }


    /**
     * @return bool
     */
    public function isActionOnAllProducts(ProductAction $product_action = null): bool
    {
        if (in_array($product_action->group, ['all', 'total'])) {
            return true;
        }

        return false;
    }


    /**
     * @param ProductAction|null $product_action
     *
     * @return array
     */
    public function getActionProductsList(ProductAction $product_action = null): array
    {
        $action = $product_action ?: $this->action;

        $ids = collect($action->action_list)->flatten()->toArray();

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

}