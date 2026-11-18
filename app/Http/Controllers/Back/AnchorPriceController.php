<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\AnchorPrice;
use App\Models\PriceListPublication;
use App\Services\AnchorPrices;
use App\Services\PriceListPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AnchorPriceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless($request->user() && $request->user()->isActive()
                && $request->user()->roles()->whereIn('name', ['admin', 'superadmin'])->exists(), 403);
            return $next($request);
        });
    }

    private function products()
    {
        return DB::table('products as p')
            ->leftJoin('product_translations as t', fn ($join) => $join->on('t.product_id', '=', 'p.id')->where('t.lang', 'hr'))
            ->leftJoin('anchor_prices as a', 'a.product_id', '=', 'p.id')
            ->select('p.id', 'p.sku', 'p.price', 'p.status', 't.name', 'a.price as anchor_price',
                'a.reference_date', 'a.status as anchor_status');
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:150', 'status' => 'nullable|in:pending,confirmed']);
        $query = $this->products();
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(fn ($q) => $q->where('t.name', 'like', '%' . $search . '%')
                ->orWhere('p.sku', 'like', '%' . $search . '%')->orWhere('p.id', $search));
        }
        if (($filters['status'] ?? '') === 'confirmed') {
            $query->where('a.status', 'confirmed');
        } elseif (($filters['status'] ?? '') === 'pending') {
            $query->where(fn ($q) => $q->whereNull('a.status')->orWhere('a.status', 'pending'));
        }
        $products = $query->orderBy('p.id')->paginate(30)->withQueryString();
        $missing = $this->products()->where('p.status', 1)->where('p.price', '>', 0)
            ->where(fn ($q) => $q->whereNull('a.status')->orWhere('a.status', '!=', 'confirmed'))->count();
        $latest = PriceListPublication::orderByDesc('id')->first();
        return view('back.anchor-prices.index', compact('products', 'missing', 'latest'));
    }

    public function settings(Request $request)
    {
        $request->validate(['display_enabled' => 'accepted'], [
            'display_enabled.accepted' => 'Prikaz potvrđenih baznih cijena je obvezan.',
        ]);
        return redirect()->route('anchor-prices.index');
    }

    public function edit(int $product)
    {
        $product = $this->products()->where('p.id', $product)->first();
        abort_unless($product, 404);
        $anchor = AnchorPrice::where('product_id', $product->id)->first();
        $revisions = DB::table('anchor_price_revisions')->where('product_id', $product->id)->orderByDesc('id')->paginate(20);
        $options = DB::table('product_option')->where('product_id', $product->id)->orderBy('sku')->get();
        return view('back.anchor-prices.edit', compact('product', 'anchor', 'revisions', 'options'));
    }

    public function update(Request $request, int $product, AnchorPrices $prices)
    {
        $prices->save(array_merge($request->only('price', 'reference_date', 'status', 'note', 'net_quantity', 'measure_unit', 'goods_barcode', 'reference_group', 'first_offered_on'), ['product_id' => $product]), $request->user()->id);
        return redirect()->route('anchor-prices.edit', $product)->with('success', 'Bazna cijena je spremljena.');
    }

    public function updateVariant(Request $request, int $product, int $option, AnchorPrices $prices)
    {
        $prices->saveVariant($product, $option, $request->only('price', 'reference_date', 'status', 'note', 'ean', 'goods_barcode'), $request->user()->id);
        return redirect()->route('anchor-prices.edit', $product)->with('success', 'Bazna cijena varijante je spremljena.');
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['product_id', 'sku', 'name', 'price', 'reference_date', 'status'], ';', '"', '');
            foreach ($this->products()->where('p.status', 1)->where('p.price', '>', 0)->lazyById(250, 'p.id', 'id') as $product) {
                $safe = fn ($value) => preg_match('/^[=+@\-\t\r\n]/u', (string) $value) ? "'" . $value : $value;
                fputcsv($output, [$product->id, $safe($product->sku), $safe($product->name),
                    $product->anchor_price,
                    $product->reference_date,
                    $product->anchor_status ?? 'pending'], ';', '"', '');
            }
            fclose($output);
        }, 'sidrene-cijene.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request, AnchorPrices $prices)
    {
        $request->validate(['file' => 'required|file|max:5120', 'note' => 'required|string|min:3|max:2000', 'dry_run' => 'nullable|boolean']);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $rows = [];
        try {
            $line = fgets($handle);
            $delimiter = substr_count((string) $line, ';') >= substr_count((string) $line, ',') ? ';' : ',';
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter, '"', '');
            if (!$headers) {
                throw ValidationException::withMessages(['file' => 'CSV datoteka je prazna.']);
            }
            $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
            if (count(array_unique($headers)) !== count($headers)
                || array_diff(['product_id', 'price', 'reference_date', 'status'], $headers)) {
                throw ValidationException::withMessages(['file' => 'Obvezni stupci: product_id, price, reference_date, status.']);
            }
            $lineNumber = 1;
            $ids = [];
            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $lineNumber++;
                if ($values === [null]) {
                    continue;
                }
                if (count($rows) >= 10000 || count($values) !== count($headers)) {
                    throw ValidationException::withMessages(['file' => "Neispravan redak {$lineNumber} ili više od 10.000 redaka."]);
                }
                $row = array_combine($headers, $values);
                $row['note'] = $request->input('note');
                try {
                    $validated = $prices->validate($row);
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['file' => "Redak {$lineNumber}: " . collect($exception->errors())->flatten()->implode(' ')]);
                }
                if (isset($ids[(int) $validated['product_id']])) {
                    throw ValidationException::withMessages(['file' => "Proizvod se ponavlja u retku {$lineNumber}."]);
                }
                $ids[(int) $validated['product_id']] = true;
                if (!empty($row['sku'])) {
                    $sku = DB::table('products')->where('id', $validated['product_id'])->value('sku');
                    if ($row['sku'] !== (string) $sku && $row['sku'] !== "'" . $sku) {
                        throw ValidationException::withMessages(['file' => "Šifra i ID proizvoda se ne podudaraju u retku {$lineNumber}."]);
                    }
                }
                $rows[] = $validated;
            }
        } finally {
            fclose($handle);
        }
        if (!$rows) {
            throw ValidationException::withMessages(['file' => 'CSV ne sadrži nijedan proizvod.']);
        }
        if (!$request->boolean('dry_run')) {
            DB::transaction(function () use ($rows, $prices, $request) {
                foreach ($rows as $row) {
                    $prices->save($row, $request->user()->id);
                }
            });
            $prices->forget();
        }
        return redirect()->route('anchor-prices.index')->with('success',
            ($request->boolean('dry_run') ? 'Provjera uspješna, bez spremanja. Redaka: ' : 'Uvezeno redaka: ') . count($rows));
    }

    public function publish(PriceListPublisher $publisher)
    {
        try {
            $publication = $publisher->publish();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['publication' => $exception->getMessage()]);
        }
        return redirect()->route('anchor-prices.index')->with('success', 'Važeći cjenik je dostupan: ' . $publication->product_count . ' proizvoda.');
    }
}
