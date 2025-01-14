<?php

/**
 *
 */

use App\Helpers\LanguageHelper;
use Illuminate\Support\Facades\Log;

if ( ! function_exists('group')) {
    /**
     * Function that returns category group based on
     * settings.php "group_path" key value. Returns it as is or
     * as a slug if the $slug parameter is true.
     *
     * @param bool $slug
     *
     * @return string
     */
    function group(bool $slug = false): string
    {
        if ($slug) {
            return \Illuminate\Support\Str::slug(config('settings.group_path'));
        }

        return config('settings.group_path');
    }
}

/**
 *
 */
if ( ! function_exists('run_query')) {
    /**
     *
     * @param bool $slug
     *
     * @return string
     */
    function run_query(string $query = null)
    {
        if ($query) {
            return \Illuminate\Support\Facades\DB::statement(
                \Illuminate\Support\Facades\DB::raw($query)
            );
        }

        return false;
    }
}

/**
 *
 */
if ( ! function_exists('logiraj_vrijeme')) {
    /**
     *
     * @param bool $slug
     *
     * @return string
     */
    function logiraj_vrijeme($code, string $log_text = '')
    {
        $log_start1 = microtime(true);

        $code();

        $log_end1 = microtime(true);
        $sec1 = number_format(($log_end1 - $log_start1), 2, ',', '.');

    }
}

if ( ! function_exists('ag_lang')) {
    /**
     * @param bool $main
     *
     * @return mixed
     */
    function ag_lang(bool $main = false)
    {
        if ($main) {
            return LanguageHelper::getMain();
        }

        return LanguageHelper::list();
    }
}

/**
 *
 */
if ( ! function_exists('current_locale')) {
    /**
     * @param bool $native
     *
     * @return string
     */
    function current_locale(bool $native = false): string
    {
        $current = LanguageHelper::getCurrentLocale();

        if ($native) {
            return config('laravellocalization.supportedLocales.' . $current . '.regional');
        }

        return $current;
    }
}
