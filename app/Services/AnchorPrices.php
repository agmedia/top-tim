<?php

namespace App\Services;

use App\Models\AnchorPrice;
use App\Models\Back\Settings\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AnchorPrices
{
    public function displayEnabled(): bool
    {
        $attributes = request()->attributes;
        if (!$attributes->has('anchor_prices.display_enabled')) {
            // Read once per request so an admin change applies on the next page load.
            $value = Schema::hasTable('settings')
                ? Settings::where('code', 'anchor_prices')->where('key', 'display_enabled')->value('value')
                : null;
            $attributes->set('anchor_prices.display_enabled', $value === null || (string) $value === '1');
        }
        return $attributes->get('anchor_prices.display_enabled');
    }

    public function setDisplayEnabled(bool $enabled): void
    {
        Settings::updateOrCreate(['code' => 'anchor_prices', 'key' => 'display_enabled'], [
            'value' => $enabled ? '1' : '0',
            'json' => false,
        ]);
        request()->attributes->remove('anchor_prices.display_enabled');
    }

    public function forProduct(int $productId): ?array
    {
        if (!$this->displayEnabled()) {
            return null;
        }
        $request = request();
        if (!$request->attributes->has('anchor_prices.confirmed')) {
            // One shared lookup for cards, product sliders and Vue serialization.
            $prices = Schema::hasTable('anchor_prices') ? Cache::remember('anchor_prices.confirmed', 300, function () {
                return AnchorPrice::where('status', 'confirmed')->get()->mapWithKeys(fn ($price) => [
                    $price->product_id => [
                        'price' => (float) $price->price,
                        'formatted' => number_format((float) $price->price, 2, ',', '.') . ' €',
                        'date' => $price->reference_date->format('d.m.Y.'),
                    ],
                ])->all();
            }) : [];
            $request->attributes->set('anchor_prices.confirmed', $prices);
        }
        return $request->attributes->get('anchor_prices.confirmed')[$productId] ?? null;
    }

    public function validate(array $data): array
    {
        if (isset($data['price']) && is_string($data['price'])) {
            $data['price'] = str_replace(',', '.', trim($data['price']));
        }
        return Validator::make($data, [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'price' => ['required', 'numeric', 'gt:0', 'max:99999999999', 'decimal:0,4'],
            'reference_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['pending', 'confirmed'])],
            'note' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'price.*' => 'Unesite valjanu sidrenu cijenu veću od nule (najviše 4 decimale).',
            'reference_date.*' => 'Unesite valjan referentni datum koji nije u budućnosti.',
            'note.*' => 'Navedite izvor potvrde ili razlog izmjene (3–2000 znakova).',
            'product_id.*' => 'Proizvod ne postoji.',
            'status.*' => 'Odaberite status Za provjeru ili Potvrđeno.',
        ])->validate();
    }

    public function save(array $data, ?int $userId): AnchorPrice
    {
        $data = $this->validate($data);
        $price = DB::transaction(function () use ($data, $userId) {
            DB::table('products')->where('id', $data['product_id'])->lockForUpdate()->first();
            $price = AnchorPrice::firstOrNew(['product_id' => $data['product_id']]);
            $before = $price->exists ? $price->only(['price', 'reference_date', 'status']) : null;
            $price->fill($data + ['updated_by' => $userId])->save();
            DB::table('anchor_price_revisions')->insert([
                'product_id' => $price->product_id,
                'before' => $before ? json_encode($before) : null,
                'after' => json_encode($price->only(['price', 'reference_date', 'status'])),
                'reason' => $data['note'], 'user_id' => $userId, 'created_at' => now(),
            ]);
            return $price;
        });
        $this->forget();
        return $price;
    }

    public function forget(): void
    {
        Cache::forget('anchor_prices.confirmed');
        request()->attributes->remove('anchor_prices.confirmed');
    }
}
