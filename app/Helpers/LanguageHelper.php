<?php

namespace App\Helpers;


use App\Models\Back\Settings\Settings;
use Illuminate\Support\Facades\Cache;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class LanguageHelper
{

    /**
     * @return mixed
     */
    public static function list()
    {
        return Cache::rememberForever('lang_list', function () {
            return Settings::get('language', 'list')->where('status', true)->sortBy('sort_order');
        });
    }


    /**
     * @return false|\Illuminate\Support\Collection
     */
    public static function adminList()
    {
        return Settings::get('language', 'list')->sortBy('sort_order');
    }


    /**
     * @return mixed
     */
    public static function getMain()
    {
        return Cache::rememberForever('lang_' . LaravelLocalization::getCurrentLocale(), function () {
            return Settings::get('language', 'list')
                           ->where('status', true)
                           ->where('code', LaravelLocalization::getCurrentLocale())
                           ->first();
        });
    }


    /**
     * @return string
     */
    public static function getCurrentLocale()
    {
        return LaravelLocalization::getCurrentLocale();
    }


    /**
     * @param $model
     * @param $prefix
     *
     * @return array
     */
    public static function resolveSelector($model, $url_prefix = ''): array
    {
        $langs = [];

        foreach (ag_lang() as $lang) {
            $langs[$lang->code] = [
                'code' => $lang->code,
                'slug' => $url_prefix . $model->translation($lang->code)->slug,
                'title' => $lang->title->{current_locale()}
            ];
        }

        return $langs;
    }


}
