<?php

namespace App\Models\Back\Catalog\Attributes;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
    public static function create(int $id, Request $request): bool
    {
        foreach (ag_lang() as $lang) {
            $saved = self::insertGetId([
                'attribute_id'      => $id,
                'lang'        => $lang->code,
                'title'       => $request->title[$lang->code],
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
    public static function edit(int $id, Request $request): bool
    {

        foreach (ag_lang() as $lang) {
            $saved = self::where('attribute_id', $id)->where('lang', $lang->code)->update([
                'title'       => $request->title[$lang->code],
                'updated_at'  => Carbon::now()
            ]);

            if ( ! $saved) {
                return false;
            }
        }

        return true;
    }
}
