<?php

namespace App\Models\Front\Catalog;

use App\Helpers\Helper;
use App\Helpers\ProductHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Class Author
 * @package App\Models\Front\Catalog
 */
class Brand extends Model implements \Mcamara\LaravelLocalization\Interfaces\LocalizedUrlRoutable
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'brands';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $appends = ['title', 'description', 'url'];

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
     * @param $locale
     *
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    public function getLocalizedRouteKey($locale)
    {
        //Log::info('$locale = ' . $locale);

        return $this->translation($locale)->slug;
    }


    /**
     * @param $value
     * @param $field
     *
     * @return Model|never|null
     */
    public function resolveRouteBinding($value, $field = NULL)
    {
        //$fallback = $this->locale == 'en' ? 'hr' : 'en';

        /*Log::info('$fallback = ' . $fallback);*/
       // Log::info('$value = ' . $value);
       // Log::info('$this->locale = ' . $this->locale);
       // Log::info('current_locale() = ' . current_locale());

        return static::query()->whereHas('translation', function ($query) use ($value) {
            $query->where('slug', $value);
        })/*->orWhereHas('translation', function ($query) use ($value, $fallback) {
            $query->where('lang', $fallback)->where('slug', $value);
        })*/->first() ?? abort(404);
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
            return $this->hasOne(BrandTranslation::class, 'brand_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(BrandTranslation::class, 'brand_id');
        }

        return $this->hasOne(BrandTranslation::class, 'brand_id')->where('lang', $this->locale);
    }

    public function translations()
    {
        return $this->hasMany(BrandTranslation::class, 'brand_id');
    }


    /**
     * @return string
     */
    public function getTitleAttribute()
    {
        return $this->translation->title;
    }


    /**
     * @return string
     */
    public function getDescriptionAttribute()
    {
        return $this->translation->description;
    }


    /**
     * @return string
     */
    public function getUrlAttribute()
    {
        return config('settings.brand_path'). '/' . $this->translation->slug;
    }



    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', 'id')->active()->hasStock();
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
     * @param $query
     *
     * @return mixed
     */
    public function scopeBasicData($query)
    {
        return $query->select('id', 'title', 'slug', 'url');
    }


    /**
     * @param array $request
     * @param int   $limit
     *
     * @return Builder
     */
    public function filter(array $request, int $limit = 20): Builder
    {
        //Log::info($request);
        $query = (new Brand())->newQuery();

        if (isset($request['search_brand']) && $request['search_brand']) {
            $query->active();

            $query = Helper::searchByTitle($query, $request['search_brand']);

        } else {
            $query->active()->featured();

            if ($request['group'] && ( ! isset($request['search_brand']) || ! $request['search_brand'])) {
                $query->whereHas('products', function ($query) use ($request) {
                    $query = ProductHelper::queryCategories($query, $request);

                 if ($request['brand']) {
                        if (strpos($request['brand'], '+') !== false) {
                            $arr = explode('+', $request['brand']);
                          $pubs = Brand::query()->whereIn('slug', $arr)->pluck('id');

                         $query->whereIn('brand_id', $pubs);
                        } else {
                            $query->where('brand_id', $request['brand']);
                        }
                    }
                });
            }

            if (! $request['group'] && $request['brand']) {
                $query->whereHas('products', function ($query) use ($request) {
                    $query = ProductHelper::queryCategories($query, $request);
                    $query->where('brand_id', Brand::where('id', $request['brand'])->pluck('id')->first());
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


    /**
     * @return Collection
     */
    public static function letters(): Collection
    {
        $letters = collect();
        $brands = Brand::active()->pluck('letter')->unique();

        foreach (Helper::abc() as $item) {
            if ($item == $brands->contains($item)) {
                $letters->push([
                    'value' => $item,
                    'active' => true
                ]);
            } else {
                $letters->push([
                    'value' => $item,
                    'active' => false
                ]);
            }
        }

        return $letters;
    }


    /**
     * @param int $id
     *
     * @return Collection
     */
    public function categories(int $id = 0): Collection
    {
        $query = (new Category())->newQuery();

        $query->active();

        if ( ! $id) {
            $query->topList()->select('id', 'group', 'title', 'slug')->whereHas('products', function ($query) {
                $query->where('brand_id', $this->id);
            });

        } else {
            $query->whereHas('products', function ($query) {
                $query->where('brand_id', $this->id);
            })->where('parent_id', $id);
        }

        return $query/*->withCount(['products as products_count' => function ($query) {
                         $query->where('author_id', $this->id);
                     }])
                     */->sortByName()
                     ->get();
    }
}
