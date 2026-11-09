<?php

namespace App\Http\Controllers\Back\Settings;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Back\Settings\Settings;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('back.settings.settings');
    }


    /**
     * @return array|mixed
     */
    public function get()
    {
        // The cart only needs currency formatting. Payment/shipping settings contain credentials.
        $response = Helper::resolveCache('set')->remember('cart.public-currency.v2', config('cache.life'), function () {
            $setting = Settings::where('code', 'currency')->where('key', 'list')->where('json', true)->first();
            $currencies = $setting ? json_decode($setting->value, true) : [];
            $fields = ['id', 'title', 'code', 'symbol_left', 'symbol_right', 'value', 'decimal_places', 'status', 'main', 'sort_order'];

            return ['currency.list' => collect(is_array($currencies) ? $currencies : [])
                ->filter(function ($currency) { return is_array($currency); })
                ->map(function (array $currency) use ($fields) {
                    return \Illuminate\Support\Arr::only($currency, $fields);
                })->values()->all()];
        });

        return $response;
    }
    
}
