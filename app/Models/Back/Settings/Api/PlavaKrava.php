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
                            $slug = ProductTranslation::resolveSlug($id, new Request(['slug' => [$lang->code => Str::slug($item[0])]]), $lang->code);

                            ProductTranslation::query()->insertGetId([
                                'product_id'       => $id,
                                'lang'             => $lang->code,
                                'name'             => $item[0],
                                'description'      => $item[4],
                                'meta_title'       => $item[11],
                                'meta_description' => $item[12],
                                'slug'             => $slug,
                                'url'              => '',
                                'created_at'       => Carbon::now(),
                                'updated_at'       => Carbon::now()
                            ]);
                        }


                        // Materijal
                        $exist = AttributesTranslation::query()->where(['group_title', 'Materijal'])->where('title', $item[14])->first();

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
                                'inserted_at'  => Carbon::now(),
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
                        // End Materijal



                        $images = explode(', ',$item[7]);

                        $len = count($images);

                        $image = config('settings.image_default');


                        foreach($images as $index => $img){
                            if ($index == 0) {
                                try {

                                    $image_path = public_path('/media/img/products/Zeus/' . $item[6] . '/' . $item[0] . '/' . $img);
                                    $image = $import->resolveImages($image_path, $item[2], $id);
                                    $import->saveImageToDB($id, $image, $img, 1);
                                } catch (\ErrorException $e) {
                                    Log::info('Image not imported. Product SKU: (' . $item[2] . ') - ' . $img);
                                    Log::info($e->getMessage());
                                }
                            } else{


                                $image_path = public_path('/media/img/products/Zeus/' . $item[6] . '/' . $item[0] . '/' . $img);
                                $image = $import->resolveImages($image_path, $item[2], $id);

                                     $import->saveImageToDB($id, $image, $img);



                            }

                        }

                      //  $categories = $import->resolveStringCategories($item[6]);

                      //  ProductCategory::storeData($categories, $id);


                        // categories
                        ProductCategory::insert([
                            'product_id'  => $id,
                            'category_id' => 39,
                        ]);

                        ProductCategory::query()->insert([
                            'product_id'  => $id,
                            'category_id' => 100,
                        ]);

                        $product = Product::query()->find($id);

                        $product->update([
                            'image'           => $image,
                            'url'             => ProductHelper::url($product),
                            'category_string' => ProductHelper::categoryString($product)
                        ]);

                        $count++;
                    }
                }
            }
        }

        return ApiHelper::response(1, 'Importano je ' . $count . ' novih artikala u bazu.');
    }

}
