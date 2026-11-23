<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LowestPriceCorrections
{
    private const TABLE = 'product_lowest_price_corrections';
    private const CACHE_KEY = 'lowest_price_corrections.latest.v1';

    public function save(int $productId, array $input, User $actor): object
    {
        abort_unless(Schema::hasTable(self::TABLE), 503, 'Uređivanje najniže cijene trenutačno nije dostupno.');
        if (isset($input['price']) && is_string($input['price'])) {
            $input['price'] = str_replace(',', '.', trim($input['price']));
        }
        if (isset($input['reason']) && is_string($input['reason'])) {
            $input['reason'] = trim($input['reason']);
        }
        $data = Validator::make($input, [
            'mode' => ['required', Rule::in(['manual', 'automatic'])],
            'price' => ['exclude_unless:mode,manual', 'required', 'numeric', 'gt:0', 'max:99999999999.99', 'decimal:0,2'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'mode.*' => 'Odaberite ručni ispravak ili automatski izračun.',
            'price.*' => 'Unesite cijenu veću od nule, do 99.999.999.999,99 € i najviše dvije decimale.',
            'reason.*' => 'Unesite razlog ispravka od 3 do 1000 znakova.',
        ])->validate();

        return DB::transaction(function () use ($productId, $data, $actor) {
            $fresh = User::query()->whereKey($actor->id)->lockForUpdate()->first();
            $detail = $fresh?->details()->lockForUpdate()->first();
            abort_unless($fresh && $detail && $detail->status
                && $fresh->roles()->whereIn('name', ['admin', 'superadmin'])->exists(), 403);
            abort_unless(DB::table('products')->where('id', $productId)->lockForUpdate()->first(), 404);

            $created = now();
            $id = DB::table(self::TABLE)->insertGetId([
                'product_id' => $productId,
                'mode' => $data['mode'],
                'price' => $data['mode'] === 'manual' ? $data['price'] : null,
                'reason' => $data['reason'], 'actor_id' => $fresh->id,
                'created_at' => $created,
                'expires_at' => $data['mode'] === 'manual' ? $created->copy()->addDays(30) : null,
            ]);
            DB::afterCommit(fn () => $this->forget());

            return DB::table(self::TABLE)->where('id', $id)->first();
        });
    }

    public function forProduct(int $productId): ?array
    {
        $attributes = request()->attributes;
        if (!$attributes->has(self::CACHE_KEY)) {
            $latest = Schema::hasTable(self::TABLE) ? Cache::remember(self::CACHE_KEY, 60, function () {
                // Select the latest audit row before considering its mode or expiry.
                return DB::table(self::TABLE)->whereIn('id', function ($query) {
                    $query->from(self::TABLE)->selectRaw('MAX(id)')->groupBy('product_id');
                })->get(['product_id', 'mode', 'price', 'created_at', 'expires_at'])->keyBy('product_id')->all();
            }) : [];
            $attributes->set(self::CACHE_KEY, $latest);
        }
        $latest = $attributes->get(self::CACHE_KEY)[$productId] ?? null;
        // Expiry is checked at read time, including when the raw row came from cache.
        if (!$this->isActive($latest)) {
            return null;
        }

        return [
            'price' => (float) $latest->price,
            'formatted' => number_format((float) $latest->price, 2, ',', '.').' €',
            'since' => Carbon::parse($latest->created_at)->timezone('Europe/Zagreb')->format('d.m.Y.'),
            'note' => 'Ručno ispravljena najniža cijena.',
        ];
    }

    public function forAdmin(int $productId): array
    {
        $history = Schema::hasTable(self::TABLE)
            ? DB::table(self::TABLE)->where('product_id', $productId)->orderByDesc('id')->limit(10)->get()
            : collect();
        $recorded = app(RecordedPrices::class);

        return [
            'automatic' => $recorded->automaticForProduct($productId),
            'correction' => $history->first(),
            'history' => $history,
            'effective' => $recorded->forProduct($productId),
            'manual_active' => $this->isActive($history->first()),
        ];
    }

    private function isActive(?object $correction): bool
    {
        return $correction && $correction->mode === 'manual' && $correction->price !== null
            && $correction->expires_at && Carbon::parse($correction->expires_at)->greaterThan(now());
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        request()->attributes->remove(self::CACHE_KEY);
        app(RecordedPrices::class)->forget();
    }
}
