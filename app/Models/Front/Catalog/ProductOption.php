<?php

namespace App\Models\Front\Catalog;

use App\Models\Back\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Attributes;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductImageTranslation;
use Carbon\Carbon;
use App\Helpers\Image;
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
     * @return string
     */
    public function getTitleAttribute()
    {
        return $this->option()->first() ?: '';
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