<?php

namespace App\Models\Back\Catalog\Product;

use App\Models\Back\Catalog\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProductCategory extends Model
{

    /**
     * @var string $table
     */
    protected $table = 'product_category';

    /**
     * @var array $guarded
     */
    protected $guarded = [];


    /**
     * Update Product categories.
     *
     * @param array $categories
     * @param int   $product_id
     *
     * @return array
     */
    public static function storeData(array $categories, int $product_id): array
    {
        $created = [];
        self::where('product_id', $product_id)->delete();

        foreach ($categories as $category) {
            $cat = Category::find($category);

            if ($cat) {
                if ($cat->parent_id) {
                    $created[] = self::insert([
                        'product_id'  => $product_id,
                        'category_id' => $cat->parent_id
                    ]);
                }

                $created[] = self::insert([
                    'product_id'  => $product_id,
                    'category_id' => $category
                ]);
            }
        }

        return $created;
    }


    /**
     * @param Request $request
     * @param int     $category_id
     *
     * @return void
     */
    public static function checkProductTransfer(Request $request, int $category_id): void
    {
        $category = Category::query()->find($category_id);

        if ($category->parent_id != $request->input('parent')) {
            self::query()->where('category_id', $category->parent_id)->delete();
        }
    }
}
