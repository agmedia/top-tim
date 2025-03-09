<?php

namespace App\Models\Back\Catalog\Attributes;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AttributesTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'attributes_translations';

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
                'attribute_id' => $id,
                'lang'         => $lang->code,
                'group_title'  => $request->input('title')[$lang->code],
                'title'        => $item['title'][$lang->code],
                'created_at'   => Carbon::now(),
                'updated_at'   => Carbon::now()
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
            $saved = self::where('attribute_id', $id)->where('lang', $lang->code)->update([
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


    /**
     * @param int    $id
     * @param string $title
     *
     * @return bool
     */
    public static function createFast(int $id, string $title, string $group_title): bool
    {
        foreach (ag_lang() as $lang) {
            $saved = self::insertGetId([
                'attribute_id' => $id,
                'lang'         => $lang->code,
                'group_title'  => $group_title,
                'title'        => $title,
                'created_at'   => Carbon::now(),
                'updated_at'   => Carbon::now()
            ]);

            if ( ! $saved) {
                return false;
            }
        }

        return true;
    }
}
