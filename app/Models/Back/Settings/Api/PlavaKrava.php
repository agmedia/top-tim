<?php

namespace App\Models\Back\Settings\Api;

use App\Helpers\ApiHelper;
use App\Helpers\Helper;
use App\Helpers\Import;
use App\Helpers\ProductHelper;
use App\Helpers\Query;
use App\Models\Back\Catalog\Attributes\Attributes;
use App\Models\Back\Catalog\Attributes\AttributesTranslation;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductAttribute;
use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Back\Catalog\Product\ProductTranslation;
use App\Models\Back\Settings\Settings;
use App\Models\Back\TempTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlavaKrava
{

    /**
     * @var array|null
     */
    protected $request;


    /**
     * @param Request $request
     *
     * @return false|string
     */
    public function upload(Request $request)
    {
        $saved = Storage::disk('assets')->putFileAs('xls/', $request->file('file'), 'toptim-import.xlsx');

        if ($saved) {
            return public_path('assets/xls/toptim-import.xlsx');
        }

        return false;
    }


    /**
     * @param array $request
     *
     * @return false|\Illuminate\Http\JsonResponse|int|string
     */
    public function process(array $request, array $data = null)
    {
        if ($request) {
            $this->request = $request;

            switch ($this->request['method']) {
                case 'upload-excel':
                    return $this->importNewProducts($data);
            }
        }

        return false;
    }


    /**
     * @return int
     */
    private function importNewProducts(array $data = null)
    {
        $count = 0;

        foreach ($data as $key => $item) {
            if ($key > 0) {
                $exist = Product::query()->where('sku', $item[2])->first();

                if ( ! $exist && ! empty($item[2])) {
                    $import       = new Import();
                    $name = str_replace('/', '-', $item[0]);
                    $brand_id = 11;

                    $id = Product::query()->insertGetId([
                        'action_id'            => 0,
                        'brand_id'             => $brand_id,
                        'sku'                  => $item[2],
                        'price'                => $item[8],
                        'quantity'             => $item[9] ?: 0,
                        'decrease'             => 1,
                        'tax_id'               => config('settings.default_tax_id'),
                        'special'              => null,
                        'special_from'         => null,
                        'special_to'           => null,
                        'sort_order'           => 0,
                        'push'                 => 0,
                        'status'               => 1,
                        'created_at'           => Carbon::now(),
                        'updated_at'           => Carbon::now(),
                        'sizeguide_id'         => 18,
                    ]);

                    if ($id) {

                        foreach (ag_lang() as $lang) {
                            $slug = ProductTranslation::resolveSlug($id, new Request(['slug' => [$lang->code => Str::slug($name)]]), $lang->code);

                            if($item[21] != ''){
                                $description = '<p>'.$item[4] .'</p><p>'.$item[21].'</p>';
                            }else{
                                $description = '<p>'.$item[4] .'</p>';
                            }

                            ProductTranslation::query()->insertGetId([
                                'product_id'       => $id,
                                'lang'             => $lang->code,
                                'name'             => $name,
                                'description'      => $description,
                                'meta_title'       => $item[11],
                                'meta_description' => $item[12],
                                'slug'             => $slug,
                                'url'              => '',
                                'created_at'       => Carbon::now(),
                                'updated_at'       => Carbon::now()
                            ]);
                        }


                        // Materijal
                        if($item[14] != ''){

                        $exist = AttributesTranslation::query()->where('group_title', 'Materijal')->where('title', $item[14])->first();

                            if ($exist) {
                                ProductAttribute::query()->insertGetId([
                                    'product_id'       => $id,
                                    'attribute_id'     => $exist->id,
                                ]);
                            } else {
                                $atr_id = Attributes::query()->insertGetId([
                                    'group'       => Str::slug('Materijal'),
                                    'type'        => 'text',
                                    'sort_order'  => 0,
                                    'status'      => 1,
                                    'created_at'  => Carbon::now(),
                                    'updated_at'  => Carbon::now()
                                ]);

                                if ($atr_id) {
                                    AttributesTranslation::insertGetId([
                                        'attribute_id' => $atr_id,
                                        'lang'         => 'hr',
                                        'group_title'  => 'Materijal',
                                        'title'        => $item[14],
                                        'created_at'   => Carbon::now(),
                                        'updated_at'   => Carbon::now()
                                    ]);

                                    ProductAttribute::query()->insertGetId([
                                        'product_id'       => $id,
                                        'attribute_id'     => $atr_id,
                                    ]);
                                }
                            }

                        }
                        // End Materijal



                        // Kroj
                        if($item[17] != ''){

                            $exist = AttributesTranslation::query()->where('group_title', 'Kroj')->where('title', $item[17])->first();

                            if ($exist) {
                                ProductAttribute::query()->insertGetId([
                                    'product_id'       => $id,
                                    'attribute_id'     => $exist->id,
                                ]);
                            } else {
                                $atr_id = Attributes::query()->insertGetId([
                                    'group'       => Str::slug('Kroj'),
                                    'type'        => 'text',
                                    'sort_order'  => 0,
                                    'status'      => 1,
                                    'created_at'  => Carbon::now(),
                                    'updated_at'  => Carbon::now()
                                ]);

                                if ($atr_id) {
                                    AttributesTranslation::insertGetId([
                                        'attribute_id' => $atr_id,
                                        'lang'         => 'hr',
                                        'group_title'  => 'Kroj',
                                        'title'        => $item[17],
                                        'created_at'   => Carbon::now(),
                                        'updated_at'   => Carbon::now()
                                    ]);

                                    ProductAttribute::query()->insertGetId([
                                        'product_id'       => $id,
                                        'attribute_id'     => $atr_id,
                                    ]);
                                }
                            }

                        }
                        // End Kroj


                        // Dimenzije
                        if($item[18] != ''){

                            $exist = AttributesTranslation::query()->where('group_title', 'Dimenzije')->where('title', $item[18])->first();

                            if ($exist) {
                                ProductAttribute::query()->insertGetId([
                                    'product_id'       => $id,
                                    'attribute_id'     => $exist->id,
                                ]);
                            } else {
                                $atr_id = Attributes::query()->insertGetId([
                                    'group'       => Str::slug('Dimenzije'),
                                    'type'        => 'text',
                                    'sort_order'  => 0,
                                    'status'      => 1,
                                    'created_at'  => Carbon::now(),
                                    'updated_at'  => Carbon::now()
                                ]);

                                if ($atr_id) {
                                    AttributesTranslation::insertGetId([
                                        'attribute_id' => $atr_id,
                                        'lang'         => 'hr',
                                        'group_title'  => 'Dimenzije',
                                        'title'        => $item[18],
                                        'created_at'   => Carbon::now(),
                                        'updated_at'   => Carbon::now()
                                    ]);

                                    ProductAttribute::query()->insertGetId([
                                        'product_id'       => $id,
                                        'attribute_id'     => $atr_id,
                                    ]);
                                }
                            }

                        }
                        // End Kroj



                        $images = explode(', ',$item[7]);

                        $len = count($images);

                        $image = config('settings.image_default');

                        $item[6] = str_replace('\\', '/', $item[6]);

                        foreach($images as $index => $img){
                            if ($index == 0) {
                                try {

                                    $image_path = public_path('/media/img/products/Zeus/' . $item[6] . '/' . $name . '/' . $img);
                                    $image = $import->resolveImages($image_path, $item[2], $id);
                                    $import->saveImageToDB($id, $image, $img, 1);
                                } catch (\ErrorException $e) {
                                    Log::info('Image not imported. Product SKU: (' . $item[2] . ') - ' . $img);
                                    Log::info($e->getMessage());
                                }
                            } else{


                                $image_path = public_path('/media/img/products/Zeus/' . $item[6] . '/' . $name . '/' . $img);
                                $image = $import->resolveImages($image_path, $item[2], $id);

                                     $import->saveImageToDB($id, $image, $img);



                            }

                        }

                        //  ProductCategory::storeData($categories, $id);

                        // categories
                        $categories = $import->resolveStringCategories($item[6]);

                        foreach ($categories as $category) {
                            ProductCategory::insert([
                                'product_id'  => $id,
                                'category_id' => $category,
                            ]);
                        }

                        $product = Product::query()->find($id);

                        $product->update([
                            'image'           => $image,
                            'url'             => ProductHelper::url($product),
                            'category_string' => ProductHelper::categoryString($product)
                        ]);
                        // end categories


                        // attr
                        $attributes = explode('\\', $item[6]);
                        $group = 'Dodatna kategorizacija';

                        if (isset($attributes[2])) {
                            $title = $attributes[2];

                            if (isset($attributes[3])) {
                                $title = $attributes[2] . ' i ' . $attributes[3];
                            }

                            //
                            $exist = AttributesTranslation::query()->where('group_title', $group)->where('title', $title)->first();

                            if ($exist) {
                                ProductAttribute::query()->insertGetId([
                                    'product_id'       => $id,
                                    'attribute_id'     => $exist->id,
                                ]);
                            } else {
                                $atr_id = Attributes::query()->insertGetId([
                                    'group'       => Str::slug($group),
                                    'type'        => 'text',
                                    'sort_order'  => 0,
                                    'status'      => 1,
                                    'created_at'  => Carbon::now(),
                                    'updated_at'  => Carbon::now()
                                ]);

                                if ($atr_id) {
                                    AttributesTranslation::insertGetId([
                                        'attribute_id' => $atr_id,
                                        'lang'         => 'hr',
                                        'group_title'  => $group,
                                        'title'        => $title,
                                        'created_at'   => Carbon::now(),
                                        'updated_at'   => Carbon::now()
                                    ]);

                                    ProductAttribute::query()->insertGetId([
                                        'product_id'       => $id,
                                        'attribute_id'     => $atr_id,
                                    ]);
                                }
                            }
                        }

                        $count++;
                    }
                }
            }
        }

        return ApiHelper::response(1, 'Importano je ' . $count . ' novih artikala u bazu.');
    }

}
