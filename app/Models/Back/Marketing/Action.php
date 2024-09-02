<?php

namespace App\Models\Back\Marketing;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Back\UserGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Action extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_actions';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $locale = 'en';


    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->locale = current_locale();
    }


    /**
     * @param null  $lang
     * @param false $all
     *
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function translation($lang = null, bool $all = false)
    {
        if ($lang) {
            return $this->hasOne(ActionTranslation::class, 'product_action_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(ActionTranslation::class, 'product_action_id');
        }

        return $this->hasOne(ActionTranslation::class, 'product_action_id')->where('lang', $this->locale);
    }


    public function userGroup()
    {
        return $this->hasOne(UserGroup::class, 'id', 'user_group_id');
    }


    /**
     * Validate new action Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->validate([
            'title'    => 'required',
            'type'     => 'required',
            'group'    => 'required',
            'discount' => 'required'
        ]);

        $this->request = $request;

        if ($this->listRequired()) {
            $request->validate([
                'action_list' => 'required'
            ]);
        }

        return $this;
    }


    /**
     * Store new category.
     *
     * @return false
     */
    public function create()
    {
        $data = $this->getRequestData();
        $id   = $this->insertGetId($this->getModelArray());

        if ($id) {
            ActionTranslation::create($id, $this->request);

            if ($this->shouldUpdateProducts($data)) {
                $this->updateProducts($this->resolveTarget($data['links']), $id, $data['start'], $data['end']);
            }

            return $this->find($id);
        }

        return false;
    }


    /**
     * @param Category $category
     *
     * @return false
     */
    public function edit()
    {
        $data       = $this->getRequestData();
        $deactivate = $this->shouldDeactivateProducts($this->status, $data['status']);
        $updated    = $this->update($this->getModelArray(false));

        if ($updated) {
            ActionTranslation::edit($updated, $this->request);

            if ($this->shouldUpdateProducts($data)) {
                $this->updateProducts($this->resolveTarget($data['links']), $this->id, $data['start'], $data['end']);
            }

            if ($deactivate) {
                $this->resolveDestruction($this->id, 0);
            }

            return $this;
        }

        return false;
    }


    /**
     * @return bool
     */
    public function isValid(string $coupon = ''): bool
    {
        $is_valid = false;

        if ($this->status) {
            $from = now()->subDay();
            $to   = now()->addDay();

            if ($this->date_start && $this->date_start != '0000-00-00 00:00:00') {
                $from = Carbon::make($this->date_start);
            }
            if ($this->date_end && $this->date_end != '0000-00-00 00:00:00') {
                $to = Carbon::make($this->date_end);
            }

            if ($from <= now() && now() <= $to) {
                $is_valid = true;
            }

            if ($is_valid) {
                $is_valid = false;

                if ($this->coupon && $coupon != '' && $coupon == $this->coupon) {
                    $is_valid = true;
                }

                if ( ! $this->coupon) {
                    $is_valid = true;
                }
            }
        }

        return $is_valid;
    }


    /**
     * @param string $coupon
     *
     * @return string[]
     */
    public function setConditionAttributes(string $coupon = ''): array
    {
        $response = [
            'type'        => '',
            'description' => ''
        ];

        if ($coupon != '') {
            $response = [
                'type'        => 'coupon',
                'description' => $coupon
            ];
        }

        return $response;
    }


    /**
     * @param int $action_id
     * @param int $complete
     *
     * @return bool
     */
    public function resolveDestruction(int $action_id, int $complete = 1): bool
    {
        $action = Action::query()->find($action_id);

        if ($action) {
            $products_updated = $action->truncateProducts();

            if ($products_updated) {
                if ($complete) {
                    $action->delete();

                    ActionTranslation::query()->where('product_action_id', $action_id)->delete();
                }

                return true;
            }
        }

        return false;
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param bool $insert
     *
     * @return array
     */
    private function getModelArray(bool $insert = true): array
    {
        $data = $this->getRequestData();

        $response = [
            'type'          => $this->request->type,
            'discount'      => $this->request->discount,
            'group'         => $this->request->group,
            'links'         => $data['links']->flatten()->toJson(),
            'date_start'    => $data['start'],
            'date_end'      => $data['end'],
            'user_group_id' => $this->request->user_group,
            'coupon'        => $this->request->coupon,
            'quantity'      => $data['coupon_quantity'],
            'lock'          => $data['lock'],
            'status'        => $data['status'],
            'updated_at'    => Carbon::now()
        ];

        if ($insert) {
            $response['created_at'] = Carbon::now();
        }

        return $response;
    }


    /**
     * @return array
     */
    private function getRequestData(): array
    {
        $links = collect([$this->request->group]);

        if ($this->request->action_list) {
            $links = collect($this->request->action_list);
        }

        return [
            'links'           => $links,
            'status'          => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'start'           => $this->request->date_start ? Carbon::make($this->request->date_start) : null,
            'end'             => $this->request->date_end ? Carbon::make($this->request->date_end) : null,
            'coupon_quantity' => (isset($this->request->coupon_quantity) and $this->request->coupon_quantity == 'on') ? 1 : 0,
            'lock'            => (isset($this->request->lock) and $this->request->lock == 'on') ? 1 : 0,
        ];
    }


    /**
     * @param array $data
     *
     * @return bool
     */
    private function shouldUpdateProducts(array $data): bool
    {
        if ($this->request->group == 'total' || $this->request->user_group) {
            return false;
        }

        if ($data['status']) {
            return true;
        }

        return false;
    }


    /**
     * @param int $new_status
     *
     * @return bool
     */
    private function shouldDeactivateProducts(int $old_status, int $new_status): bool
    {
        if ($old_status == 1 && $new_status == 0) {
            return true;
        }

        return false;
    }


    /**
     * @return bool
     */
    private function listRequired(): bool
    {
        if (in_array($this->request->group, ['all', 'total'])) {
            return false;
        }

        return true;
    }


    /**
     * @param $links
     *
     * @return mixed
     */
    private function resolveTarget($links)
    {
        if (in_array($this->request->group, ['product', 'category', 'brand', 'all'])) {
            $products = Product::query();

            if ($this->request->group == 'product') {
                $products->whereIn('id', $links);
            }

            if ($this->request->group == 'category') {
                $ids = ProductCategory::whereIn('category_id', $links)->pluck('product_id')->unique();

                $products->whereIn('id', $ids);
            }

            if ($this->request->group == 'brand') {
                return $products->whereIn('brand_id', $links);
            }

            $products = $this->removeLockedActionsProducts($products);

            return $products->pluck('id')
                            ->unique();
        }

        return $this->request->group;
    }


    /**
     * @param Builder $products
     *
     * @return Builder
     */
    private function removeLockedActionsProducts(Builder $products): Builder
    {
        $locked_actions = Action::query()->where('lock', 1)->get();

        foreach ($locked_actions as $locked_action) {
            $links = json_decode($locked_action->links, true);

            if ($locked_action->group == 'product') {
                $products->whereNotIn('id', $links);
            }

            if ($locked_action->group == 'category') {
                $ids = ProductCategory::whereIn('category_id', $links)->pluck('product_id')->unique();

                $products->whereNotIn('id', $ids);
            }

            if ($locked_action->group == 'brand') {
                $products->whereNotIn('brand_id', $links);
            }

            if ($locked_action->group == 'all') {
                $ids = Product::query()->pluck('id')->unique();

                $products->whereNotIn('id', $ids);
            }
        }

        //dd($products->pluck('id'));

        return $products;
    }


    /**
     * @param     $target
     * @param int $id
     * @param     $start
     * @param     $end
     */
    private function updateProducts($target, int $id, $start, $end): void
    {
        $query    = [];
        $products = Product::query()->whereIn('id', $target)->pluck('price', 'id');

        foreach ($products->all() as $k_id => $price) {
            $query[] = [
                'product_id' => $k_id,
                'special'    => Helper::calculateDiscountPrice($price, $this->request->discount, $this->request->type)
            ];
        }

        $start = $start ?: 'null';
        $end   = $end ?: 'null';

        DB::table('temp_table')->truncate();

        foreach (array_chunk($query, 500) as $chunk) {
            DB::table('temp_table')->insert($chunk);
        }

        DB::select(DB::raw("UPDATE products p INNER JOIN temp_table tt ON p.id = tt.product_id SET p.special = tt.special, p.action_id = " . $id . ", p.special_from = '" . $start . "', p.special_to = '" . $end . "';"));

        DB::table('temp_table')->truncate();
    }


    /**
     * @return mixed
     */
    private function truncateProducts(int $action_id = 0)
    {
        $id = $this->id ?? 0;

        if ($action_id) {
            $id = $action_id;
        }

        return Product::where('action_id', $id)->update([
            'action_id'    => 0,
            'special'      => null,
            'special_from' => null,
            'special_to'   => null,
        ]);
    }
}
