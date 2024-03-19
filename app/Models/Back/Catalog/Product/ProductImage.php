<?php

namespace App\Models\Back\Catalog\Product;

use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductImageTranslation;
use Carbon\Carbon;
use App\Helpers\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProductImage extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_images';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var string[]
     */
    protected $appends = ['title', 'alt'];

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
     * @return string
     */
    public function getTitleAttribute()
    {
        //dd($this->translation()->toArray());
        return (isset($this->translation()->title) && $this->translation()->title) ? $this->translation()->title : '';
    }


    /**
     * @return string
     */
    public function getAltAttribute()
    {
        return (isset($this->translation()->alt) && $this->translation()->alt) ? $this->translation()->alt : '';
    }


    /**
     * @param Model $resource
     */
    public function setResource(Model $resource): void
    {
        $this->resource = $resource;
    }


    /**
     * @param $resource
     * @param $request
     *
     * @return mixed
     */
    public function store($resource, $request)
    {
        $this->resource = $resource;
        $existing       = isset($request['slim']) ? $request['slim'] : null;
        $new            = isset($request['files']) ? $request['files'] : null;

        // Ako ima novih slika
        if ($new) {
            foreach ($new as $new_image) {
                if (isset($new_image['image']) && $new_image['image']) {
                    $this->saveNew($new_image, $request['name']);
                }
            }
        }

        // Ako ima postoječih slika
        if ($existing) {
            foreach ($existing as $key => $image) {
                // Ako slika nije editirana
                $this->updateImageData($key, $image);

                if ($image['image']) {
                    $this->replace($key, $image);
                }
            }
        }

        return true;
    }


    /**
     * @param array $new_image
     *
     * @return bool
     */
    public function saveNew(array $new_image, array $title): bool
    {
        $path = Image::save('products', $new_image, $this->resource);

        $id = $this->insertGetId([
            'product_id'   => $this->resource->id,
            'image'        => config('filesystems.disks.products.url') . $path,
            'default'      => (isset($new_image['default']) && $new_image['default']) ? 1 : 0,
            'published'    => 1,
            'sort_order'   => (isset($new_image['sort_order']) && $new_image['sort_order']) ? $new_image['sort_order'] : 0,
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now()
        ]);

        $this->isDefaultImage($new_image, $path);

        if ($id) {
            ProductImageTranslation::create($id, $title);

            return true;
        }

        return false;
    }


    /**
     * @param int   $id
     * @param array $image_data
     *
     * @return bool
     */
    public function updateImageData(int $id, array $image_data): bool
    {
        $updated = $this->where('id', $id)->update([
            'default'    => (isset($image_data['default']) && $image_data['default']) ? 1 : 0,
            'published'  => (isset($image_data['published']) and $image_data['published'] == 'on') ? 1 : 0,
            'sort_order' => (isset($image_data['sort_order']) && $image_data['sort_order']) ? $image_data['sort_order'] : 0,
            'updated_at' => Carbon::now()
        ]);

        if ($this->where('id', $id)->first()) {
            $path = str_replace(config('filesystems.disks.products.url'), '', $this->where('id', $id)->first()->image);

            $this->isDefaultImage($image_data, $path);
        }

        if ($updated) {
            ProductImageTranslation::edit($id, $image_data);

            return true;
        }

        return false;
    }


    /**
     * @param $id
     * @param $new
     *
     * @return mixed
     */
    public function replace(int $id, array $image)
    {
        // Nađi staru sliku, izdvoji naziv
        $old  = $this->where('id', $id)->first();
        $path = Image::cleanPath('products', $this->resource->id, $old->image);

        // Obriši staru i snimi novu fotku
        Image::delete('product', $this->resource->id, $path);
        $new_path = Image::save('products', $image, $this->resource);

        $updated = $this->where('id', $id)->update([
            'image' => config('filesystems.disks.products.url') . $new_path
        ]);

        $this->isDefaultImage($image, $new_path);

        if ( ! $updated) {
            return false;
        }

        return true;
    }


    /**
     * @param array  $image
     * @param string $path
     *
     * @return void
     */
    public function isDefaultImage(array $image, string $path)
    {
        if (isset($image['default']) && $image['default']) {
            return Product::where('id', $this->resource->id)->update([
                'image' => config('filesystems.disks.products.url') . $path
            ]);
        }
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param Apartment|null $apartment
     *
     * @return array
     */
    public static function getExistingImages(Product $product = null): array
    {
        if ( ! $product || empty($product)) {
            return [];
        }

        $response = [];
        $images   = $product->images()->get();

        foreach ($images as $image) {
            $response[$image->id] = [
                'id'         => $image->id,
                'image'      => $image->image,
                'default'    => $image->default,
                'published'  => $image->published,
                'sort_order' => $image->sort_order,
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

}
