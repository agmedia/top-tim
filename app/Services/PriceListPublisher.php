<?php

namespace App\Services;

use App\Helpers\Special;
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
            $date = now(config('price_lists.timezone'))->toDateString();
            $existing = PriceListPublication::whereDate('publication_date', $date)->first();
            if ($existing) {
                if (!$this->valid($existing)) {
                    throw new RuntimeException('Današnji cjenik nedostaje ili je oštećen. Vratite izvornu datoteku iz sigurnosne kopije.');
                }
                return $existing;
            }
            return $this->generate($date);
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

    private function generate(string $date): PriceListPublication
    {
        $disk = Storage::disk(config('price_lists.disk'));
        $disk->makeDirectory('price-lists');
        $publishedAt = now();
        $filename = 'webshop_' . Str::slug(config('price_lists.store_address')) . '_'
            . Str::slug(config('price_lists.store_code')) . '_1_'
            . $publishedAt->copy()->timezone(config('price_lists.timezone'))->format('Ymd_His') . '.xml';
        $path = 'price-lists/' . $filename;
        $temporaryPath = $disk->path('price-lists/' . Str::uuid() . '.tmp');
        $xml = new XMLWriter();
        $publishedFile = false;
        try {
            if (!$xml->openUri($temporaryPath)) {
                throw new RuntimeException('Nije moguće zapisati cjenik. Provjerite dozvole direktorija.');
            }
            $xml->setIndent(true);
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('digital_price_list');
            $xml->writeAttribute('version', '1.0');
            $xml->startElement('metadata');
            foreach ([
                'store_name' => config('price_lists.store_name'),
                'sales_channel' => 'webshop',
                'store_address' => config('price_lists.store_address'),
                'store_code' => config('price_lists.store_code'),
                'published_at' => $publishedAt->copy()->timezone(config('price_lists.timezone'))->toIso8601String(),
                'currency' => 'EUR',
            ] as $key => $value) {
                $xml->writeElement($key, $this->text($value));
            }
            $xml->endElement();
            $xml->startElement('products');

            // Public prices only: never use a visitor's cart, coupon or customer discount.
            $actions = ProductAction::query()->where('status', 1)->whereNull('user_group_id')
                ->where(fn ($q) => $q->whereNull('coupon')->orWhere('coupon', ''))
                ->where(fn ($q) => $q->whereNull('logged')->orWhere('logged', 0))
                ->get();
            $actionNames = DB::table('product_actions_translations')->where('lang', 'hr')->pluck('title', 'product_action_id');
            $products = DB::table('products as p')
                ->leftJoin('product_translations as t', fn ($join) => $join->on('t.product_id', '=', 'p.id')->where('t.lang', 'hr'))
                ->leftJoin('brand_translations as b', fn ($join) => $join->on('b.brand_id', '=', 'p.brand_id')->where('b.lang', 'hr'))
                ->leftJoin('anchor_prices as a', 'a.product_id', '=', 'p.id')
                ->where('p.status', 1)->where('p.price', '>', 0)
                ->select('p.id', 'p.brand_id', 'p.sku', 'p.ean', 'p.price', 'p.quantity', 't.name',
                    'b.title as brand_name', 'a.price as anchor_price', 'a.reference_date', 'a.status as anchor_status');
            $count = 0;
            $missingBarcodes = 0;
            $invalid = [];
            foreach ($products->lazyById(250, 'p.id', 'id') as $row) {
                $referenceDate = $row->reference_date ? \Carbon\Carbon::parse($row->reference_date)->toDateString() : null;
                if ($row->anchor_status !== 'confirmed' || (float) $row->anchor_price <= 0
                    || !$referenceDate || $referenceDate > $date
                    || trim((string) $row->name) === '' || trim((string) $row->sku) === '') {
                    if (count($invalid) < 20) {
                        $invalid[] = $row->id;
                    }
                    continue;
                }
                $product = new Product();
                $product->setRawAttributes((array) $row);
                $special = new Special($product, null, true);
                $eligible = $actions->filter(fn ($action) => $special->checkDates($action) && $special->isProductOnAction($action));
                $locked = $eligible->where('lock', 1);
                $pool = $locked->isNotEmpty() ? $locked : $eligible;
                $current = (float) $row->price;
                $offer = '';
                foreach ($pool as $action) {
                    $candidate = max(0, (float) $special->getDiscountPrice($action));
                    if ($candidate < $current) {
                        $current = $candidate;
                        $offer = trim((string) ($actionNames[$action->id] ?? '')) ?: 'Akcijska prodaja';
                    }
                }
                $barcode = trim((string) $row->ean);
                if (!$this->validBarcode($barcode)) {
                    $barcode = '';
                    $missingBarcodes++;
                }
                $xml->startElement('product');
                foreach ([
                    'product_id' => $row->id, 'name' => $row->name, 'sku' => $row->sku,
                    'manufacturer' => $row->brand_name, 'unit' => 'kom', 'unit_price' => $this->money($current),
                    'regular_price' => $this->money($row->price), 'current_price' => $this->money($current),
                    'special_offer' => $offer !== '' ? 'da' : 'ne', 'special_offer_name' => $offer,
                    'anchor_price' => $this->money($row->anchor_price), 'anchor_date' => $referenceDate,
                    'barcode' => $barcode, 'availability' => $row->quantity > 0 ? 'dostupno' : 'nedostupno',
                    'quantity' => max(0, (int) $row->quantity), 'currency' => 'EUR',
                ] as $key => $value) {
                    $xml->writeElement($key, $this->text($value));
                }
                $xml->endElement();
                $count++;
            }
            if ($invalid) {
                throw new RuntimeException('Cjenik nije objavljen: nedostaje potvrđena sidrena cijena, naziv ili šifra. Provjerite proizvode ID: ' . implode(', ', $invalid) . '.');
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
            if ($disk->exists($path) || !rename($temporaryPath, $disk->path($path))) {
                throw new RuntimeException('Nije moguće spremiti cjenik.');
            }
            $publishedFile = true;
            return PriceListPublication::create([
                'publication_date' => $date, 'filename' => $filename, 'path' => $path,
                'sha256' => hash_file('sha256', $disk->path($path)), 'product_count' => $count,
                'missing_barcodes' => $missingBarcodes, 'published_at' => $publishedAt,
            ]);
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

    private function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function validBarcode(string $barcode): bool
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
