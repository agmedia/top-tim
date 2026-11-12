<?php

namespace App\Models\Front\Catalog\Options;

use App\Helpers\Helper;
use App\Helpers\OptionHelper;
use App\Helpers\ProductHelper;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\ProductOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Options extends Model
{

    /**
     * @var string
     */
    protected $table = 'options';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var string[]
     */
    protected $appends = ['name'];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $locale = 'en';


    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

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
            return $this->hasOne(OptionsTranslation::class, 'option_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(OptionsTranslation::class, 'option_id');
        }

        return $this->hasOne(OptionsTranslation::class, 'option_id')->where('lang', $this->locale);
    }


    /**
     * @param $value
     *
     * @return mixed
     */
    public function getGroupAttribute($value)
    {
        return $this->translation->group_title;
    }


    /**
     * @param $value
     *
     * @return mixed
     */
    public function getNameAttribute($value)
    {
        return $this->translation->title;
    }


    /**
     * @return array
     */
    public function getList()
    {
        $response = [];
        $values   = Options::query()->get();

        foreach ($values as $value) {
            $response[$value->group]['group']   = $value->translation->group_title;
            $response[$value->group]['items'][] = [
                'id'         => $value->id,
                'title'      => $value->translation->title,
                'value'      => $value->value,
                'style'      => OptionHelper::getStyle($value),
                'value_opt'  => $value->value_opt,
                'sort_order' => $value->sort_order
            ];
        }

        return $response;
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function products()
    {
        return $this->hasManyThrough(Product::class, ProductOption::class, 'option_id', 'id', 'id', 'product_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function subproducts()
    {
        return $this->hasManyThrough(Product::class, ProductOption::class, 'parent_id', 'id', 'id', 'product_id');
    }



    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', 1);
    }


    /**
     * @param array $request
     * @param int   $limit
     *                    If $limit is 0 result is not limited.
     *
     * @return Builder
     */
    public function filter(array $request, int $limit = 20): Builder
    {
        $params = array_filter(\Illuminate\Support\Arr::only($request, [
            'ids', 'group', 'cat', 'subcat', 'brand', 'option', 'attribute',
        ]), fn ($value) => $value !== null && $value !== '');
        $products = (new Product())->filter(new Request($params))->reorder()->select('products.id');
        // Resolve the small set of available options once, before loading translations.
        $links = ProductOption::query()->whereIn('product_id', $products)
            ->distinct()->toBase()->get(['option_id', 'parent_id']);
        $optionIds = $links->flatMap(fn ($link) => [$link->option_id, $link->parent_id])
            ->filter()->unique()->values()->all();

        $query = $this->newQuery()->active()->whereIn('options.id', $optionIds);
        if (!empty($request['search_option'])) {
            $query = Helper::searchByTitle($query, $request['search_option']);
        }
        if ($limit) {
            $query->limit($limit);
        }

        return $query->with('translation')->orderBy('sort_order');
    }
}
