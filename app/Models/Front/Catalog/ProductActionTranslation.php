<?php

namespace App\Models\Front\Catalog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductActionTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_actions_translations';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


}
