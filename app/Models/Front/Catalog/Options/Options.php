<?php

namespace  App\Models\Front\Catalog\Options;

use App\Helpers\Helper;
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



    public function getList()
    {
        $response = [];
        $values = Options::query()->get();

        foreach ($values as $value) {

            if($value->color_opt){
                $style ='background: linear-gradient(45deg, '.$value->color.' 50%, '.$value->color_opt.' 50%);';
            }
            else{
                $style ='background-color:'.$value->color;
            }


            $response[$value->group]['group'] = $value->translation->group_title;
            $response[$value->group]['items'][] = [
                'id' => $value->id,
                'title' => $value->translation->title,
                'value' => $value->color,
                'style' => $style,
                'value_opt'       => $value->color_opt,
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


    public function filter(array $request, int $limit = 20): Builder
    {

        $query = (new Options())->newQuery();

        if (isset($request['search_option']) && $request['search_option']) {
            $query->active();

            $query = Helper::searchByTitle($query, $request['search_option']);



        } else {
            $query->active();
            if ($request['group'] && ( ! isset($request['search_option']) || ! $request['search_option'])) {
                $query->whereHas('products', function ($query) use ($request) {
                    $query = ProductHelper::queryCategories($query, $request);
                    if ($request['option']) {
                        if (strpos($request['option'], '+') !== false) {
                            $arr = explode('+', $request['option']);
                            $pubs = Options::query()->whereIn('id', $arr)->pluck('id');
                            $query->whereIn('option_id', $pubs);
                        } else {
                            $query->where('option_id', $request['option']);
                        }
                    }
                });
            }
            if (! $request['group'] && $request['option']) {
                $query->whereHas('products', function ($query) use ($request) {
                    $query = ProductHelper::queryCategories($query, $request);
                    $query->where('option_id', Option::where('id', $request['option'])->pluck('id')->first());
                });
            }
            if (! $request['group'] && $request['ids']) {
                $_ids = collect(explode(',', substr($request['ids'], 1, -1)))->unique();
                $query->whereHas('products', function ($query) use ($_ids) {
                    $query->active()->hasStock()->whereIn('id', $_ids);
                });
            }
        }

        $query->limit($limit)
            ->withCount('products')
            ->orderBy('sort_order');



        return $query;
    }
}
