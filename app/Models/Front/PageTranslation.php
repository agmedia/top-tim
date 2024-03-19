<?php

namespace App\Models\Front;

use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'page_translations';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

}
