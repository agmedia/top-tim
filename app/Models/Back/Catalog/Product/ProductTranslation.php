<?php

namespace App\Models\Back\Catalog\Product;

use App\Helpers\ProductHelper;
use App\Models\Back\Catalog\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Bouncer;

class ProductTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_translations';

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
            $slug = static::resolveSlug($id, $request, $lang->code);

            $saved = self::insertGetId([
                'product_id'       => $id,
                'lang'             => $lang->code,
                'name'             => trim($request->name[$lang->code]),
                'description'      => ProductHelper::cleanHTML($request->description[$lang->code]),
                //'podaci'           => ProductHelper::cleanHTML($request->podaci[$lang->code]),
                //'sastojci'         => ProductHelper::cleanHTML($request->sastojci[$lang->code]),
                'meta_title'       => $request->meta_title[$lang->code],
                'meta_description' => $request->meta_description[$lang->code],
                'slug'             => $slug,
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now()
            ]);

            if ( ! $saved) {
                return false;
            }

            self::query()->where('id', $saved)->update([
                'url' => ProductHelper::url(Product::query()->where('id', $id)->first())
            ]);
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
            $slug = static::resolveSlug($id, $request, $lang->code, 'update');

            $saved = self::where('product_id', $id)->where('lang', $lang->code)->update([
                'name'             => trim($request->name[$lang->code]),
                'description'      => ProductHelper::cleanHTML($request->description[$lang->code]),
                //'podaci'           => ProductHelper::cleanHTML($request->podaci[$lang->code]),
                //'sastojci'         => ProductHelper::cleanHTML($request->sastojci[$lang->code]),
                'meta_title'       => $request->meta_title[$lang->code],
                'meta_description' => $request->meta_description[$lang->code],
                'slug'             => $slug,
                'updated_at'       => Carbon::now()
            ]);

            if ( ! $saved) {
                return false;
            }

            self::query()->where('product_id', $id)->where('lang', $lang->code)->update([
                'url' => ProductHelper::url(Product::query()->where('id', $id)->first())
            ]);
        }

        return true;
    }


    /**
     * @param string       $target
     * @param Request|null $request
     *
     * @return string
     */
    public static function resolveSlug(int $id, Request $request, string $lang, string $target = 'insert'): string
    {
        $slug = isset($request->slug[$lang]) ? trim(Str::slug($request->slug[$lang])) : trim(Str::slug($request->name[$lang]));

        if ($target == 'update') {
            $product = Product::query()->where('id', $id)->first();

            if ($product) {
                $slug = $product->translation->slug;
            }
        }

        $exist = Product::query()->whereHas('translation', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->count();

        $cat_exist = Category::query()->whereHas('translation', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->count();

        if (($cat_exist || $exist > 1) && $target == 'update') {
            return $slug . '-' . time();
        }

        if (($cat_exist || $exist) && $target == 'insert') {
            return $slug . '-' . time();
        }

        return $slug;
    }

}
