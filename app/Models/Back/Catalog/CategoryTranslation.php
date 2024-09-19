<?php

namespace App\Models\Back\Catalog;

use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CategoryTranslation extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'category_translations';
    
    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }


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
                'category_id'      => $id,
                'lang'             => $lang->code,
                'title'            => $request->title[$lang->code],
                'description'      => $request->description[$lang->code],
                'meta_title'       => $request->meta_title[$lang->code],
                'meta_description' => $request->meta_description[$lang->code],
                'slug'             => Str::slug($request->title[$lang->code]),
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now()
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
            $saved = self::where('category_id', $id)->where('lang', $lang->code)->update([
                'title'            => $request->title[$lang->code],
                'description'      => $request->description[$lang->code],
                'meta_title'       => $request->meta_title[$lang->code],
                'meta_description' => $request->meta_description[$lang->code],
                'slug'             => Str::slug($request->title[$lang->code]),
                'updated_at'       => Carbon::now()
            ]);
            if ( ! $saved) {
                return false;
            }
        }
        
        return true;
    }
    
}
