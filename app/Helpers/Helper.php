<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Marketing\Action;
use App\Models\Back\Settings\Settings;
use App\Models\Back\Widget\WidgetGroup;
use App\Models\Front\Blog;
use App\Models\Front\Loyalty;
use App\Models\Front\Recepti;
use App\Models\Front\Catalog\Author;

use App\Models\Back\Marketing\Review;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use Darryldecode\Cart\CartCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\False_;

class Helper
{

    /**
     * @param float $price
     * @param int   $discount
     *
     * @return float|int
     */
    public static function calculateDiscountPrice(float $price, int $discount, string $type)
    {
        if ($type == 'F') {
            return $price - $discount;
        }

        return $price - ($price * ($discount / 100));
    }


    /**
     * @param $list_price
     * @param $seling_price
     *
     * @return float|int
     */
    public static function calculateDiscount($list_price, $seling_price, string $type = 'P')
    {
        if (is_string($list_price)) {
            $list_price = str_replace('.', '', $list_price);
            $list_price = str_replace(',', '.', $list_price);
        }
        if (is_string($seling_price)) {
            $seling_price = str_replace('.', '', $seling_price);
            $seling_price = str_replace(',', '.', $seling_price);
        }

        if ($type == 'F') {
            return $list_price - $seling_price;
        }

        return (($list_price - $seling_price) / $list_price) * 100;
    }


    /**
     * @return string[]
     */
    public static function abc()
    {
        return ['A', 'B', 'C', 'Ć', 'Č', 'D', 'Đ', 'Dž', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'Lj', 'M', 'N', 'Nj', 'O', 'P', 'R', 'S', 'Š', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ž'];
    }


    /**
     * @param string $price
     *
     * @return string
     */
    public static function priceString($price): string
    {
        if (is_float($price)) {
            $price = '"' . number_format($price, 2) . '"';
        }

        if ( ! is_string($price)) {
            return 'Not a number.!';
        }

        $set = explode('.', $price);

        if ( ! isset($set[1])) {
            $set[1] = '00';
        }

        return number_format($price, 0, '', '.') . ',<small>' . substr($set[1], 0, 2) . 'kn</small>';
    }


    /**
     * @param string $target
     * @param bool   $builder
     *
     * @return array|false|Collection
     */
    public static function search(string $target = '', bool $builder = false, bool $api = false)
    {
        if ($target != '') {
            $response = collect();

            Log::info('target' . $target);

            // Search products.
            $products = Product::query()->whereHas('translation', function (Builder $query) use ($target) {
                $query->where('name', 'like', '%' . $target . '%');
            })->orWhere('sku', 'like', '%' . $target . '%')/*->orwhereHas('translation', function ($query) use ($target) {
                $query->where('meta_description', 'like', '%' . $target . '%');
            })->orWhere('sku', 'like', '%' . $target . '%')
                               ->orwhereHas('translation', function ($query) use ($target) {
                                   $query->where( 'sastojci', 'like', '%' . $target . '%');
                               })->orwhereHas('translation', function ($query) use ($target) {
                    $query->where('podaci', 'like', '%' . $target . '%');
                })->orwhereHas('translation', function ($query) use ($target) {
                    $query->where('description', 'like', '%' . $target . '%');
                })*/->pluck('id');

            // If API and count is too small
            // search additionaly.

            if ($api) {
                $products = $products->take(5);
            }

            if ( ! $products->count()) {
                $products = collect();
            }

            $response->put('products', $products->unique()->flatten());

            if ($builder) {
                return $response;
            }

            return $response['products']->toJson();
        }

        return false;
    }


    /**
     * @param Builder $query
     * @param string  $search
     *
     * @return Builder
     */
    public static function searchByTitle(Builder $query, string $search): Builder
    {
        $preg = explode(' ', $search, 3);
        if (isset ($preg[1]) && in_array($preg[1], $preg) && ! isset($preg[2])) {
            $query->whereHas('translation', function ($query) use ($preg) {
                $query->where('title', 'like', '%' . $preg[0] . '%' . $preg[1] . '%');
            })->orwhereHas('translation', function ($query) use ($preg) {
                $query->where('title', 'like', '%' . $preg[1] . '% ' . $preg[0] . '%');
            });

        } elseif (isset ($preg[2]) && in_array($preg[2], $preg)) {
            $query->whereHas('translation', function ($query) use ($preg) {
                $query->where('title', 'like', $preg[0] . '%' . $preg[1] . '%' . $preg[2] . '%');
            })->orwhereHas('translation', function ($query) use ($preg) {
                $query->where('title', 'like', $preg[2] . '%' . $preg[1] . '% ' . $preg[0] . '%');
            })->orwhereHas('translation', function ($query) use ($preg) {
                $query->where('title', 'like', $preg[0] . '%' . $preg[2] . '% ' . $preg[1] . '%');
            })->orwhereHas('translation', function ($query) use ($preg) {
                $query->where('title', 'like', $preg[1] . '%' . $preg[0] . '% ' . $preg[2] . '%');
            })->orwhereHas('translation', function ($query) use ($preg) {
                $query->where('title', 'like', $preg[1] . '%' . $preg[2] . '% ' . $preg[0] . '%');
            });

        } else {
            $query->whereHas('translation', function ($query) use ($preg) {
                $query->where('title', 'like', '%' . $preg[0] . '%');
            });

        }

        return $query;
    }


    /**
     * @param $cat
     * @param $subcat
     *
     * @return mixed
     */
    public static function getRelated($cat = null, $subcat = null)
    {
        $related = [];

        if ($subcat) {
            $related = $subcat->products()->inRandomOrder()->take(10)->groupBy('id')->get();
        }

       else  {
            $related = $cat->products()->inRandomOrder()->take(10)->groupBy('id')->get();
        }

        if ($related->count() < 9) {
            $related->merge(Product::query()->inRandomOrder()->take(10 - $related->count())->get());
        }

        return $related;
    }


    /**
     * @param string $description
     *
     * @return false|string
     */
    public static function setDescription(string $description, int $page_id)
    {
        if ($description == '') {
            return '';
        }

        /*$ids = Cache::remember('wg_ids' . $page_id . current_locale(), config('cache.life'), function () use ($description) {
            $iterator = substr_count($description, '++');
            $offset   = 0;
            $ids      = [];

            for ($i = 0; $i < $iterator / 2; $i++) {
                $from  = strpos($description, '++', $offset) + 2;
                $to    = strpos($description, '++', $from + 2);
                $ids[] = substr($description, $from, $to - $from);

                $offset = $to + 2;
            }

            return $ids;
        });*/

        $iterator = substr_count($description, '++');
        $offset   = 0;
        $ids      = [];

        for ($i = 0; $i < $iterator / 2; $i++) {
            $from  = strpos($description, '++', $offset) + 2;
            $to    = strpos($description, '++', $from + 2);
            $ids[] = substr($description, $from, $to - $from);

            $offset = $to + 2;
        }

        /*$wgs = Cache::remember('wgs' . $page_id . current_locale(), config('cache.life'), function () use ($ids) {
            return WidgetGroup::whereIn('id', $ids)->orWhereIn('slug', $ids)->where('status', 1)->with('widgets')->get();
        });*/

        $wgs = WidgetGroup::whereIn('id', $ids)->orWhereIn('slug', $ids)->where('status', 1)->with('widgets')->get();

        foreach ($ids as $id) {
            /*$description = Cache::remember('wg.' . $id . current_locale(), config('cache.life'), function () use ($wgs, $description, $id) {
                return static::resolveDescription($wgs, $description, $id);
            });*/
            $description = static::resolveDescription($wgs, $description, $id);
        }

        return $description;
    }


    /**
     * @param Collection $wgs
     * @param string     $description
     * @param string     $id
     *
     * @return string
     */
    private static function resolveDescription(Collection $wgs, string $description, string $id): string
    {
        $wg = $wgs->where('id', $id)->first();

        if ( ! $wg) {
            $wg = $wgs->where('slug', $id)->first();
        }

        $widgets = [];

        if ($wg->template == 'product_carousel' || $wg->template == 'page_carousel') {
            $widget = $wg->widgets()->first();

            if ( ! $widget || empty($widget->data)) {
                return '';
            }

            $data = unserialize($widget->data);

            if (static::isDescriptionTarget($data, 'product')) {
                $items     = static::products($data)->get();
                $tablename = 'product';
            }

            if (static::isDescriptionTarget($data, 'blog')) {
                $items     = static::blogs($data)->get();
                $tablename = 'blog';
            }

            if (static::isDescriptionTarget($data, 'recepti')) {
                $items     = static::receptis($data)->get();
                $tablename = 'recepti';
            }

            if (static::isDescriptionTarget($data, 'brand')) {
                $items     = static::brand($data)->get();
                $tablename = 'brand';
            }

            if (static::isDescriptionTarget($data, 'category')) {
                $items     = static::category($data)->get();
                $tablename = 'category';
            }

            if (static::isDescriptionTarget($data, 'publisher')) {
                $items     = static::publisher($data)->get();
                $tablename = 'publisher';
            }

            if (static::isDescriptionTarget($data, 'reviews')) {
                $items     = static::reviews($data)->get();
                $tablename = 'reviews';
            }

            $widgets = [
                'title'      => $widget->title,
                'subtitle'   => $widget->subtitle,
                'url'        => $widget->url,
                'tablename'  => $tablename,
                'css'        => $data['css'],
                'container'  => (isset($data['container']) && $data['container'] == 'on') ? 1 : null,
                'background' => (isset($data['background']) && $data['background'] == 'on') ? 1 : null,
                'items'      => $items
            ];

        } else {
            foreach ($wg->widgets()->orderBy('sort_order')->get() as $widget) {
                $data = unserialize($widget->data);


                $widgets[] = [
                    'title'    => $widget->title,
                    'subtitle' => $widget->subtitle,
                    'color'    => $widget->badge,
                    'url'      => $widget->url,
                    'image'    => str_replace('.jpg', '.webp', $widget->image),
                    'width'    => $widget->width,
                    'right'    => (isset($data['right']) && $data['right'] == 'on') ? 1 : null,
                ];
            }
        }

        return str_replace(
            '++' . $id . '++',
            view('front.layouts.widget.widget_' . $wg->template, ['data' => $widgets]),
            $description
        );
    }


    /**
     * @param array  $data
     * @param string $target
     *
     * @return bool
     */
    public static function isDescriptionTarget(array $data, string $target): bool
    {
        if (isset($data['target']) && $data['target'] == $target) {
            return true;
        }
        if (isset($data['group']) && $data['group'] == $target) {
            return true;
        }

        return false;
    }


    /**
     * @param string $text
     *
     * @return string
     */
    public static function resolveFirstLetter(string $text): string
    {
        $letter = substr($text, 0, 1);

        if (in_array(substr($text, 0, 2), ['Nj', 'Lj', 'Š', 'Č', 'Ć', 'Ž', 'Đ'])) {
            $letter = substr($text, 0, 2);
        }

        if (in_array(substr($text, 0, 3), ['Dž', 'Đ'])) {
            $letter = substr($text, 0, 3);
        }

        return $letter;
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function products(array $data): Builder
    {
        $prods = (new Product())->newQuery();

        $prods->active()->available()->last();

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $prods->popular();
        }

        if (isset($data['list']) && $data['list']) {
            $prods->whereIn('id', $data['list']);
        }

        return $prods;
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function blogs(array $data): Builder
    {
        $blogs = (new Blog())->newQuery();

        $blogs->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $blogs->last();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $blogs->popular();
        }

        if (isset($data['list']) && $data['list']) {
            $blogs->whereIn('id', $data['list']);
        }

        return $blogs;
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function receptis(array $data): Builder
    {
        $receptis = (new Recepti())->newQuery();

        $receptis->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $receptis->last();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $receptis->popular();
        }

        if (isset($data['list']) && $data['list']) {
            $receptis->whereIn('id', $data['list']);
        }

        return $receptis;
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function category(array $data): Builder
    {
        $category = (new Category())->newQuery();

        $category->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $category->latest();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $category->latest();
        }

        if (isset($data['list']) && $data['list']) {
            $category->whereIn('id', $data['list']);
        }

        return $category;
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function publisher(array $data): Builder
    {
        $publisher = (new Publisher())->newQuery();

        $publisher->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $publisher->latest();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $publisher->latest();
        }

        if (isset($data['list']) && $data['list']) {
            $publisher->whereIn('id', $data['list']);
        }

        return $publisher;
    }

    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function brand(array $data): Builder
    {
        $brand = (new Brand())->newQuery();

        $brand->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $brand->latest();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $brand->latest();
        }

        if (isset($data['list']) && $data['list']) {
            $brand->whereIn('id', $data['list']);
        }

        return $brand;
    }


    /**
     * @param string $title
     * @param string $target
     *
     * @return string
     */
    public static function resolveViewFilepath(string $title, string $target): string
    {
        $path = '';

        if (in_array($target, ['pages', 'page'])) {
            $path = config('settings.pages_views');
        }
        if (in_array($target, ['widgets', 'widget'])) {
            $path = config('settings.widgets_views');
        }

        return $path . $title . '.blade.php';
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function reviews(array $data): Builder
    {
        $reviews = (new Review())->newQuery();

        $reviews->where('featured', '1')->limit(10)->get();

        return $reviews;
    }


    /**
     * @param string $tag
     *
     * @return \Illuminate\Cache\TaggedCache|mixed|object
     */
    public static function resolveCache(string $tag): ?object
    {
        if (env('APP_ENV') == 'local') {
            return Cache::getFacadeRoot();
        }

        return Cache::tags([$tag]);
    }


    /**
     * @param string $tag
     * @param string $key
     *
     * @return object|bool|mixed|null
     */
    public static function flushCache(string $tag, string $key)
    {
        if (env('APP_ENV') == 'local') {
            return Cache::getFacadeRoot();
        }

        return Cache::tags([$tag])->forget($key);
    }


    /**
     * @return null
     */
    public static function getEur()
    {
        $eur = Settings::get('currency', 'list')->where('code', 'EUR')->first();

        if (isset($eur->status) && $eur->status) {
            return $eur->value;
        }

        return null;
    }


    public static function categoryGroupPath(bool $slug = false): string
    {
        if ($slug) {
            return Str::slug(config('settings.group_path'));
        }

        return config('settings.group_path');
    }


    /**
     * @param array  $data
     * @param string $tag
     * @param        $target
     *
     * @return string
     */
    public static function resolveSlug(array $data, string $tag = 'title', $target = null): string
    {
        $slug = null;

        if ($target) {
            $product = Product::where('id', $target)->first();

            if ($product) {
                $slug = $product->slug;
            }
        }

        $slug  = $slug ?: Str::slug($data[$tag]);
        $exist = Product::where('slug', $slug)->count();

        $cat_exist = Category::where('slug', $slug)->count();

        if (($cat_exist || $exist > 1) && $target) {
            return $slug . '-' . time();
        }

        if (($cat_exist || $exist) && ! $target) {
            return $slug . '-' . time();
        }

        return $slug;
    }


    /**
     * @param $cart
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasSpecialCartCondition($cart = null)
    {
        $condition     = false;
        $has_condition = false;

        if ($cart->getTotal() > 50) {
            $has_condition = 10;
        }
        if ($cart->getTotal() > 100) {
            $has_condition = 15;
        }
        if ($cart->getTotal() > 200) {
            $has_condition = 20;
        }

        if ($has_condition && self::isDateBetween()) {
            $value    = self::calculateDiscountPrice($cart->getTotal(), $has_condition, 'P');
            $discount = $cart->getTotal() - $value;

            $condition = new CartCondition(array(
                'name'       => config('settings.special_action.title'),
                'type'       => 'special',
                'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                'value'      => '-' . $discount,
                'attributes' => [
                    'description' => '',
                    'geo_zone'    => ''
                ]
            ));
        }

        return $condition;
    }


    /**
     * @param        $cart
     * @param string $coupon
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasCouponCartConditions($cart, string $coupon = '')
    {
        $condition = false;
        $actions   = Action::query()->where('group', 'total')->get();

        if ($actions->count()) {
            foreach ($actions as $action) {
                if ($action->isValid($coupon)) {
                    $value    = self::calculateDiscountPrice($cart->getTotal(), $action->discount, $action->type);
                    $discount = $cart->getTotal() - $value;

                    $condition = new CartCondition(array(
                        'name'       => $action->title,
                        'type'       => 'special',
                        'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                        'value'      => '-' . $discount,
                        'attributes' => $action->setConditionAttributes($coupon)
                    ));
                }
            }
        }

        return $condition;
    }


    /**
     * @param        $cart
     * @param string $coupon
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasLoyaltyCartConditions($cart, int $loyalty = 0)
    {
        $condition = false;
        $has_loyalty   = Loyalty::hasLoyalty();

        if ($has_loyalty) {
            $discount = Loyalty::calculateLoyalty($loyalty);

            if ($cart->getTotal() > $discount) {
                $condition = new CartCondition(array(
                    'name'       => 'Loyalty',
                    'type'       => 'special',
                    'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                    'value'      => '-' . $discount,
                    'attributes' => [
                        'type'        => 'loyalty',
                        'description' => 'Loyalty Program'
                    ]
                ));
            }
        }

        return $condition;
    }


    /**
     * @param $cart
     *
     * @return false|mixed
     */
    public static function isCouponUsed($cart)
    {
        $coupon = false;
        $items = $cart->getContent();

        foreach ($items as $item) {
            if ($item->getConditions()->getType() == 'coupon') {
                $coupon = $item->getConditions()->getTarget();
            }
        }

        foreach ($cart->getConditions() as $condition) {
            if (isset($condition->getAttributes()['type']) && $condition->getAttributes()['type'] == 'coupon' && floatval($condition->getValue()) < 0) {
                $coupon = $condition->getAttributes()['description'];
            }
        }



        return $coupon;
    }


    /**
     * @param $date
     *
     * @return bool
     */
    public static function isDateBetween($date = null): bool
    {
        $now   = $date ?: Carbon::now();
        $start = Carbon::createFromFormat('d/m/Y H:i:s', config('settings.special_action.start'));
        $end   = Carbon::createFromFormat('d/m/Y H:i:s', config('settings.special_action.end'));

        if ($now->isBetween($start, $end)) {
            return true;
        }

        return false;
    }

}
