<?php

    namespace App\Models\Front\Catalog;

    use App\Helpers\Helper;
    use App\Helpers\ProductHelper;
    use App\Models\Back\Catalog\Product\ProductAttribute;
    use Carbon\Carbon;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Http\Request;
    use Illuminate\Support\Str;

    class Attributes extends Model
    {

        /**
         * @var string
         */
        protected $table = 'attributes';

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
                return $this->hasOne(AttributesTranslation::class, 'attribute_id')->where('lang', $lang)->first();
            }

            if ($all) {
                return $this->hasMany(AttributesTranslation::class, 'attribute_id');
            }

            return $this->hasOne(AttributesTranslation::class, 'attribute_id')->where('lang', $this->locale);
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
         * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
         */
        public function products()
        {
            return $this->hasManyThrough(Product::class, ProductAttribute::class, 'attribute_id', 'id', 'id', 'product_id');
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
         * @param array $request
         * @param int   $limit
         *
         * @return Builder
         */
        public function filter(array $request, int $limit = 20): Builder
        {
            $query = (new Attributes())->newQuery();

            if (isset($request['search_attribute']) && $request['search_attribute']) {
                $query->active();

                $query = Helper::searchByTitle($query, $request['search_attribute']);

            } else {
                $query->active();

                if ($request['group'] && ( ! isset($request['search_attribute']) || ! $request['search_attribute'])) {
                    $query->whereHas('products', function ($query) use ($request) {
                        $query = ProductHelper::queryCategories($query, $request);
                      /*  if ($request['attribute']) {
                            if (strpos($request['attribute'], '+') !== false) {
                                $arr  = explode('+', $request['option']);
                                $pids = ProductAttribute::query()->whereIn('attribute_id', $arr)->pluck('product_id')->unique();
                            } else {
                                $pids = ProductAttribute::query()->where('attribute_id', $request['option'])->pluck('product_id')->unique;
                            }

                            $query->whereIn('id', $pids->toArray());
                        }*/
                    });
                }

                if ( ! $request['group'] && $request['ids']) {

                    $_ids = collect(explode(',', substr($request['ids'], 1, -1)))->unique();
                    $query->whereHas('products', function ($query) use ($_ids) {
                        $query->active()->hasStock()->whereIn('id', $_ids);
                    })->orwhereHas('subproducts', function ($query) use ($_ids) {
                        $query->active()->hasStock()->whereIn('id', $_ids);
                    });
                }
            }

            $query->limit($limit)
                 // ->withCount('products')
                  ->orderBy('sort_order');

            return $query;
        }

    }

