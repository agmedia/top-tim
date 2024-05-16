<?php

namespace App\Models\Front\Catalog;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttributesTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'attributes_translations';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];



}
