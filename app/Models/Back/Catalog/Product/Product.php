<?php

namespace App\Models\Back\Catalog\Product;

use App\Helpers\Helper;
use App\Helpers\ProductHelper;
use App\Models\Back\Catalog\Attributes\Attributes;
use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Options\Options;
use App\Models\Back\Catalog\Publisher;
use App\Models\Back\Settings\Settings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Bouncer;
use Illuminate\Validation\ValidationException;

class Product extends Model
{

    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'products';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var null
     */
    protected $old_product = null;

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
            return $this->hasOne(ProductTranslation::class, 'product_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(ProductTranslation::class, 'product_id');
        }

        return $this->hasOne(ProductTranslation::class, 'product_id')->where('lang', $this->locale);
    }


    /**
     * @return Relation
     */
    public function categories()
    {
        return $this->hasManyThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')->where('parent_id', '==', 0);
    }


    /**
     * @return Relation
     */
    public function subcategories()
    {
        return $this->hasManyThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')->where('parent_id', '!=', 0);
    }


    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOneThrough|\Illuminate\Database\Query\Builder|mixed|object|null
     */
    public function category()
    {
        return $this->hasOneThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')
                    ->where('parent_id', '=', 0)
                    ->first();
    }


    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOneThrough|\Illuminate\Database\Query\Builder|mixed|object|null
     */
    public function subcategory()
    {
        return $this->hasOneThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')
                    ->where('parent_id', '!=', 0)
                    ->first();
    }


    public function attributes()
    {
        return $this->hasManyThrough(Attributes::class, ProductAttribute::class, 'product_id', 'id', 'id', 'attribute_id');
    }


    /**
     * @return Relation
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }


    /**
     * @return Relation
     */
    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function brand()
    {
        return $this->hasOne(Brand::class, 'id', 'brand_id');
    }


    /**
     * @return Relation
     */
    public function all_actions()
    {
        return $this->hasOne(ProductAction::class, 'product_id');
    }


    /**
     * @return false|mixed
     */
    public function special()
    {
        // If special is set, return special.
        if ($this->special) {
            $from = now()->subDay();
            $to   = now()->addDay();

            if ($this->special_from) {
                $from = Carbon::make($this->special_from);
            }
            if ($this->special_to) {
                $to = Carbon::make($this->special_to);
            }

            if ($from <= now() && now() <= $to) {
                return $this->special;
            }
        }

        return false;
    }


    /**
     * Validate New Product Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        // Validate the request.
        $request->validate([
            'name.*'     => 'required',
            'sku'      => 'required',
            'price'    => 'required',
            //'category' => 'required'
        ]);

        // Set Product Model request variable
        $this->setRequest($request);

        if ($this->isDuplicateSku()) {
            throw ValidationException::withMessages(['sku_dupl' => $this->request->sku . ' - Šifra već postoji...']);
        }

        if ($this->isDuplicateOptionSku()) {
            throw ValidationException::withMessages(['sku_opt' => 'Šifra opcije već postoji...']);
        }

        return $this;
    }


    /**
     * Create and return new Product Model.
     *
     * @return mixed
     */
    public function create()
    {
        $id = $this->insertGetId($this->createModelArray());

        if ($id) {
            $this->resolveCategories($id);
            $this->resolveAttributes($id);
            ProductTranslation::create($id, $this->request);

            return $this->find($id);
        }

        return false;
    }


    /**
     * Update and return new Product Model.
     *
     * @return mixed
     */
    public function edit()
    {
        //$this->old_product = $this->setHistoryProduct();

        $updated = $this->update($this->createModelArray('update'));

        if ($updated) {
            $this->resolveOptions();
            $this->resolveCategories($this->id);
            $this->resolveAttributes($this->id);
            ProductTranslation::edit($this->id, $this->request);

            return $this;
        }

        return false;
    }


    /**
     * @return array
     */
    public function getRelationsData(): array
    {
        return [
            'categories'     => (new Category())->getList(false),
            'attributes'     => (new Attributes())->getList(),
            'options'        => [],//ProductOption::getExistingOptions($this),
            'brands'         => '',
            'images'         => ProductImage::getExistingImages($this),
            'taxes'          => Settings::get('tax', 'list')
        ];
    }


    /**
     * @param Product $product
     *
     * @return mixed
     */
    public function storeImages(Product $product)
    {
        return (new ProductImage())->store($product, $this->request);
    }


    /**
     * @param string $type
     *
     * @return mixed
     */
    public function addHistoryData(string $type)
    {
        $new = $this->setHistoryProduct();

        $history = new ProductHistory($new, $this->old_product);

        return $history->addData($type);
    }


    /**
     * @param Request $request
     *
     * @return Builder
     */
    public function filter(Request $request): Builder
    {
        $query = (new Product())->newQuery();

        if ($request->has('search') && ! empty($request->input('search'))) {
            $query->whereHas('translation', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('search') . '%');
            })
                ->orwhereHas('translation', function ($query) use ($request) {
                    $query->where('description', 'like', '%' . $request->input('search')  . '%');
                          })
                  ->orWhere('sku', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->has('category') && ! empty($request->input('category'))) {
            $query->whereHas('categories', function ($query) use ($request) {
                $query->where('id', $request->input('category'));
            })->orWhereHas('subcategories', function ($query) use ($request) {
                $query->where('id', $request->input('category'));
            });
        }

        if ($request->has('brand') && ! empty($request->input('brand'))) {
            $query->where('brand_id', $request->input('brand'));
        }

        if ($request->has('status')) {
            if ($request->input('status') == 'active') {
                $query->where('status', 1);
            }
            if ($request->input('status') == 'inactive') {
                $query->where('status', 0);
            }
        }

        if ($request->has('sort')) {
            if ($request->input('sort') == 'new') {
                $query->orderBy('created_at', 'desc');
            }
            if ($request->input('sort') == 'old') {
                $query->orderBy('created_at', 'asc');
            }
            if ($request->input('sort') == 'price_up') {
                $query->orderBy('price', 'asc');
            }
            if ($request->input('sort') == 'price_down') {
                $query->orderBy('price', 'desc');
            }
            if ($request->input('sort') == 'az') {
                $query->orderBy(ProductTranslation::query()->select('name')->whereColumn('product_translations.product_id', 'products.id')->where('product_translations.lang', current_locale()));

            }
            if ($request->input('sort') == 'za') {

                $query->orderByDesc(ProductTranslation::query()->select('name')->whereColumn('product_translations.product_id', 'products.id')->where('product_translations.lang', current_locale()));

            }
            if ($request->input('sort') == 'qty_up') {
                $query->orderBy('quantity', 'asc');
            }
            if ($request->input('sort') == 'qty_down') {
                $query->orderBy('quantity', 'desc');
            }
        } else {
            $query->orderBy('updated_at', 'desc');

        }

        return $query;
    }

    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @param $request
     *
     * @return void
     */
    private function setRequest($request)
    {
        $this->request = $request;
    }


    /**
     * @param string $method
     *
     * @return array
     */
    private function createModelArray(string $method = 'insert'): array
    {
        $response = [
            'brand_id'     => $this->request->brand_id ?: 0,
            'sizeguide_id'     => $this->request->sizeguide_id ?: 0,
            'action_id'    => $this->request->action ?: 0,
            'sku'          => $this->request->sku,
            'ean'          => $this->request->ean ?? '',
            'price'        => isset($this->request->price) ? $this->request->price : 0,
            'quantity'     => $this->request->quantity ?: 0,
            'decrease'     => (isset($this->request->decrease) and $this->request->decrease == 'on') ? 0 : 1,
            'tax_id'       => $this->request->tax_id ?: 1,
            'special'      => $this->request->special,
            'special_from' => $this->request->special_from ? Carbon::make($this->request->special_from) : null,
            'special_to'   => $this->request->special_to ? Carbon::make($this->request->special_to) : null,
            'sort_order'   => 0,
            'push'         => 0,
            'status'       => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'updated_at'   => Carbon::now()
        ];

        if ($method == 'insert') {
            $response['created_at'] = Carbon::now();
        }

        return $response;
    }


    /**
     * @return mixed
     */
    private function setHistoryProduct()
    {
        $product = $this->where('id', $this->id)->first();

        $response             = $product->toArray();
        $response['category'] = [];

        if ($product->category()) {
            $response['category'] = $product->category()->toArray();
        }

        $response['subcategory'] = $product->subcategory() ? $product->subcategory()->toArray() : [];
        $response['images']      = $product->images()->get()->toArray();

        return $response;
    }


    /**
     * @param int|null $product_id
     *
     * @return Product
     */
    private function resolveCategories(int $product_id = null): Product
    {
        if ( ! empty($this->request->input('category')) && is_array($this->request->input('category'))) {
            ProductCategory::storeData($this->request->input('category'), $product_id ?: $this->id);
        }

        return $this;
    }


    /**
     * @param int|null $product_id
     *
     * @return Product
     */
    private function resolveAttributes(int $product_id = null): Product
    {
        if ( ! empty($this->request->input('attributes')) && is_array($this->request->input('attributes'))) {
            ProductAttribute::storeData($this->request->input('attributes'), $product_id ?: $this->id);
        }

        return $this;
    }


    /**
     * @param int|null $product_id
     *
     * @return Product
     */
    private function resolveOptions(int $product_id = null): Product
    {
        if ( ! empty($this->request->input('options')) && is_array($this->request->input('options'))) {
            $product_id = $product_id ?: $this->id;
            $inputs = $this->request->input('options');
            $groups = Options::query()->get()->unique('group')->pluck('group');

            foreach ($groups as $group) {
                $group = Str::slug($group);

                if ( ! empty($inputs[$group])) {
                    foreach ($inputs[$group] as $option) {
                        if (isset($option['main_id'])) {
                            ProductOption::storeDouble($inputs[$group], $product_id);
                        } elseif (isset($option['value'])) {
                            ProductOption::storeSingle($inputs[$group], $product_id);
                        }
                    }
                }


            }

        }

        return $this;
    }


    /**
     * @return bool
     */
    private function isDuplicateSku(): bool
    {
        $exist = $this->where('sku', $this->request->sku)->first();

        if (isset($this->id) && $exist && $exist->id != $this->id) {
            return true;
        }

        return false;
    }


    /**
     * @return bool
     * @throws ValidationException
     */
    private function isDuplicateOptionSku()
    {
        if ( ! empty($this->request->input('options')) && is_array($this->request->input('options'))) {
            $inputs = $this->request->input('options');
            $groups = Options::query()->get()->unique('type')->pluck('type');

            foreach ($groups as $group) {
                $group = Str::slug($group);

                // single options
                if (isset($inputs[$group][0]['value'])) {
                    foreach ($inputs[$group] as $option) {
                        $opt = Options::query()->find(intval($option['value']) ?? 0);

                        if ($opt) {
                            if (ProductHelper::isDuplicateOptionSku($option['sku'], $opt->id)) {
                                return true;
                            }
                        }
                    }
                }

                // double (referenced) options
                if (isset($inputs[$group][0]['main_id'])) {
                    foreach ($inputs[$group] as $option) {
                        $opt = Options::query()->find(intval($option['main_id']) ?? 0);

                        if ($opt && ! empty($option['sub_options'])) {
                            foreach ($option['sub_options'] as $sub_option) {
                                $sub_opt = Options::query()->find(intval($sub_option['id']) ?? 0);

                                if ($sub_opt) {
                                    if (ProductHelper::isDuplicateOptionSku($sub_option['sku'], $sub_opt->id)) {
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

}
