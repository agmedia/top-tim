<?php

namespace App\Models;

use App\Models\Back\UserGroup;
use App\Models\Front\Catalog\Brand;
use Illuminate\Database\Eloquent\Model;

class CustomerDiscount extends Model
{
    protected $fillable = ['user_id', 'user_group_id', 'applies_to_everyone', 'brand_id', 'brand_ids', 'brand_key', 'min_quantity', 'percentage', 'active'];

    protected $attributes = ['applies_to_everyone' => false];

    protected $casts = ['active' => 'boolean', 'applies_to_everyone' => 'boolean', 'brand_ids' => 'array', 'min_quantity' => 'integer', 'percentage' => 'float'];

    protected static function booted(): void
    {
        static::saving(function (CustomerDiscount $discount) {
            $ids = self::normalizedBrandIds($discount->brand_ids ?: [$discount->brand_id]);
            $discount->brand_ids = $ids;
            $discount->brand_id = $ids[0];
            $discount->brand_key = self::brandSetKey($ids);
        });
    }

    public static function normalizedBrandIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    public static function brandSetKey(array $ids): string
    {
        return hash('sha256', json_encode(self::normalizedBrandIds($ids)));
    }

    public function brandIds(): array
    {
        return self::normalizedBrandIds($this->brand_ids ?: [$this->brand_id]);
    }

    public function appliesToBrand(int $brandId): bool
    {
        return in_array($brandId, $this->brandIds(), true);
    }

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
