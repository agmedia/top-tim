<?php

namespace App\Models\Back\Catalog\Options;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class OptionsTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'options_translations';

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
    public static function create(int $id, Request $request, array $item): bool
    {
        foreach (ag_lang() as $lang) {
            $saved = self::insertGetId([
                'option_id'   => $id,
                'lang'        => $lang->code,
                'group_title' => $request->input('title')[$lang->code],
                'title'       => $item['title'][$lang->code],
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now()
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
    public static function edit(int $id, Request $request, array $item): bool
    {
        foreach (ag_lang() as $lang) {
            $saved = self::where('option_id', $id)->where('lang', $lang->code)->update([
                'group_title' => $request->input('title')[$lang->code],
                'title'       => $item['title'][$lang->code],
                'updated_at'  => Carbon::now()
            ]);

            if ( ! $saved) {
                return false;
            }
        }

        return true;
    }
}
