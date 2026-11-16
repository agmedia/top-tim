<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CategoryNavigation
{
    public static function version(): string
    {
        return (string) Cache::get('category_navigation.version', '1');
    }

    public static function invalidate(): void
    {
        Cache::forever('category_navigation.version', (string) \Illuminate\Support\Str::uuid());
    }
}
