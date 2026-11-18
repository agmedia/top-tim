<?php

namespace App\Services;

use App\Helpers\Special;
use App\Helpers\Helper;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\ProductAction;
use App\Models\PriceListPublication;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use XMLWriter;

class PriceListPublisher
{
    public function publish(): PriceListPublication
    {
        $result = Cache::lock('price_lists.publish', 1800)->get(function () {
            $current = PriceListPublication::orderByDesc('id')->first();
            if ($current && !$this->valid($current)) {
                throw new RuntimeException('Aktualni cjenik nedostaje ili je oštećen. Vratite izvornu datoteku iz sigurnosne kopije.');
            }
            return $this->generate($current);
        });
        if (!$result) {
            throw new RuntimeException('Objava cjenika već je u tijeku. Pokušajte ponovno za nekoliko minuta.');
        }
        return $result;
    }

    public function valid(PriceListPublication $publication): bool
    {
        $path = Storage::disk(config('price_lists.disk'))->path($publication->path);
        return is_file($path) && hash_equals($publication->sha256, hash_file('sha256', $path));
    }

    private function generate(?PriceListPublication $previous): PriceListPublication
    {
        $disk = Storage::disk(config('price_lists.disk'));
        $disk->makeDirectory('price-lists');
        $publishedAt = now(config('price_lists.timezone'));
        $date = $publishedAt->toDateString();
        $revision = $previous && $previous->publication_date->toDateString() === $date ? $previous->revision + 1 : 1;
        $filename = implode('_', [config('price_lists.oib'), 'internetska-trgovina', Str::slug(config('price_lists.store_address')),
            config('price_lists.store_code'), 'ROBA', $revision, $publishedAt->format('Ymd_Hi')]) . '.xml';
        // A private unique path keeps file cleanup isolated even if two workers contend.
        $path = 'price-lists/' . Str::uuid() . '.xml';
        $temporaryPath = $disk->path($path . '.tmp');
        $hash = hash_init('sha256');
        $xml = new XMLWriter();
        $publishedFile = false;
        try {
            if (!$xml->openUri($temporaryPath)) {
                throw new RuntimeException('Nije moguće zapisati cjenik. Provjerite dozvole direktorija.');
            }
            $xml->setIndent(true);
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('digital_price_list');
            $xml->writeAttribute('version', '2.0');
            $xml->startElement('metadata');
            $metadata = [
                'oib' => config('price_lists.oib'),
                'store_name' => config('price_lists.store_name'),
                'sales_channel' => 'webshop',
                'store_address' => config('price_lists.store_address'),
                'store_code' => config('price_lists.store_code'),
                'currency' => 'EUR',
            ];
            hash_update($hash, json_encode(['version' => 2, 'metadata' => $metadata], JSON_THROW_ON_ERROR));
            $metadata['published_at'] = $publishedAt->toIso8601String();
            foreach ($metadata as $key => $value) {
                $xml->writeElement($key, $this->text($value));
            }
            $xml->endElement();
            $xml->startElement('products');

            // Public prices only: never use a visitor's cart, coupon or customer discount.
            $actions = ProductAction::query()->active()->where('group', '!=', 'total')->whereNull('user_group_id')
                ->where(fn ($q) => $q->whereNull('coupon')->orWhere('coupon', ''))
                ->where(fn ($q) => $q->whereNull('logged')->orWhere('logged', 0))
                ->get();
            $actionNames = DB::table('product_actions_translations')->where('lang', 'hr')->pluck('title', 'product_action_id');
            $actionProductId = null;
            $pool = collect();
            $count = 0;
            $missingBarcodes = 0;
            $invalid = [];
            foreach ($this->offers() as $row) {
                $referenceDate = $row->reference_date ? \Carbon\Carbon::parse($row->reference_date)->toDateString() : null;
                if ($row->anchor_status !== 'confirmed' || (float) $row->anchor_price <= 0
                    || !$referenceDate || $referenceDate > $date
                    || trim((string) $row->name) === '' || trim((string) $row->sku) === '') {
                    $invalid[] = $row->sku ?: "ID $row->id";
                    continue;
                }
                if ($actionProductId !== $row->id) {
                    $product = new Product();
                    $product->setRawAttributes((array) $row);
                    $special = new Special($product, null, true);
                    $eligible = $actions->filter(fn ($action) => $special->isProductOnAction($action));
                    $locked = $eligible->where('lock', 1);
                    $pool = $locked->isNotEmpty() ? $locked : $eligible;
                    $actionProductId = $row->id;
                }
                $current = (float) $row->price;
                $offer = '';
                foreach ($pool as $action) {
                    $candidate = Helper::calculateDiscountPrice($row->price, $action->discount, $action->type);
                    if ($candidate < $current) {
                        $current = $candidate;
                        $offer = trim((string) ($actionNames[$action->id] ?? '')) ?: 'Akcijska prodaja';
                    }
                }
                $gtin = trim((string) $row->ean);
                if (!self::validBarcode($gtin)) {
                    $gtin = config('price_lists.verified_ean')[$row->sku] ?? '';
                }
                $barcode = trim((string) $row->goods_barcode) ?: $gtin;
                $ean = in_array(strlen($gtin), [8, 13], true) ? $gtin : '';
                if ($barcode === '') {
                    $missingBarcodes++;
                }
                $net = $row->net_quantity;
                if (($net === null) !== ($row->measure_unit === null) || ($net !== null && (float) $net <= 0)) {
                    throw new RuntimeException("Provjerite neto količinu i jedinicu mjere za {$row->sku}.");
                }
                $fields = [
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'manufacturer' => $row->brand_name,
                    'net_quantity' => $net === null ? '' : rtrim(rtrim(number_format((float) $net, 6, '.', ''), '0'), '.'),
                    'unit' => $row->measure_unit ?? 'kom',
                    'current_price' => $this->money($current),
                    'special_offer' => $offer !== '' ? 'da' : 'ne',
                    'unit_price' => $this->money($current / ($net ?? 1)),
                    'barcode' => $barcode,
                    'ean' => $ean,
                    'anchor_price' => $this->money($row->anchor_price),
                ];
                // Legacy supplementary values remain available after the ordered statutory fields.
                $fields += [
                    'product_id' => $row->id, 'regular_price' => $this->money($row->price),
                    'special_offer_name' => $offer, 'anchor_date' => $referenceDate,
                    'availability' => $row->quantity > 0 ? 'dostupno' : 'nedostupno',
                    'quantity' => max(0, (int) $row->quantity), 'currency' => 'EUR',
                ];
                hash_update($hash, json_encode($fields, JSON_THROW_ON_ERROR));
                $xml->startElement('product');
                foreach ($fields as $key => $value) {
                    $xml->writeElement($key, $this->text($value));
                }
                $xml->endElement();
                $count++;
            }
            if ($invalid) {
                throw new RuntimeException('Cjenik nije objavljen: nedostaje potvrđena bazna cijena, naziv ili šifra. Provjerite šifre: ' . implode(', ', $invalid) . '.');
            }
            if (!$count) {
                throw new RuntimeException('Cjenik nije objavljen jer nema aktivnih proizvoda s cijenom.');
            }
            $xml->endElement();
            $xml->writeElement('product_count', (string) $count);
            $xml->endElement();
            $xml->endDocument();
            $xml->flush();
            unset($xml);
            $contentHash = hash_final($hash);
            if ($previous && $previous->content_hash === $contentHash) {
                unlink($temporaryPath);
                return $previous;
            }
            if ($disk->exists($path) || !rename($temporaryPath, $disk->path($path))) {
                throw new RuntimeException('Nije moguće spremiti cjenik.');
            }
            $publishedFile = true;
            return DB::transaction(function () use ($previous, $date, $revision, $filename, $path, $disk, $contentHash, $count, $missingBarcodes, $publishedAt) {
                if ($previous && PriceListPublication::whereKey($previous->id)->whereNull('replaced_at')
                    ->update(['replaced_at' => $publishedAt]) !== 1) {
                    throw new RuntimeException('Cjenik je već zamijenjen. Ponovite objavu.');
                }
                return PriceListPublication::create([
                    'publication_date' => $date, 'revision' => $revision, 'filename' => $filename, 'path' => $path,
                    'sha256' => hash_file('sha256', $disk->path($path)), 'content_hash' => $contentHash,
                    'product_count' => $count, 'missing_barcodes' => $missingBarcodes, 'published_at' => $publishedAt,
                ]);
            });
        } catch (Throwable $exception) {
            if (isset($xml)) {
                unset($xml);
            }
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
            if ($publishedFile) {
                $disk->delete($path);
            }
            throw $exception;
        }
    }

    private function offers(): \Generator
    {
        $products = DB::table('products as p')
            ->leftJoin('product_translations as t', fn ($join) => $join->on('t.product_id', '=', 'p.id')->where('t.lang', 'hr'))
            ->leftJoin('brand_translations as b', fn ($join) => $join->on('b.brand_id', '=', 'p.brand_id')->where('b.lang', 'hr'))
            ->leftJoin('anchor_prices as a', 'a.product_id', '=', 'p.id')
            ->where('p.status', 1)->where('p.price', '>', 0)
            ->select('p.id', 'p.brand_id', 'p.sku', 'p.ean', 'p.price', 'p.quantity', 't.name',
                'b.title as brand_name', 'a.price as anchor_price', 'a.reference_date', 'a.status as anchor_status',
                'a.variant_prices', 'a.net_quantity', 'a.measure_unit', 'a.goods_barcode');
        foreach ($products->lazyById(250, 'p.id', 'id')->chunk(250) as $batch) {
            $options = DB::table('product_option as o')
                ->leftJoin('options_translations as child', fn ($join) => $join->on('child.option_id', '=', 'o.option_id')->where('child.lang', 'hr'))
                ->leftJoin('options_translations as parent', fn ($join) => $join->on('parent.option_id', '=', 'o.parent_id')->where('parent.lang', 'hr'))
                ->whereIn('o.product_id', $batch->pluck('id')->all())->orderBy('o.id')
                ->get(['o.*', 'child.title as child_name', 'parent.title as parent_name'])->groupBy('product_id');
            foreach ($batch as $row) {
                $variants = $options->get($row->id);
                if (!$variants) {
                    yield $row;
                    continue;
                }
                $prices = json_decode($row->variant_prices ?? '{}', true, 512, JSON_THROW_ON_ERROR);
                foreach ($variants as $option) {
                    $offer = clone $row;
                    $offer->sku = trim((string) $option->sku) ?: $row->sku;
                    $labels = array_filter([$option->parent_name, $option->child_name]);
                    $offer->name = $row->name . ($labels ? ' – ' . implode(' / ', $labels) : '');
                    $offer->price = (float) $row->price + (float) $option->price;
                    $offer->quantity = $option->quantity;
                    $variant = $prices[$option->parent_id . ':' . $option->option_id] ?? null;
                    // A model-level EAN need not identify any particular size/colour.
                    $offer->ean = $variant['ean'] ?? '';
                    $offer->goods_barcode = $variant['goods_barcode'] ?? '';
                    if ($variant) {
                        $offer->anchor_price = $variant['price'];
                        $offer->anchor_status = $variant['status'];
                        $offer->reference_date = $variant['reference_date'];
                    } elseif ((float) $option->price != 0) {
                        $offer->anchor_status = 'pending';
                    }
                    yield $offer;
                }
            }
        }
    }

    private function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    public static function validBarcode(string $barcode): bool
    {
        if (!preg_match('/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/D', $barcode)) {
            return false;
        }
        $sum = 0;
        $weight = 3;
        for ($i = strlen($barcode) - 2; $i >= 0; $i--) {
            $sum += (int) $barcode[$i] * $weight;
            $weight = $weight === 3 ? 1 : 3;
        }
        return (10 - ($sum % 10)) % 10 === (int) substr($barcode, -1);
    }

    private function text($value): string
    {
        return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $value) ?? '';
    }
}
