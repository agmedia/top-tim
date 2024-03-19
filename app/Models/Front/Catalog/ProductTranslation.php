<?php

namespace App\Models\Front\Catalog;

use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class ProductTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_translations';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


}
