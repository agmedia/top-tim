<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnchorPrice extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['price' => 'decimal:4', 'reference_date' => 'date', 'variant_prices' => 'array'];
}
