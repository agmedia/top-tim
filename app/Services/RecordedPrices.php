<?php

namespace App\Services;

use App\Helpers\Special;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\ProductAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Observed public prices, never a backdated or certified pre-sale reference price. */
class RecordedPrices
{
    private const CACHE_KEY = 'recorded_prices.minimums.v1';

    public function capture(bool $missingOnly = false): int
    {
        $latest = DB::table('product_price_observations')->whereIn('id', function ($q) {
            $q->from('product_price_observations')->selectRaw('MAX(id)')->groupBy('product_id');
        })->get()->keyBy('product_id');
        $actions = ProductAction::where('status', 1)->whereNull('user_group_id')
            ->where(fn ($q) => $q->whereNull('coupon')->orWhere('coupon', ''))
            ->where(fn ($q) => $q->whereNull('logged')->orWhere('logged', 0))->get();
        $discounts = app(QuantityDiscounts::class);
        $created = 0;
        DB::table('products')->where('status', 1)->where('price', '>', 0)
            ->select('id', 'brand_id', 'price')->orderBy('id')->chunkById(250, function ($rows) use ($latest, $actions, $discounts, $missingOnly, &$created) {
                $inserts = [];
                $unchanged = [];
                $time = now();
                foreach ($rows as $row) {
                    $previous = $latest->get($row->id);
                    if ($missingOnly && $previous) {
                        continue;
                    }
                    $product = new Product();
                    $product->setRawAttributes((array) $row);
                    $special = new Special($product, null, true);
                    $eligible = $actions->filter(fn ($action) => $special->checkDates($action) && $special->isProductOnAction($action));
                    $locked = $eligible->where('lock', 1);
                    $current = (float) $row->price;
                    foreach ($locked->isNotEmpty() ? $locked : $eligible as $action) {
                        $current = min($current, max(0, (float) $special->getDiscountPrice($action)));
                    }
                    $current = $discounts->publicPrice((float) $row->price, $current, (int) $row->brand_id, 1);
                    $current = round($current, 4);
                    if ($previous && abs((float) $previous->price - $current) < 0.00005) {
                        $unchanged[] = $previous->id;
                        continue;
                    }
                    $inserts[] = ['product_id' => $row->id, 'price' => $current,
                        'observed_at' => $time, 'last_seen_at' => $time,
                        'source' => $previous ? 'public_price_snapshot' : 'current_price_seed'];
                }
                DB::transaction(function () use ($inserts, $unchanged, $time) {
                    if ($inserts) {
                        DB::table('product_price_observations')->insert($inserts);
                    }
                    if ($unchanged) {
                        DB::table('product_price_observations')->whereIn('id', $unchanged)->update(['last_seen_at' => $time]);
                    }
                });
                $created += count($inserts);
            });
        $this->forget();
        return $created;
    }

    public function forProduct(int $productId): ?array
    {
        return app(LowestPriceCorrections::class)->forProduct($productId)
            ?? $this->automaticForProduct($productId);
    }

    public function automaticForProduct(int $productId): ?array
    {
        $attributes = request()->attributes;
        if (!$attributes->has(self::CACHE_KEY)) {
            $values = Schema::hasTable('product_price_observations')
                ? Cache::remember(self::CACHE_KEY, 60, function () {
                    $cutoff = now()->subDays(30);
                    $starts = DB::table('product_price_observations')->selectRaw('product_id, MIN(observed_at) as started_at')->groupBy('product_id');
                    return DB::table('product_price_observations as p')->joinSub($starts, 's', 's.product_id', '=', 'p.product_id')
                        ->where('p.last_seen_at', '>=', $cutoff)->where('p.observed_at', '<=', now())
                        ->selectRaw('p.product_id, MIN(p.price) as price, s.started_at')->groupBy('p.product_id', 's.started_at')
                        ->get()->mapWithKeys(function ($row) use ($cutoff) {
                            $start = Carbon::parse($row->started_at);
                            return [$row->product_id => ['price' => (float) $row->price,
                                'formatted' => number_format((float) $row->price, 2, ',', '.') . ' €',
                                'since' => $start->timezone('Europe/Zagreb')->format('d.m.Y.'),
                                'note' => $start->greaterThan($cutoff)
                                    ? 'Evidencija od ' . $start->format('d.m.Y.')
                                    : 'Zabilježene javne cijene u zadnjih 30 dana.']];
                        })->all();
                }) : [];
            $attributes->set(self::CACHE_KEY, $values);
        }
        return $attributes->get(self::CACHE_KEY)[$productId] ?? null;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        request()->attributes->remove(self::CACHE_KEY);
    }
}
