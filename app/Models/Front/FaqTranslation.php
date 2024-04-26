<?php

namespace App\Models\Front;

use Illuminate\Database\Eloquent\Model;

class FaqTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'faq_translations';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

}
