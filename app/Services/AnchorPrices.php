<?php

namespace App\Services;

use App\Models\AnchorPrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnchorPrices
{
    public function forProduct(int $productId): ?array
    {
        $request = request();
        if (!$request->attributes->has('anchor_prices.confirmed')) {
            // One shared lookup for cards, product sliders and Vue serialization.
            $prices = Schema::hasTable('anchor_prices') ? Cache::remember('anchor_prices.confirmed', 300, function () {
                return AnchorPrice::where('status', 'confirmed')->get()->mapWithKeys(fn ($price) => [
                    $price->product_id => [
                        'price' => (float) $price->price,
                        'formatted' => number_format((float) $price->price, 2, ',', '.') . ' €',
                        'date' => $price->reference_date->format('d.m.Y.'),
                    ] + ($price->net_quantity ? ['net_quantity' => (float) $price->net_quantity, 'unit' => $price->measure_unit] : []),
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
        if (isset($data['net_quantity']) && is_string($data['net_quantity'])) {
            $data['net_quantity'] = str_replace(',', '.', trim($data['net_quantity']));
        }
        $validated = Validator::make($data, [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'price' => ['required', 'numeric', 'gt:0', 'max:99999999999', 'decimal:0,4'],
            'reference_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['pending', 'confirmed'])],
            'reference_group' => ['nullable', 'required_with:first_offered_on', Rule::in(['general', 'household'])],
            'first_offered_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'net_quantity' => ['nullable', 'required_with:measure_unit', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,6'],
            'measure_unit' => ['nullable', 'required_with:net_quantity', Rule::in(['kg', 'l', 'm', 'm²', 'm³', 'kom'])],
            'goods_barcode' => ['nullable', 'string', 'max:100'],
            'note' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'price.*' => 'Unesite valjanu sidrenu cijenu veću od nule (najviše 4 decimale).',
            'reference_date.*' => 'Unesite valjan referentni datum koji nije u budućnosti.',
            'note.*' => 'Navedite izvor potvrde ili razlog izmjene (3–2000 znakova).',
            'product_id.*' => 'Proizvod ne postoji.',
            'status.*' => 'Odaberite status Za provjeru ili Potvrđeno.',
        ])->validate();
        $this->checkReferenceDate($validated);
        return $validated;
    }

    private function checkReferenceDate(array $values): void
    {
        if (empty($values['reference_group']) || $values['status'] !== 'confirmed') {
            return;
        }
        $cutoff = $values['reference_group'] === 'household' ? '2025-05-02' : config('price_lists.reference_date');
        $date = $values['reference_date'];
        if ($date < $cutoff || (!empty($values['first_offered_on']) && $date !== max($cutoff, $values['first_offered_on']))) {
            throw ValidationException::withMessages(['reference_date' => 'Datum mora odgovarati referentnom datumu skupine ili kasnijem danu prve stvarne ponude.']);
        }
    }

    public function save(array $data, ?int $userId): AnchorPrice
    {
        $data = $this->validate($data);
        $price = DB::transaction(function () use ($data, $userId) {
            DB::table('products')->where('id', $data['product_id'])->lockForUpdate()->first();
            $price = AnchorPrice::firstOrNew(['product_id' => $data['product_id']]);
            $tracked = ['price', 'reference_date', 'status', 'net_quantity', 'measure_unit', 'goods_barcode', 'reference_group', 'first_offered_on'];
            $before = $price->exists ? $price->only($tracked) : null;
            $price->fill($data + ['updated_by' => $userId]);
            $this->checkReferenceDate($price->only(['status', 'reference_group', 'first_offered_on']) + ['reference_date' => $data['reference_date']]);
            $price->save();
            DB::table('anchor_price_revisions')->insert([
                'product_id' => $price->product_id,
                'before' => $before ? json_encode($before) : null,
                'after' => json_encode($price->only($tracked)),
                'reason' => $data['note'], 'user_id' => $userId, 'created_at' => now(),
            ]);
            return $price;
        });
        $this->forget();
        return $price;
    }

    public function saveVariant(int $productId, int $optionId, array $input, ?int $userId): void
    {
        $data = $this->validate($input + ['product_id' => $productId]);
        $codes = Validator::make($input, [
            'ean' => ['nullable', 'regex:/^(?:[0-9]{8}|[0-9]{13})$/'],
        ])->validate();
        if (!empty($codes['ean']) && !PriceListPublisher::validBarcode($codes['ean'])) {
            throw ValidationException::withMessages(['ean' => 'EAN nema ispravnu kontrolnu znamenku.']);
        }
        DB::transaction(function () use ($productId, $optionId, $data, $codes, $userId) {
            DB::table('products')->where('id', $productId)->lockForUpdate()->first();
            $option = DB::table('product_option')->where('product_id', $productId)->where('id', $optionId)->first();
            abort_unless($option, 404);
            $anchor = AnchorPrice::where('product_id', $productId)->lockForUpdate()->firstOrFail();
            $variants = $anchor->variant_prices ?? [];
            $key = $option->parent_id . ':' . $option->option_id;
            $before = $variants[$key] ?? null;
            $variants[$key] = [
                'sku' => $option->sku, 'price' => $data['price'], 'reference_date' => $data['reference_date'],
                'status' => $data['status'], 'ean' => $codes['ean'] ?? null, 'goods_barcode' => $data['goods_barcode'] ?? null,
            ];
            $anchor->variant_prices = $variants;
            $anchor->save();
            DB::table('anchor_price_revisions')->insert([
                'product_id' => $productId, 'before' => $before ? json_encode($before) : null,
                'after' => json_encode($variants[$key]), 'reason' => $data['note'],
                'user_id' => $userId, 'created_at' => now(),
            ]);
        });
    }

    public function forget(): void
    {
        Cache::forget('anchor_prices.confirmed');
        request()->attributes->remove('anchor_prices.confirmed');
    }
}
