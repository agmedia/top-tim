<?php

namespace App\Services;

use App\Helpers\FilterColors;
use App\Models\Front\Catalog\Options\Options;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CatalogImages
{
    /** Use the selected color's published image only for this catalog response. */
    public static function apply(Collection $products, string|array|null $selection): void
    {
        // Retain the legacy option-array format accepted by Product::filter().
        $selected = is_array($selection)
            ? array_map(fn ($item, $key) => $item->id ?? $key, $selection, array_keys($selection))
            : explode('+', $selection ?? '');
        $selected = array_values(array_filter($selected, fn ($id) => (string) $id !== ''));
        if ($products->isEmpty() || !$selected) {
            return;
        }

        $options = Options::active()->where('type', 'color')->get(['id', 'value', 'value_opt']);
        $groups = collect(FilterColors::group($options))->keyBy('id');
        $colorIds = $options->pluck('id')->all();
        $priorities = [];
        foreach ($selected as $id) {
            if (isset($groups[$id])) {
                $priorities[] = $groups[$id]['option_ids'];
            } elseif (ctype_digit((string) $id) && in_array((int) $id, $colorIds, true)) {
                $priorities[] = [(int) $id];
            }
        }
        if (!$priorities) {
            return;
        }

        // One image query for the visible page, regardless of its product count.
        $images = DB::table('product_images')
            ->whereIn('product_id', $products->modelKeys())
            ->whereIn('option_id', collect($priorities)->flatten()->unique()->all())
            ->where('published', 1)
            ->whereNotNull('image')->where('image', '!=', '')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('product_option')
                    ->whereColumn('product_option.product_id', 'product_images.product_id')
                    ->where(function ($query) {
                        $query->whereColumn('product_option.option_id', 'product_images.option_id')
                            ->orWhereColumn('product_option.parent_id', 'product_images.option_id');
                    });
            })
            ->orderBy('sort_order')->orderBy('id')
            ->get(['product_id', 'option_id', 'image'])->groupBy('product_id');

        foreach ($products as $product) {
            foreach ($priorities as $ids) {
                $image = $images->get($product->id, collect())->first(
                    fn ($image) => in_array((int) $image->option_id, $ids, true)
                );
                if ($image) {
                    // Keep the raw path: Product's accessor supplies the image domain and WebP.
                    $product->setAttribute('image', $image->image);
                    break;
                }
            }
        }
    }
}
