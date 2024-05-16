<?php

namespace App\Models\Back\Catalog\Product;

use App\Models\Back\Catalog\Attributes\Attributes;
use App\Models\Back\Catalog\Category;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{

    /**
     * @var string $table
     */
    protected $table = 'product_attribute';

    /**
     * @var array $guarded
     */
    protected $guarded = [];


    /**
     * @param array $attributes
     * @param int   $product_id
     *
     * @return array
     */
    public static function storeData(array $attributes, int $product_id): array
    {
        $created = [];
        self::where('product_id', $product_id)->delete();

        foreach ($attributes as $attribute) {
            $att = Attributes::query()->find($attribute);

            if ($att) {
                $created[] = self::insert([
                    'product_id'  => $product_id,
                    'attribute_id' => $att->id
                ]);
            }
        }

        return $created;
    }
}
