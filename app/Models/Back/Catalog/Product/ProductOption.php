<?php

namespace App\Models\Back\Catalog\Product;

use App\Helpers\ProductHelper;
use App\Models\Back\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductImageTranslation;
use Carbon\Carbon;
use App\Helpers\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductOption extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_option';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var string[]
     */
    protected $appends = ['title'];

    /**
     * @var string
     */
    protected $locale = 'en';

    /**
     * @var Model
     */
    protected $resource;


    /**
     * apartment constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->locale = current_locale();
    }


    /**
     * @param null $lang
     *
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function translation($lang = null)
    {
        if ($lang) {
            return $this->hasOne(ProductImageTranslation::class, 'product_image_id')->where('lang', $lang)->first();
        }

        return $this->hasOne(ProductImageTranslation::class, 'product_image_id')->where('lang', $this->locale)->first();
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany(ProductImageTranslation::class, 'product_image_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function option()
    {
        return $this->hasOne(Options::class, 'id', 'option_id');
    }


    public function top()
    {
        return $this->hasOne(Options::class, 'id', 'parent_id');
    }


    /**
     * @return string
     */
    public function getTitleAttribute()
    {
        //dd($this->translation()->toArray());
        return $this->option()->first() ?: '';
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param array $options
     * @param int   $product_id
     *
     * @return int
     */
    public static function storeSingle(array $options, int $product_id): int
    {
        self::query()->where('product_id', $product_id)->delete();
        $product = Product::query()->find($product_id);
        $quantity = 0;

        foreach ($options as $option) {
            $opt = Options::query()->find(intval($option['value']) ?? 0);

            if ($opt) {
                $qty = $option['qty'] ?? 0;

                $created = self::insert([
                    'product_id'  => $product_id,
                    'option_id' => $opt->id,
                    'sku' => $product->sku . '-' . $opt->option_sku,
                    'parent' => 'single',
                    'parent_id' => 0,
                    'quantity' => $qty,
                    'price' => str_replace(',', '.', $option['price']) ?? 0,
                    'data' => '[]',
                    'status' => 1,
                ]);

                if ($created) {
                    $quantity += $qty;
                }
            }
        }

        return $quantity;
    }


    /**
     * @param array $options
     * @param int   $product_id
     *
     * @return int
     */
    public static function storeDouble(array $options, int $product_id): int
    {
        $quantity = 0;
        self::query()->where('product_id', $product_id)->delete();
        $product = Product::query()->find($product_id);

        foreach ($options as $option) {
            $opt = Options::query()->find(intval($option['main_id']) ?? 0);

            if ($opt && ! empty($option['sub_options'])) {
                foreach ($option['sub_options'] as $sub_option) {
                    $sub_opt = Options::query()->find(intval($sub_option['id']) ?? 0);

                    if ($sub_opt) {
                        $qty = $sub_option['qty'] ?? 0;

                        $created = self::insert([
                            'product_id'  => $product_id,
                            'option_id' => $sub_opt->id,
                            'sku' => $product->sku . '-' . $opt->option_sku . '-' . $sub_opt->option_sku,
                            'parent' => 'option',
                            'parent_id' => $opt->id,
                            'quantity' => $qty,
                            'price' => $sub_option['price'] ?? 0,
                            'data' => '[]',
                            'status' => 1,
                        ]);

                        if ($created) {
                            $quantity += $qty;
                        }
                    }
                }
            }
        }

        return $quantity;
    }


    /**
     * @param int $product_id
     *
     * @return mixed
     */
    public static function deleteAll(int $product_id): mixed
    {
        return self::query()->where('product_id', $product_id)->delete();
    }


    /**
     * @param Apartment|null $apartment
     *
     * @return array
     */
    public static function getExistingOptions(Product $product = null): array
    {
        if ( ! $product || empty($product)) {
            return [];
        }

        $response = [];
        $options  = $product->options()->get();



        foreach ($options as $image) {
            $response[$image->id] = [
                'id'         => $image->id,
                'sku'      => $image->image,
                'group'      => $image->image,
                'title'      => $image->image,
                'value'    => $image->default,
                'quantity'  => $image->published,
                'price' => $image->sort_order,
            ];

            foreach (ag_lang() as $lang) {
                $title = isset($image->translation($lang->code)->title) ? $image->translation($lang->code)->title : '';
                $alt   = isset($image->translation($lang->code)->alt) ? $image->translation($lang->code)->alt : '';

                $response[$image->id]['title'][$lang->code] = $title;
                $response[$image->id]['alt'][$lang->code]   = $alt;
            }
        }

        //dd($response);

        return $response;
    }


    public static function getList()
    {
        $response = [];
        $values = Attributes::query()->get();

        foreach ($values as $value) {
            $response[$value->group]['group'] = $value->translation->group_title;
            $response[$value->group]['items'][] = [
                'id' => $value->id,
                'title' => $value->translation->title,
                'sort_order' => $value->sort_order
            ];
        }

        return $response;
    }

}
