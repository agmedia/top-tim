<?php

namespace App\Models;

use App\Models\Back\UserGroup;
use App\Models\Front\Catalog\Brand;
use Illuminate\Database\Eloquent\Model;

class CustomerDiscount extends Model
{
    protected $fillable = ['user_id', 'user_group_id', 'brand_id', 'min_quantity', 'percentage', 'active'];

    protected $casts = ['active' => 'boolean', 'min_quantity' => 'integer', 'percentage' => 'float'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
