<?php

namespace App\Models\Back\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PageTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'page_translations';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    /**
     * @param int     $id
     * @param Request $request
     *
     * @return bool
     */
    public static function create(int $id, Request $request): bool
    {
        foreach (ag_lang() as $lang) {
            $slug = isset($request->slug[$lang->code]) ? Str::slug($request->slug[$lang->code]) : Str::slug($request->title[$lang->code]);

            $saved = self::insertGetId([
                'page_id'           => $id,
                'lang'              => $lang->code,
                'title'             => $request->title[$lang->code],
                'short_description' => '',
                'description'       => $request->description[$lang->code],
                'meta_title'        => $request->meta_title[$lang->code],
                'meta_description'  => $request->meta_description[$lang->code],
                'slug'              => $slug,
                'keywords'          => null,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now()
            ]);
            if ( ! $saved) {
                return false;
            }
        }

        return true;
    }


    /**
     * @param int     $id
     * @param Request $request
     *
     * @return bool
     */
    public static function edit(int $id, Request $request): bool
    {
        foreach (ag_lang() as $lang) {
            $slug = isset($request->slug[$lang->code]) ? Str::slug($request->slug[$lang->code]) : Str::slug($request->title[$lang->code]);

            $saved = self::where('page_id', $id)->where('lang', $lang->code)->update([
                'title'             => $request->title[$lang->code],
                'short_description' => '',
                'description'       => $request->description[$lang->code],
                'meta_title'        => $request->meta_title[$lang->code],
                'meta_description'  => $request->meta_description[$lang->code],
                'slug'              => $slug,
                'keywords'          => null,
                'updated_at'        => Carbon::now()
            ]);
            if ( ! $saved) {
                return false;
            }
        }

        return true;
    }
}
