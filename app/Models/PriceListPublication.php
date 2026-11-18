<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListPublication extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'publication_date', 'published_at', 'filename', 'path', 'sha256', 'product_count',
        'missing_barcodes', 'revision', 'content_hash', 'replaced_at',
    ];
    protected $casts = ['publication_date' => 'date', 'published_at' => 'datetime'];

    public function scopePublicArchive($query)
    {
        return $query->where(function ($versions) {
            $versions->whereNull('replaced_at')->orWhere('replaced_at', '>=', now()->subDays(config('price_lists.archive_days')));
        })->orderByDesc('id');
    }
}
