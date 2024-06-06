<?php

namespace App\Models\Front\Catalog;

use App\Models\Front\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Attributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
     * @var Model
     */
    protected $resource;


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function option()
    {
        return $this->hasOne(Options::class, 'id', 'option_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function top()
    {
        return $this->hasOne(Options::class, 'id', 'parent_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }


    /**
     * @return string
     */
    public function getTitleAttribute()
    {
        return $this->option()->first() ?: '';
    }





    public static function getList()
    {
        $response = [];
        $values = Options::query()->get();

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
