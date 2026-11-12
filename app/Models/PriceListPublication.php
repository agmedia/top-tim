<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListPublication extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['publication_date' => 'date', 'published_at' => 'datetime'];

    public function scopePublicArchive($query)
    {
        return $query->where('published_at', '>=', now()->subDays(config('price_lists.archive_days')))
            ->orderByDesc('publication_date');
    }
}
