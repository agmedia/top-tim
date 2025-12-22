<?php

namespace App\Models\Back\Settings\Api;

use App\Helpers\ApiHelper;
use App\Helpers\Csv;
use App\Mail\akmkSendReport;
use App\Models\Back\Catalog\BrandTranslation;
use App\Models\Back\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductOption;
use App\Models\Back\Catalog\Product\ProductTranslation;
use App\Models\Back\Jobs;
use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\SizeGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Symfony\Component\HttpFoundation\StreamedResponse;
use stdClass;

class Export
{
    /**
     * @var array|null
     */
    protected $request;

    /**
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @var string[]
     */
    protected $excel_keys = [
        'Šifra',
        'Šifra opcije', // Ako je vezana opcija odvojena crticom
        'Barkod', // C
        'Naziv',
        'Opis',
        'Slug',
        'Meta naziv',
        'Meta opis', // Max znakova.?
        'Cijena', // I
        'Količina', // J
        'PDV', // K
        'Aktivan', // L
        'Slike', // Odvojene zarezom. Šifra nakon zadnje crtice u nazivu slike ako se pridružuje opciji.
        'Proizvođač', // N
        'Primarna skupina', // Glavna kategorija.
        'Sekundarna skupina', // P
        'Tablica veličina', // Q
        'Materijal', // Atribut. Vrijednosti odvojene zarezom.
        'Spol', // Atribut. Vrijednosti odvojene zarezom.
        'Tip rukava', // Atribut. Vrijednosti odvojene zarezom.
        'Kroj', // Atribut. Vrijednosti odvojene zarezom.
        'Dimenzije', // Atribut. Vrijednosti odvojene zarezom.
        'Dodatna kategorizacija', // Atribut. Vrijednosti odvojene zarezom.
        'Naziv opcije',        // 👈 NOVO
        'Vrijednost opcije',   // 👈 NOVO
    ];

    protected $coordinate_letters = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'
    ];

    protected $imagesBaseDir = '';


    /**
     * @return int
     */
    public function toExcel()
    {
        $job = new Jobs();
        $job->start('cron', 'Pošalji excel report', '', ApiHelper::response(0, 'Nije završen'));

        //
        $spreadsheet  = new Spreadsheet();
        $active_sheet = $spreadsheet->getActiveSheet();

        $active_sheet->setTitle('TopTim_Export');

        for ($i = 0; $i < count($this->excel_keys); $i++) {
            $active_sheet->setCellValue($this->coordinate_letters[$i] . '1', $this->excel_keys[$i]);
        }

        $row = 2;

        $products = Product::query()
                           ->with('translation', 'images', 'categories', 'subcategories', 'options', 'attributes')
                           //->whereIn('id', [2674,2682,2691,2694])
                           /*->inRandomOrder()
                           ->limit(500)*/
                           ->get();

        foreach ($products as $product) {
            $images       = $this->setImagesString($product);
            $sizeguide_id = $this->getSizeGuideID($product);
            $attributes   = $this->getAttributes($product);

            $active_sheet->setCellValue('A' . $row, $product->sku);
            $active_sheet->setCellValue('B' . $row, '');
            $active_sheet->setCellValue('C' . $row, $product->ean);
            $active_sheet->setCellValue('D' . $row, $product->translation->name);
            $active_sheet->setCellValue('E' . $row, $product->translation->description);
            $active_sheet->setCellValue('F' . $row, $product->translation->slug);
            $active_sheet->setCellValue('G' . $row, $product->translation->meta_title);
            $active_sheet->setCellValue('H' . $row, $product->translation->meta_description);
            $active_sheet->setCellValue('I' . $row, $product->price);
            $active_sheet->setCellValue('J' . $row, $product->quantity);
            $active_sheet->setCellValue('K' . $row, 25);
            $active_sheet->setCellValue('L' . $row, $product->status ? 1 : 0);
            $active_sheet->setCellValue('M' . $row, $images);
            $active_sheet->setCellValue('N' . $row, $product->brand ? $product->brand->translation->title : '');
            $active_sheet->setCellValue('O' . $row, $product->categories()->first() ? $product->categories()->first()->translation->title : '');
            $active_sheet->setCellValue('P' . $row, $product->subcategories()->first() ? $product->subcategories()->first()->translation->title : '');
            $active_sheet->setCellValue('Q' . $row, $sizeguide_id);
            $active_sheet->setCellValue('R' . $row, $attributes['Materijal']);
            $active_sheet->setCellValue('S' . $row, $attributes['Spol']);
            $active_sheet->setCellValue('T' . $row, $attributes['Tip rukava']);
            $active_sheet->setCellValue('U' . $row, $attributes['Kroj']);
            $active_sheet->setCellValue('V' . $row, $attributes['Dimenzije']);
            $active_sheet->setCellValue('W' . $row, $attributes['Dodatna kategorizacija']);

            $row++;

            if ($product->options()->count() > 0) {
                foreach ($product->options()->get() as $option) {
                    $option_id = $option->parent_id != 0 ? $option->parent_id : $option->option_id;

                    $active_sheet->setCellValue('A' . $row, $product->sku);
                    $active_sheet->setCellValue('B' . $row, $option->sku);
                    $active_sheet->setCellValue('C' . $row, '');
                    $active_sheet->setCellValue('D' . $row, '');
                    $active_sheet->setCellValue('E' . $row, '');
                    $active_sheet->setCellValue('F' . $row, '');
                    $active_sheet->setCellValue('G' . $row, '');
                    $active_sheet->setCellValue('H' . $row, '');
                    $active_sheet->setCellValue('I' . $row, $product->price + $option->price);
                    $active_sheet->setCellValue('J' . $row, intval($option->quantity));
                    $active_sheet->setCellValue('K' . $row, '');
                    $active_sheet->setCellValue('L' . $row, $option->status ? 1 : 0);
                    $active_sheet->setCellValue('M' . $row, $this->setImagesString($product, $option_id));
                    $active_sheet->setCellValue('N' . $row, '');
                    $active_sheet->setCellValue('O' . $row, '');
                    $active_sheet->setCellValue('P' . $row, '');
                    $active_sheet->setCellValue('Q' . $row, '');
                    $active_sheet->setCellValue('R' . $row, '');
                    $active_sheet->setCellValue('S' . $row, '');
                    $active_sheet->setCellValue('T' . $row, '');
                    $active_sheet->setCellValue('U' . $row, '');
                    $active_sheet->setCellValue('V' . $row, '');
                    $active_sheet->setCellValue('W' . $row, '');

                    $row++;
                }
            }
        }

        try {
            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . urlencode('TopTim_Excel_Export') . '"');

            $writer->save('php://output');


        } catch (\Exception $exception) {
            $job->finish(0, 0, ApiHelper::response(0, $exception->getMessage()));

            return 0;
        }

        $job->finish(1, 1, ApiHelper::response(1, 'Excel je poslan.'));

        return 1;
    }


    /**
     * @return int
     */
    public function toSimpleExcel()
    {
        $name = 'TopTim_Simple_Excel_Export';
        $job  = new Jobs();
        $job->start('cron', 'Pošalji simple excel report', '', ApiHelper::response(0, 'Nije završen'));

        //
        $spreadsheet  = new Spreadsheet();
        $active_sheet = $spreadsheet->getActiveSheet();

        $active_sheet->setTitle($name);

        for ($i = 0; $i < count($this->excel_keys); $i++) {
            $active_sheet->setCellValue($this->coordinate_letters[$i] . '1', $this->excel_keys[$i]);
        }

        $row = 2;

        $products = Product::query()
                           ->with('translation'/*, 'categories', 'subcategories', 'attributes'*/)
                           ->get();

        foreach ($products as $product) {
            /*$images = $this->setImagesString($product);
            $sizeguide_id = $this->getSizeGuideID($product);
            $attributes = $this->getAttributes($product);*/

            $active_sheet->setCellValue('A' . $row, $product->sku);
            $active_sheet->setCellValue('B' . $row, '');
            $active_sheet->setCellValue('C' . $row, $product->ean);
            $active_sheet->setCellValue('D' . $row, $product->translation->name);
            $active_sheet->setCellValue('E' . $row, $product->translation->description);
            $active_sheet->setCellValue('F' . $row, $product->translation->slug);
            $active_sheet->setCellValue('G' . $row, $product->translation->meta_title);
            $active_sheet->setCellValue('H' . $row, $product->translation->meta_description);
            $active_sheet->setCellValue('I' . $row, $product->price);
            $active_sheet->setCellValue('J' . $row, $product->quantity);
            $active_sheet->setCellValue('K' . $row, 25);
            $active_sheet->setCellValue('L' . $row, $product->status ? 1 : 0);
            $active_sheet->setCellValue('M' . $row, $product->image);
            $active_sheet->setCellValue('N' . $row, $product->brand ? $product->brand->translation->title : '');
            /*$active_sheet->setCellValue('O' . $row, $product->categories()->first() ? $product->categories()->first()->translation->title : '');
            $active_sheet->setCellValue('P' . $row, $product->subcategories()->first() ? $product->subcategories()->first()->translation->title : '');
            $active_sheet->setCellValue('Q' . $row, $sizeguide_id);
            $active_sheet->setCellValue('R' . $row, $attributes['Materijal']);
            $active_sheet->setCellValue('S' . $row, $attributes['Spol']);
            $active_sheet->setCellValue('T' . $row, $attributes['Tip rukava']);
            $active_sheet->setCellValue('U' . $row, $attributes['Kroj']);
            $active_sheet->setCellValue('V' . $row, $attributes['Dimenzije']);
            $active_sheet->setCellValue('W' . $row, $attributes['Dodatna kategorizacija']);*/

            $row++;
        }

        try {
            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . urlencode($name) . '"');

            $writer->save('php://output');

        } catch (\Exception $exception) {
            $job->finish(0, 0, ApiHelper::response(0, $exception->getMessage()));

            return 0;
        }

        $job->finish(1, 1, ApiHelper::response(1, 'Excel je poslan.'));

        return 1;
    }



    public function toCsvResponse(): StreamedResponse
    {
        @set_time_limit(0);

        $filename = 'TopTim_Export_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            // Excel-friendly UTF-8 BOM (da HR znakovi ne budu "čudni" u Excelu)
            fwrite($out, "\xEF\xBB\xBF");

            // Header
            fputcsv($out, $this->excel_keys, ';');

            $query = Product::query()
                ->with([
                    'translation',
                    'brand.translation',
                    'categories.translation',
                    'subcategories.translation',
                    'options.translation',
                    'attributes.translation',
                ])
                ->orderBy('id');

            $query->chunkById(200, function ($products) use ($out) {
                foreach ($products as $product) {
                    $tr = $product->translation;

                    // ✅ maknuto: $images = $this->setImagesString($product);
                    $sizeguide_id = $this->getSizeGuideID($product);
                    $attributes   = $this->getAttributes($product);

                    $brandTitle = $product->brand && $product->brand->translation
                        ? (string) $product->brand->translation->title
                        : '';

                    $primaryCat = $product->categories && $product->categories->count()
                        ? (string) optional($product->categories->first()->translation)->title
                        : '';

                    $secondaryCat = $product->subcategories && $product->subcategories->count()
                        ? (string) optional($product->subcategories->first()->translation)->title
                        : '';

                    // PROIZVODSKI RED
                    $row = [
                        (string) $product->sku,                 // A Šifra
                        '',                                     // B Šifra opcije
                        (string) $product->ean,                 // C Barkod
                        (string) optional($tr)->name,           // D Naziv
                        $this->stripNewlines((string) optional($tr)->description), // E Opis
                        (string) optional($tr)->slug,           // F Slug
                        (string) optional($tr)->meta_title,     // G Meta naziv
                        $this->stripNewlines((string) optional($tr)->meta_description), // H Meta opis
                        (float) $product->price,                // I Cijena
                        (int) $product->quantity,               // J Količina
                        25,                                     // K PDV
                        $product->status ? 1 : 0,               // L Aktivan

                        '',                                     // M Slike  ✅ uvijek prazno
                        (string) $brandTitle,                   // N Proizvođač
                        (string) $primaryCat,                   // O Primarna skupina
                        (string) $secondaryCat,                 // P Sekundarna skupina
                        (string) $sizeguide_id,                 // Q Tablica veličina
                        (string) ($attributes['Materijal'] ?? ''),              // R
                        (string) ($attributes['Spol'] ?? ''),                   // S
                        (string) ($attributes['Tip rukava'] ?? ''),             // T
                        (string) ($attributes['Kroj'] ?? ''),                   // U
                        (string) ($attributes['Dimenzije'] ?? ''),              // V
                        (string) ($attributes['Dodatna kategorizacija'] ?? ''), // W
                    ];

                    // osiguraj broj stupaca
                    $row = array_pad($row, count($this->excel_keys), '');

                    fputcsv($out, $row, ';');

                    // OPCIJE (varijante)
                    if ($product->options && $product->options->count() > 0) {
                        foreach ($product->options as $option) {
                            // prijevod opcije (options_translations)
                            $optTr = $option->translation ?? null;
                            $optionGroup = $optTr ? (string) $optTr->group_title : '';
                            $optionValue = $optTr ? (string) $optTr->title : '';

                            $optRow = [
                                (string) $product->sku,        // A
                                (string) $option->sku,         // B
                                '',                            // C
                                '', '', '', '', '',            // D-H
                                (float) $product->price + (float) $option->price, // I
                                (int) $option->quantity,       // J
                                '',                            // K
                                $option->status ? 1 : 0,       // L

                                '',                            // M Slike ✅ uvijek prazno
                                '', '', '', '', '', '', '', '', '', '', '' // N-W prazno
                            ];

                            // Ako si dodao 2 nova stupca u excel_keys:
                            // 'Naziv opcije', 'Vrijednost opcije'
                            $optRow[] = $optionGroup;
                            $optRow[] = $optionValue;

                            $optRow = array_pad($optRow, count($this->excel_keys), '');

                            fputcsv($out, $optRow, ';');
                        }
                    }
                }

                if (function_exists('flush')) { @flush(); }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }


    /**
     * CSV-friendly: makni nove redove (da ti ne lomi redove u CSV-u).
     */
    private function stripNewlines(string $s): string
    {
        $s = str_replace(["\r\n", "\r", "\n"], ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s) ?? '');
    }



    /**
     * @param Product $product
     *
     * @return string
     */
    public function setImagesString(Product $product, int $option_id = 0): string
    {
        $images = $product->images()->get();

        if ($images->isEmpty()) {
            return '';
        }

        // Ako tražimo sliku opcije — filtriraj po option_id
        if ($option_id) {
            $image = $images->firstWhere('option_id', $option_id);

            if ($image) {
                $product_option = ProductOption::query()
                                               ->where('product_id', $product->id)
                                               ->where(function ($subquery) use ($option_id) {
                                                   $subquery->where('option_id', $option_id)
                                                            ->orWhere('parent_id', $option_id);
                                               })
                                               ->first();

                if ($product_option) {
                    $image_temp = str_replace('.jpg', '', asset($image->image));
                    return $image_temp . '-' . $product_option->sku . '.jpg';
                }
            }

            // Ako nema slike s tim option_id — vrati prazno
            return '';
        }

        // Ako nema specificiran option_id, vrati samo slike koje nisu vezane uz opciju
        $default_images = $images->whereNull('option_id')->pluck('image')->toArray();

        if (empty($default_images)) {
            return '';
        }

        return collect($default_images)
            ->map(fn($img) => asset($img))
            ->implode($this->delimiter);
    }



    /**
     * @param Product $product
     *
     * @return string
     */
    public function getSizeGuideID(Product $product): string
    {
        $sizeguide = SizeGuide::query()->where('id', $product->sizeguide_id)->first();

        if ( ! $sizeguide) {
            return '';
        }

        return $sizeguide->translation->title;
    }


    /**
     * @param Product $product
     *
     * @return array
     */
    public function getAttributes(Product $product): array
    {
        $response['Materijal']              = '';
        $response['Spol']                   = '';
        $response['Tip rukava']             = '';
        $response['Kroj']                   = '';
        $response['Dimenzije']              = '';
        $response['Dodatna kategorizacija'] = '';

        foreach ($product->attributes()->get() as $attribute) {
            $response[$attribute->group] .= $attribute->translation->title . ',';
        }

        foreach ($product->attributes()->get() as $attribute) {
            $response[$attribute->group] = substr($response[$attribute->group], 0, -1);
        }

        return $response;
    }


    /**
     * Upload Excel/XLSX fajla i vraća apsolutnu putanju u storage.
     */
    public function upload(Request $request): ?string
    {
        if ( ! $request->hasFile('file')) {
            return null;
        }

        $file = $request->file('file');

        // Spremi u storage/app/imports/...
        $path = $file->storeAs(
            'imports',
            'toptim_export_import_' . time() . '.' . $file->getClientOriginalExtension()
        );

        Log::info('Upload file: ' . $path);

        return $path ? storage_path('app/' . $path) : null;
    }


    // =======================================================
// Export.php  —  METHODS (paste odavde naniže)
// =======================================================

    /**
     * Router payloada iz upload endpointa.
     * - 'validate-excel' => samo provjera (bez upisa u bazu).
     * - 'import-from-excel' => pravi import (create-only).
     * Napomena: images_base_dir se puni iz sessiona ili payload-a.
     */
    public function process(array $data = null, array $rows = null)
    {
        if (!$data || !$rows) {
            return ApiHelper::response(0, 'Nedostaje payload ili redovi za obradu.');
        }

        Log::info('Payload: ' . json_encode($data));
        Log::info('Session dir: ' . session('import_images_dir'));

        $this->imagesBaseDir = $data['images_dir'] ?? session('import_images_dir') ?? '';
        $this->request = $data;

        Log::info('Upload payload: ' . $this->imagesBaseDir);

        switch ($data['method'] ?? '') {
            case 'import-from-excel':
                if ($this->imagesBaseDir === '' || !is_dir($this->imagesBaseDir)) {
                    return ApiHelper::response(0,
                        "Import slika: 'images_dir' nije postavljen ili direktorij ne postoji. " .
                        "Pošalji absolute path vraćen iz upload endpointa kao parametar 'images_dir'."
                    );
                }
                return $this->importFromExcel($rows);

            case 'validate-excel':
                return $this->validateExcel($rows);
        }

        return ApiHelper::response(0, 'Nepoznata metoda.');
    }


    /**
     * IMPORT iz Excel tablice (create-only).
     * - Ako SKU već postoji -> preskoči (bez update-a).
     * - Kategorije/atributi/opcije: samo VEZANJE postojećih (bez kreiranja novih).
     * - Slike: MORAJU POSTOJATI u upload folderu; u suprotnom artikl se preskače.
     */
    private function importFromExcel(array $rows)
    {
        $createdProducts = 0;
        $skippedProducts = 0;
        $linkedCats      = 0;
        $linkedAttrs     = 0;
        $createdImgs     = 0;
        $createdOpts     = 0;
        $skippedOpts     = 0;
        $errors          = [];

        $locale = config('app.locale', 'hr');

        // 1) PROLAZ: kreiraj nove artikle + slike + kategorije + atributi
        $createdProductIdsBySku = [];

        // Excel header je u $rows[0]
        $IDX = [
            'A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,'K'=>10,'L'=>11,
            'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,'U'=>20,'V'=>21,'W'=>22
        ];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $sku         = trim((string)($row[$IDX['A']] ?? ''));
            $optionSku   = trim((string)($row[$IDX['B']] ?? ''));
            if ($sku === '' || $optionSku !== '') continue; // samo PROIZVODSKI redovi

            $ean         = trim((string)($row[$IDX['C']] ?? ''));
            $name        = (string)($row[$IDX['D']] ?? '');
            $description = (string)($row[$IDX['E']] ?? '');
            $slugIn      = (string)($row[$IDX['F']] ?? '');
            $metaTitle   = (string)($row[$IDX['G']] ?? '');
            $metaDesc    = (string)($row[$IDX['H']] ?? '');
            $priceCell   = (float)($row[$IDX['I']] ?? 0);
            $qtyCell     = (int)($row[$IDX['J']] ?? 0);
            $activeCell  = (string)($row[$IDX['L']] ?? '');
            $imagesCsv   = (string)($row[$IDX['M']] ?? '');
            $brand       = (string)($row[$IDX['N']] ?? '');
            $catPrimary  = (string)($row[$IDX['O']] ?? '');
            $catSecondary= (string)($row[$IDX['P']] ?? '');
            $attrMaterial= (string)($row[$IDX['R']] ?? '');
            $attrGender  = (string)($row[$IDX['S']] ?? '');
            $attrSleeve  = (string)($row[$IDX['T']] ?? '');
            $attrFit     = (string)($row[$IDX['U']] ?? '');
            $attrDims    = (string)($row[$IDX['V']] ?? '');
            $catsExtraCsv= (string)($row[$IDX['W']] ?? '');

            // create-only
            if (\App\Models\Back\Catalog\Product\Product::query()->where('sku', $sku)->exists()) {
                $skippedProducts++;
                continue;
            }

            try {
                DB::transaction(function () use (
                    $sku, $ean, $name, $description, $slugIn, $metaTitle, $metaDesc,
                    $priceCell, $qtyCell, $activeCell, $imagesCsv, $brand, $catPrimary, $catSecondary,
                    $catsExtraCsv, $attrMaterial, $attrGender, $attrSleeve, $attrFit, $attrDims,
                    $locale, &$createdProducts, &$createdImgs, &$linkedCats, &$linkedAttrs, &$createdProductIdsBySku
                ) {

                    $brand_id = BrandTranslation::query()->where('title', $brand)->value('brand_id') ?: 0;

                    // 1a) Kreiraj proizvod
                    /** @var \App\Models\Back\Catalog\Product\Product $product */
                    $product = \App\Models\Back\Catalog\Product\Product::query()->create([
                        'brand_id'   => $brand_id,
                        'action_id'  => 0,
                        'sku'        => $sku,
                        'ean'        => $ean ?: null,
                        'image'      => null,
                        'price'      => $priceCell ?: 0,
                        'quantity'   => $qtyCell,
                        'decrease'   => 0,
                        'tax_id'     => 0,
                        'special'    => null,
                        'special_from' => null,
                        'special_to' => null,
                        'related_products' => null,
                        'vegan'      => 0,
                        'vegetarian' => 0,
                        'glutenfree' => 0,
                        'viewed'     => 0,
                        'sort_order' => 0,
                        'push'       => 0,
                        'status'     => ($activeCell === '' ? 0 : (int)$activeCell),
                    ]);

                    // 1b) Prijevod – za početak samo produkt slug (zadnji dio URL-a),
                    // full path ćemo složiti nakon što povežemo kategorije
                    $productSlug = $slugIn ? Str::slug($slugIn) : Str::slug($name ?: $sku);

                    \App\Models\Back\Catalog\Product\ProductTranslation::query()->create([
                        'product_id'       => $product->id,
                        'lang'             => $locale,
                        'name'             => $name ?: $sku,
                        'description'      => $description ?: null,
                        'podaci'           => null,
                        'sastojci'         => null,
                        'meta_title'       => $metaTitle ?: null,
                        'meta_description' => $metaDesc ?: null,
                        'slug'             => $productSlug,
                        'url'              => $productSlug,
                        'tags'             => null,
                    ]);

                    // 1c) SLIKE — obavezno: povlačimo iz upload foldera i spremamo trajno (JPG/WEBP/THUMB) preko ProductImage::saveNew (Image::save)
                    $added = $this->initImages($product->id, $imagesCsv, $sku, $slugIn, $name);
                    $createdImgs += $added;

                    if ($added === 0) {
                        \Log::info("importFromExcel:no-base-images", [
                            'sku'       => $sku,
                            'productId' => $product->id,
                        ]);
                        // OK je – možda će opcije kasnije donijeti slike
                    }
                    /*$added = $this->initImages($product->id, $imagesCsv, $sku, $slugIn, $name);
                    if ($added === 0) {
                        // OČISTI slike ako su kojim slučajem djelomično nastale prije brisanja proizvoda
                        $imgIds = \DB::table('product_images')->where('product_id', $product->id)->pluck('id')->all();
                        if (!empty($imgIds)) {
                            \DB::table('product_images_translations')->whereIn('product_image_id', $imgIds)->delete();
                            \DB::table('product_images')->whereIn('id', $imgIds)->delete();
                        }

                        \DB::table('product_translations')->where('product_id', $product->id)->delete();
                        \DB::table('products')->where('id', $product->id)->delete();

                        throw new \RuntimeException("SKU {$sku}: nema nijedne slike u upload folderu (M-stupac ili fallback).");
                    }

                    $createdImgs += $added;*/

                    // 1d) Kategorije — samo postojeće (case-insensitive title, pa slug)
                    $linkedCats  += $this->attachExistingCategories($product->id, $catPrimary, $catSecondary, $catsExtraCsv, $locale);

                    // 1e) Atributi — samo postojeći (group_title + title)
                    $linkedAttrs += $this->attachExistingAttributes($product->id, [
                        'Materijal'  => $attrMaterial,
                        'Spol'       => $attrGender,
                        'Tip rukava' => $attrSleeve,
                        'Kroj'       => $attrFit,
                        'Dimenzije'  => $attrDims,
                    ], $locale);

                    // 1f) Nakon što smo povezali kategorije, složi FULL SLUG:
                    // hr/kategorija-proizvoda/{cat}/{subcat}/{product-slug}
                    $basePrefix = $locale . '/kategorija-proizvoda';

                    // pokupi slugove svih kategorija vezanih na proizvod (sortirano po hijerarhiji)
                    // prilagodi imena tablica / kolona po potrebi:
                    $catSlugs = \DB::table('product_category as pc')
                                   ->join('categories as c', 'c.id', '=', 'pc.category_id')
                                   ->join('category_translations as ct', 'ct.category_id', '=', 'c.id')
                                   ->where('pc.product_id', $product->id)
                                   ->where('ct.lang', $locale)
                                   ->orderBy('c.id') // ako imaš nested set; ako ne, promijeni u id ili nešto drugo
                                   ->pluck('ct.slug')
                                   ->all();

                    // ako iz Excela želiš baš primary/secondary redoslijed,
                    // možeš ovdje filtrirati/posložiti prema $catPrimary/$catSecondary,
                    // ali u praksi je dovoljno da su po lft.

                    $segments = [];
                    $segments[] = trim($basePrefix, '/');

                    foreach ($catSlugs as $cs) {
                        if ($cs) {
                            $segments[] = trim($cs, '/');
                        }
                    }

                    $segments[] = trim($productSlug, '/');

                    $fullPath = implode('/', $segments);

                    \DB::table('product_translations')
                       ->where('product_id', $product->id)
                       ->where('lang', $locale)
                       ->update([
                           'slug' => $productSlug,
                           'url'  => $fullPath,
                       ]);

                    $createdProducts++;
                    $createdProductIdsBySku[$sku] = $product->id;
                });
            } catch (\Throwable $e) {
                $errors[] = "Red " . ($i+1) . " — {$sku}: " . $e->getMessage();
                $skippedProducts++;
                continue;
            }
        }

        // 2) PROLAZ: veži OPCIJE (samo za novokreirane artikle)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $sku       = trim((string)($row[$IDX['A']] ?? ''));
            $optCode   = trim((string)($row[$IDX['B']] ?? ''));
            if ($sku === '' || $optCode === '') continue;

            $productId = $createdProductIdsBySku[$sku] ?? null;
            if (!$productId) { $skippedOpts++; continue; }

            $priceCell = (float)($row[$IDX['I']] ?? 0);
            $qtyCell   = (int)($row[$IDX['J']] ?? 0);

            // mapiraj boju/veličinu i upiši pivot (parent = "option" ako ima boja; "single" ako nema)
            try {
                [$parent, $parentId, $optionId] = $this->resolveOptionLinkage($optCode);

                if (!$optionId && !$parentId) {
                    $skippedOpts++;
                    continue;
                }

                $basePrice  = (float) \DB::table('products')->where('id', $productId)->value('price');
                $priceDelta = max(0, $priceCell - $basePrice);

                $exists = \DB::table('product_option')
                             ->where('product_id', $productId)
                             ->where('option_id', $optionId ?: 0)
                             ->where('sku', $optCode)
                             ->exists();
                if ($exists) { $skippedOpts++; continue; }

                \DB::table('product_option')->insert([
                    'product_id' => $productId,
                    'option_id'  => $optionId ?: 0,
                    'image_id'   => 0,
                    'sku'        => $optCode,
                    'parent'     => $parent,            // "option" ili "single"
                    'parent_id'  => $parentId ?: 0,     // ID boje ili 0
                    'quantity'   => $qtyCell,
                    'price'      => $priceDelta,
                    'data'       => null,
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // pokušaj naći i spremiti sliku za varijantu (…_ŠIFRAOPCIJE.*)
                $this->initImagesForOption($productId, (int)($parentId ?: 0), $optCode);

                $createdOpts++;
            } catch (\Throwable $e) {
                $errors[] = "Opcija — red " . ($i+1) . " — {$sku}/{$optCode}: " . $e->getMessage();
                $skippedOpts++;
            }
        }

        $msg = "Kreirano artikala: {$createdProducts}, preskočeno: {$skippedProducts}. "
               . "Dodano slika: {$createdImgs}, povezano kategorija: {$linkedCats}, atributa: {$linkedAttrs}. "
               . "Povezano opcija: {$createdOpts}, preskočeno opcija: {$skippedOpts}.";

        if (!empty($errors)) {
            $msg .= " Napomene: " . implode(' | ', array_slice($errors, 0, 10));
        }

        return ApiHelper::response(1, $msg);
    }

    /**
     * Spremi početni set slika za NOVI proizvod iz temp upload foldera.
     * - Kandidati su imena iz M; ako je M prazan: {slug}_{SKU}.jpg
     * - Učitamo file, pretvorimo u data-URI, i predamo ProductImage::saveNew koji poziva Image::save (radi JPG/WEBP/thumb).
     * - Vraća broj uspješno kreiranih slika.
     */
    private function initImages(int $productId, string $imagesCsv, string $sku = '', string $slugIn = '', string $name = ''): int
    {
        \Log::info('initImages:start', [
            'productId' => $productId, 'imagesCsv' => $imagesCsv, 'sku' => $sku,
            'slugIn' => $slugIn, 'name' => $name, 'imagesDir' => $this->imagesBaseDir ?? null,
        ]);

        $baseDir = $this->imagesBaseDir ?? '';
        if (!$baseDir || !is_dir($baseDir)) {
            \Log::info('initImages:baseDir-missing', ['baseDir' => $baseDir]);
            return 0;
        }

        $product = \App\Models\Back\Catalog\Product\Product::query()->find($productId);
        if (!$product) {
            \Log::info('initImages:product-not-found', ['productId' => $productId]);
            return 0;
        }

        // 1) ako imagesCsv ima vrijednosti → koristi njih
        $cands = array_values(array_filter(
            array_map('trim', explode(',', (string) $imagesCsv)),
            fn($v) => $v !== ''
        ));

        // 2) ako je prazno → probaj samo sku.jpg (NEMA više sku-* fallbacka)
        if (empty($cands) && $sku !== '') {
            $skuFile = "{$sku}.jpg";
            $absSku  = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $skuFile;

            if (is_file($absSku)) {
                $cands = [$skuFile];
            }
        }

        \Log::info('initImages:candidates-initial', ['cands' => $cands]);

        $files = [];
        foreach ($cands as $fname) {
            $abs = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fname;
            $exists = is_file($abs);
            \Log::info('initImages:probe-file', ['fname' => $fname, 'abs' => $abs, 'exists' => $exists]);
            if ($exists) {
                $files[] = $abs;
            }
        }

        if (empty($files)) {
            \Log::info('initImages:no-files-found-main', [
                'productId' => $productId,
                'sku'       => $sku,
                'imagesCsv' => $imagesCsv,
            ]);
            return 0;
        }

        \Log::info('initImages:final-files', [
            'files' => $files,
            'cands' => $cands,
        ]);

        $locale  = config('app.locale', 'hr');
        $sort    = 1;
        $created = 0;

        foreach ($files as $idx => $abs) {
            try {
                $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
                $mime = $ext === 'png'
                    ? 'image/png'
                    : ($ext === 'webp'
                        ? 'image/webp'
                        : 'image/jpeg');

                $bin = @file_get_contents($abs);
                \Log::info('initImages:file-read', [
                    'idx' => $idx, 'abs' => $abs, 'ext' => $ext, 'mime' => $mime,
                    'size' => $bin === false ? 'READ_FAIL' : strlen($bin) . 'B',
                ]);
                if ($bin === false) {
                    continue;
                }

                $imageJson  = json_encode([
                    'output' => [
                        'image' => "data:{$mime};base64," . base64_encode($bin),
                    ],
                ]);
                $imgPayload = [
                    'image'      => $imageJson,
                    'default'    => $sort === 1 ? 1 : 0,
                    'sort_order' => $sort,
                ];

                $paths   = \App\Helpers\Image::save('products', $imgPayload, $product);
                $relPath = is_array($paths)
                    ? ($paths['jpg'] ?? $paths['webp'] ?? $paths['image'] ?? $paths['path'] ?? (reset($paths) ?: null))
                    : $paths;

                if (!is_string($relPath) || $relPath === '') {
                    \Log::info('initImages:invalid-relPath', [
                        'relPath' => $relPath,
                        'type'    => gettype($paths),
                    ]);
                    continue;
                }

                $publicUrl = rtrim(config('filesystems.disks.products.url'), '/') . '/' . ltrim($relPath, '/');

                $sql = 'INSERT INTO `product_images`
                (`product_id`,`option_id`,`image`,`default`,`published`,`sort_order`,`created_at`,`updated_at`)
                VALUES (?,?,?,?,?,?,?,?)';

                $bindings = [
                    (int) $product->id,
                    null,
                    (string) $publicUrl,
                    (int) ($sort === 1 ? 1 : 0),
                    1,
                    (int) $sort,
                    now()->toDateTimeString(),
                    now()->toDateTimeString(),
                ];

                \DB::insert($sql, $bindings);
                $imageId = (int) \DB::getPdo()->lastInsertId();

                \DB::table('product_images_translations')->insert([
                    'product_image_id' => $imageId,
                    'lang'             => (string) $locale,
                    'title'            => (string) (optional($product->translation)->name ?: ($name ?: $sku)),
                    'alt'              => (string) (optional($product->translation)->name ?: ($name ?: $sku)),
                    'created_at'       => now()->toDateTimeString(),
                    'updated_at'       => now()->toDateTimeString(),
                ]);

                if ($sort === 1) {
                    \DB::table('products')
                       ->where('id', $product->id)
                       ->update([
                           'image'      => (string) $publicUrl,
                           'updated_at' => now()->toDateTimeString(),
                       ]);
                }

                $created++;
                $sort++;
            } catch (\Throwable $e) {
                \Log::info('initImages:exception', [
                    'file'  => $abs,
                    'error' => $e->getMessage(),
                    'trace' => substr($e->getTraceAsString(), 0, 2000),
                ]);
            }
        }

        \Log::info('initImages:done', ['created' => $created]);
        return $created;
    }



    private function initImagesForOption(int $productId, int $parentId, string $optCode): void
    {
        \Log::info('initImagesForOption:start', [
            'productId' => $productId,
            'parentId'  => $parentId,
            'optCode'   => $optCode,
        ]);

        $optCode = trim($optCode);
        if ($optCode === '') {
            \Log::info('initImagesForOption:empty-optCode');
            return;
        }

        /** @var \App\Models\Back\Catalog\Product\Product|null $product */
        $product = \App\Models\Back\Catalog\Product\Product::query()->find($productId);
        if (!$product) {
            \Log::info('initImagesForOption:product-not-found', ['productId' => $productId]);
            return;
        }

        // 0) Provjeri da product_option red uopće postoji
        $optionRow = \DB::table('product_option')
                        ->where('product_id', $productId)
                        ->where('sku', $optCode)
                        ->first();

        if (!$optionRow) {
            \Log::info('initImagesForOption:product_option-not-found', [
                'productId' => $productId,
                'optCode'   => $optCode,
            ]);
            return;
        }

        // Ako je već povezan image_id, ne diraj
        if (!empty($optionRow->image_id)) {
            \Log::info('initImagesForOption:already-has-image', [
                'productId' => $productId,
                'optCode'   => $optCode,
                'image_id'  => $optionRow->image_id,
            ]);
            return;
        }

        // 1) Pokušaj naći POSTOJEĆU sliku za ovaj proizvod koja sadrži optCode u URL-u
        $image = \DB::table('product_images')
                    ->where('product_id', $productId)
                    ->where('image', 'like', '%' . $optCode . '%')
                    ->orderBy('id')
                    ->first();

        // 2) Ako takve nema, probaj još malo pametnije (SKU + optCode kombinacije)
        $sku = (string) $product->sku;

        if (!$image && $sku !== '') {
            $image = \DB::table('product_images')
                        ->where('product_id', $productId)
                        ->where(function ($q) use ($sku, $optCode) {
                            $q->where('image', 'like', '%' . $sku . '_' . $optCode . '%')
                              ->orWhere('image', 'like', '%' . $sku . '-' . $optCode . '%')
                              ->orWhere('image', 'like', '%' . $sku . $optCode . '%');
                        })
                        ->orderBy('id')
                        ->first();
        }

        // 3) Ako još uvijek nema slike → pokušaj napraviti JE iz upload foldera (optCode.jpg)
        if (!$image) {
            $baseDir = $this->imagesBaseDir ?? '';
            if ($baseDir && is_dir($baseDir)) {
                $abs = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $optCode . '.jpg';

                \Log::info('initImagesForOption:probe-upload-file', [
                    'abs' => $abs,
                    'exists' => is_file($abs),
                ]);

                if (is_file($abs)) {
                    try {
                        $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
                        $mime = $ext === 'png'
                            ? 'image/png'
                            : ($ext === 'webp'
                                ? 'image/webp'
                                : 'image/jpeg');

                        $bin = @file_get_contents($abs);
                        \Log::info('initImagesForOption:file-read', [
                            'abs'  => $abs,
                            'ext'  => $ext,
                            'mime' => $mime,
                            'size' => $bin === false ? 'READ_FAIL' : strlen($bin) . 'B',
                        ]);

                        if ($bin !== false) {
                            $imageJson = json_encode([
                                'output' => [
                                    'image' => "data:{$mime};base64," . base64_encode($bin),
                                ],
                            ]);

                            // odredi sort_order (nakon postojećih slika)
                            $maxSort = (int) \DB::table('product_images')
                                                ->where('product_id', $productId)
                                                ->max('sort_order');
                            $sort = $maxSort > 0 ? $maxSort + 1 : 1;

                            $imgPayload = [
                                'image'      => $imageJson,
                                'default'    => 0,
                                'sort_order' => $sort,
                            ];

                            $paths   = \App\Helpers\Image::save('products', $imgPayload, $product);
                            $relPath = is_array($paths)
                                ? ($paths['jpg'] ?? $paths['webp'] ?? $paths['image'] ?? $paths['path'] ?? (reset($paths) ?: null))
                                : $paths;

                            if (is_string($relPath) && $relPath !== '') {
                                $publicUrl = rtrim(config('filesystems.disks.products.url'), '/') . '/' . ltrim($relPath, '/');

                                \DB::table('product_images')->insert([
                                    'product_id' => (int) $productId,
                                    'option_id'  => $parentId ?: null,
                                    'image'      => (string) $publicUrl,
                                    'default'    => 0,
                                    'published'  => 1,
                                    'sort_order' => $sort,
                                    'created_at' => now()->toDateTimeString(),
                                    'updated_at' => now()->toDateTimeString(),
                                ]);

                                $imageId = (int) \DB::getPdo()->lastInsertId();

                                $locale = config('app.locale', 'hr');
                                \DB::table('product_images_translations')->insert([
                                    'product_image_id' => $imageId,
                                    'lang'             => (string) $locale,
                                    'title'            => (string) (optional($product->translation)->name ?: ($sku ?: $optCode)),
                                    'alt'              => (string) (optional($product->translation)->name ?: ($sku ?: $optCode)),
                                    'created_at'       => now()->toDateTimeString(),
                                    'updated_at'       => now()->toDateTimeString(),
                                ]);

                                $image = (object) [
                                    'id'    => $imageId,
                                    'image' => $publicUrl,
                                ];

                                \Log::info('initImagesForOption:created-image-from-upload', [
                                    'productId' => $productId,
                                    'optCode'   => $optCode,
                                    'image_id'  => $imageId,
                                    'image'     => $publicUrl,
                                ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        \Log::info('initImagesForOption:exception-on-upload', [
                            'file'  => $abs,
                            'error' => $e->getMessage(),
                            'trace' => substr($e->getTraceAsString(), 0, 2000),
                        ]);
                    }
                }
            }
        }

        // 4) Ako ni nakon svega nemamo sliku → odustani
        if (!$image) {
            \Log::info('initImagesForOption:no-matching-image', [
                'productId' => $productId,
                'optCode'   => $optCode,
                'sku'       => $sku ?? null,
            ]);
            return;
        }

        // 5) Veži sliku na opciju
        \DB::table('product_option')
           ->where('product_id', $productId)
           ->where('sku', $optCode)
           ->update([
               'image_id'   => (int) $image->id,
               'updated_at' => now(),
           ]);

        // 6) Ako proizvod NEMA glavnu sliku, postavi ovu kao glavnu (default),
        // ali bez ikakvog dupiranja fajlova – samo prebacimo flag i products.image
        $prodRow = \DB::table('products')
                      ->select('image')
                      ->where('id', $productId)
                      ->first();

        if ($prodRow && (empty($prodRow->image) || $prodRow->image === null)) {
            \Log::info('initImagesForOption:set-product-main-image-from-option', [
                'productId' => $productId,
                'optCode'   => $optCode,
                'image_id'  => $image->id,
                'image'     => $image->image ?? null,
            ]);

            // makni postojeće defaulte
            \DB::table('product_images')
               ->where('product_id', $productId)
               ->update(['default' => 0]);

            \DB::table('product_images')
               ->where('id', $image->id)
               ->update([
                   'default'    => 1,
                   'updated_at' => now()->toDateTimeString(),
               ]);

            \DB::table('products')
               ->where('id', $productId)
               ->update([
                   'image'      => $image->image,
                   'updated_at' => now()->toDateTimeString(),
               ]);
        }
    }


    /**
     * Ako postoji slika specifična za opciju, uploada je kao dodatnu sliku proizvoda,
     * i (ako postoji stupac) povezuje s product_option.image_id.
     * Traži datoteku koja završava na "_{ŠIFRAOPCIJE}.(jpg|jpeg|png|webp)" u upload folderu.
     */
//    private function initImagesForOption(int $productId, int $optionIdOrParentId, string $optionCode): int
//    {
//        \Log::info('initImagesForOption:start', [
//            'productId'  => $productId,
//            'parentId'   => $optionIdOrParentId,
//            'optionCode' => $optionCode,
//            'imagesDir'  => $this->imagesBaseDir ?? null,
//        ]);
//
//        $baseDir = $this->imagesBaseDir ?? '';
//        if (!$baseDir || !is_dir($baseDir)) {
//            \Log::info('initImagesForOption:baseDir-missing', ['baseDir' => $baseDir]);
//            return 0;
//        }
//
//        /** @var \App\Models\Back\Catalog\Product\Product|null $product */
//        $product = \App\Models\Back\Catalog\Product\Product::query()->find($productId);
//        if (!$product) {
//            \Log::info('initImagesForOption:product-not-found', ['productId' => $productId]);
//            return 0;
//        }
//
//        // --- 1) Pripremi "jako normalizirane" igle (needles) iz optionCode-a ---
//        $normalizeStrong = function (string $s): string {
//            // ukloni ekstenziju ako je zadana
//            $s = preg_replace('/\.[^.]+$/', '', $s);
//            // sve separatore u ništa, sve u lower
//            $s = mb_strtolower($s, 'UTF-8');
//            $s = preg_replace('/[^A-Za-z0-9]+/u', '', $s);
//            return $s ?? '';
//        };
//
//        // varijante igle
//        $needlesRaw = [
//            $optionCode,                                  // "LEG00009-2-S/M"
//            str_replace('/', '-', $optionCode),           // "LEG00009-2-S-M"
//            str_replace('/', '',  $optionCode),           // "LEG00009-2-SM"
//        ];
//
//        // dodaj i varijantu s basename-om opcije (zadnji segment nakon '/')
//        $lastSeg = str_contains($optionCode, '/')
//            ? substr($optionCode, strrpos($optionCode, '/') + 1)
//            : $optionCode;
//        $needlesRaw[] = $lastSeg;                         // "S/M"
//        $needlesRaw[] = str_replace('/', '-', $lastSeg);  // "S-M"
//        $needlesRaw[] = str_replace('/', '',  $lastSeg);  // "SM"
//        $needlesRaw = array_values(array_unique($needlesRaw));
//
//        $needles = array_map($normalizeStrong, $needlesRaw);
//        $needles = array_values(array_unique(array_filter($needles, fn($v) => $v !== '')));
//
//        \Log::info('initImagesForOption:needles', ['raw' => $needlesRaw, 'normalized' => $needles]);
//
//        // --- 2) Brzi exact pokušaji (nested i flat) prije rekurzije ---
//        $exts = ['jpg','jpeg','png','webp'];
//        $tryPaths = [];
//        foreach ($exts as $e) {
//            $tryPaths[] = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $optionCode) . '.' . $e;   // nested
//            $tryPaths[] = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', '-', $optionCode) . '.' . $e;                   // flat s '-'
//            $tryPaths[] = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', '',  $optionCode) . '.' . $e;                   // flat bez '/'
//        }
//
//        $matchedAbs = null;
//        foreach ($tryPaths as $p) {
//            if (is_file($p)) { $matchedAbs = $p; break; }
//        }
//        \Log::info('initImagesForOption:fast-exact', ['matched' => (bool)$matchedAbs, 'path' => $matchedAbs]);
//
//        // --- 3) Rekurzivno pretraživanje: uspoređuj RELATIVNI STEM s needle-ovima (strong norm) ---
//        $scanned = 0;
//        $hits = 0;
//        if (!$matchedAbs) {
//            try {
//                $it = new \RecursiveIteratorIterator(
//                    new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
//                    \RecursiveIteratorIterator::SELF_FIRST
//                );
//                foreach ($it as $file) {
//                    /** @var \SplFileInfo $file */
//                    if (!$file->isFile()) continue;
//                    $ext = strtolower($file->getExtension());
//                    if (!in_array($ext, $exts, true)) continue;
//
//                    $full = $file->getRealPath();
//                    // relativni path unutar baseDir-a
//                    $rel  = ltrim(str_replace(rtrim($baseDir, DIRECTORY_SEPARATOR), '', $full), DIRECTORY_SEPARATOR);
//                    // bez ekstenzije, sa separatorima normaliziranim u '/'
//                    $relStem = preg_replace('/\.[^.]+$/', '', $rel);
//                    $relStem = str_replace(DIRECTORY_SEPARATOR, '/', $relStem);
//
//                    $normRel = $normalizeStrong($relStem);
//                    $scanned++;
//
//                    if (in_array($normRel, $needles, true)) {
//                        $matchedAbs = $full;
//                        $hits++;
//                        break;
//                    }
//                }
//            } catch (\Throwable $e) {
//                \Log::info('initImagesForOption:recursive-ex', ['err' => $e->getMessage()]);
//            }
//        }
//
//        \Log::info('initImagesForOption:scan-stats', ['scanned' => $scanned, 'hits' => $hits, 'matched' => (bool)$matchedAbs, 'path' => $matchedAbs]);
//        if (!$matchedAbs) {
//            // za debug – ispiši prvih ~30 file-ova (relativno) da vidimo što je unutra
//            try {
//                $list = [];
//                $it2 = new \DirectoryIterator($baseDir);
//                foreach ($it2 as $fi) {
//                    if ($fi->isDot()) continue;
//                    $list[] = $fi->getFilename();
//                    if (count($list) >= 30) break;
//                }
//                \Log::info('initImagesForOption:dir-sample', ['baseDir' => $baseDir, 'sample' => $list]);
//            } catch (\Throwable $e) {
//                // noop
//            }
//            return 0;
//        }
//
//        // --- 4) Resolvaj option_id ---
//        $optionId = $optionIdOrParentId ?: null;
//        if (!$optionId) {
//            $opt = \DB::table('options')->where('option_sku', $optionCode)->first();
//            $optionId = $opt ? (int)$opt->id : null;
//        }
//        \Log::info('initImagesForOption:resolved-optionId', ['optionId' => $optionId]);
//
//        // --- 5) Spremi sliku i upiši u DB ---
//        try {
//            $ext  = strtolower(pathinfo($matchedAbs, PATHINFO_EXTENSION));
//            $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
//            $bin  = @file_get_contents($matchedAbs);
//            if ($bin === false) {
//                \Log::info('initImagesForOption:file-read-failed', ['abs' => $matchedAbs]);
//                return 0;
//            }
//
//            $imageJson  = json_encode(['output' => ['image' => "data:{$mime};base64," . base64_encode($bin)]]);
//            $imgPayload = ['image' => $imageJson, 'default' => 0, 'sort_order' => 999, 'option_id' => $optionId];
//
//            $paths   = \App\Helpers\Image::save('products', $imgPayload, $product);
//            $relPath = is_array($paths)
//                ? ($paths['jpg'] ?? $paths['webp'] ?? $paths['image'] ?? $paths['path'] ?? (reset($paths) ?: null))
//                : $paths;
//
//            if (!is_string($relPath) || $relPath === '') {
//                \Log::info('initImagesForOption:invalid-relPath', ['relPath' => $relPath, 'type' => gettype($paths)]);
//                return 0;
//            }
//            $publicUrl = rtrim(config('filesystems.disks.products.url'), '/') . '/' . ltrim($relPath, '/');
//
//            // RAW insert u product_images
//            $sql = 'INSERT INTO `product_images`
//                (`product_id`,`option_id`,`image`,`default`,`published`,`sort_order`,`created_at`,`updated_at`)
//                VALUES (?,?,?,?,?,?,?,?)';
//            $bindings = [
//                (int)$product->id,
//                $optionId ? (int)$optionId : null,
//                (string)$publicUrl,
//                0,
//                1,
//                999,
//                now()->toDateTimeString(),
//                now()->toDateTimeString(),
//            ];
//            \DB::insert($sql, $bindings);
//            $imageId = (int)\DB::getPdo()->lastInsertId();
//
//            // RUČNI insert prijevoda
//            $locale = config('app.locale', 'hr');
//            \DB::table('product_images_translations')->insert([
//                'product_image_id' => $imageId,
//                'lang'             => (string)$locale,
//                'title'            => (string)(optional($product->translation)->name ?: $product->sku),
//                'alt'              => (string)(optional($product->translation)->name ?: $product->sku),
//                'created_at'       => now()->toDateTimeString(),
//                'updated_at'       => now()->toDateTimeString(),
//            ]);
//
//            // LINK: po product_id + sku (i option_id ako postoji)
//            $q = \DB::table('product_option')->where('product_id', $productId)->where('sku', $optionCode);
//            if ($optionId) $q->where('option_id', $optionId);
//            $updated = $q->update(['image_id' => $imageId, 'updated_at' => now()->toDateTimeString()]);
//            \Log::info('initImagesForOption:link-product_option', ['updated_rows' => $updated, 'image_id' => $imageId, 'publicUrl' => $publicUrl]);
//
//            return 1;
//        } catch (\Throwable $e) {
//            \Log::info('initImagesForOption:exception', [
//                'file' => $matchedAbs, 'error' => $e->getMessage(),
//                'trace' => substr($e->getTraceAsString(), 0, 2000),
//            ]);
//            return 0;
//        }
//    }



    /**
     * Minimalna validacija headera + obaveznih slika za svaki PROIZVOD (B prazno).
     * Vrati [bool $ok, array $messages] — poruke su greške ili upozorenja.
     */
    private function validateExcelStructure(array $rows): array
    {
        $errors = [];
        $headerExpected = [
            'Šifra', 'Šifra opcije', 'Barkod', 'Naziv', 'Opis', 'Slug',
            'Meta naziv', 'Meta opis', 'Cijena', 'Količina', 'PDV', 'Aktivan',
            'Slike', 'Proizvođač', 'Primarna skupina', 'Sekundarna skupina',
            'Tablica veličina', 'Materijal', 'Spol', 'Tip rukava', 'Kroj',
            'Dimenzije', 'Dodatna kategorizacija',
        ];

        $header = array_map('trim', $rows[0] ?? []);
        $missing = array_diff($headerExpected, $header);
        if (!empty($missing)) {
            $errors[] = 'Nedostaju stupci: ' . implode(', ', $missing);
            return [false, $errors];
        }

        $IDX = [
            'A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,'K'=>10,'L'=>11,
            'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,'U'=>20,'V'=>21,'W'=>22
        ];

        $baseDir = $this->imagesBaseDir ?? '';
        if (!$baseDir || !is_dir($baseDir)) {
            $errors[] = "Upload folder slika nije postavljen — prvo učitaj ZIP/slike.";
            return [false, $errors];
        }

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $sku = trim((string)($row[$IDX['A']] ?? ''));
            $opt = trim((string)($row[$IDX['B']] ?? ''));

            if ($sku === '') {
                $errors[] = "Red " . ($i+1) . ": A(SKU) je prazan.";
                continue;
            }
            if (strlen($sku) > 14) {
                $errors[] = "Red " . ($i+1) . ": A(SKU) > 14 znakova.";
            }

            // Samo proizvodski redovi moraju imati dostupnu sliku u upload folderu
            if ($opt === '') {
                $imagesCsv = (string)($row[$IDX['M']] ?? '');
                $name      = (string)($row[$IDX['D']] ?? '');
                $slugIn    = (string)($row[$IDX['F']] ?? '');

                $cands = array_filter(array_map('trim', explode(',', $imagesCsv)));
                if (empty($cands)) {
                    $slug = $slugIn ? \Illuminate\Support\Str::slug($slugIn) : \Illuminate\Support\Str::slug($name ?: $sku);
                    $cands = ["{$slug}_{$sku}.jpg"];
                }

                $found = false;
                foreach ($cands as $f) {
                    $abs = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $f;
                    if (is_file($abs)) { $found = true; break; }
                }
                if (!$found) {
                    $errors[] = "Red " . ($i+1) . ": nijedna slika iz M-stupca ne postoji u upload folderu (traženo: " . implode(', ', $cands) . ").";
                }
            }
        }

        return [count($errors) === 0, $errors];
    }

    /**
     * Pomoćna: mapira excel opcijski kod u parent/option id-eve iz tablice options.
     * Vraća [string $parent, ?int $parentId, ?int $optionId]
     * - parent = "option" ako ima boja (group='boja'), inače "single"
     */
    private function resolveOptionLinkage(string $excelCode): array
    {
        // Primjeri: "33-L", "ZS00000-33-l", "XL", "106-3XS"
        $code = trim($excelCode);
        $parent   = 'single';
        $parentId = null;
        $optionId = null;

        $parts = array_values(array_filter(explode('-', $code), fn($p) => $p !== ''));

        // Ukloni leading SKU proizvoda ako je prisutan (npr. SKU-boja-veličina)
        // Ovo je tolerantno — boja je prvi, veličina zadnji dio.
        if (count($parts) >= 3) {
            array_shift($parts);
        }

        $colorTok = null;
        $sizeTok  = null;

        if (count($parts) === 1) {
            $sizeTok = strtoupper(trim($parts[0]));
        } elseif (count($parts) >= 2) {
            $colorTok = trim($parts[0]);
            $sizeTok  = strtoupper(trim(end($parts)));
        }

        // Boja (group='boja') -> parent
        if ($colorTok) {
            $color = \DB::table('options')->where('group', 'boja')->where('option_sku', $colorTok)->first();
            if ($color) {
                $parent   = 'option';
                $parentId = (int)$color->id;
            }
        }

        // Veličina (group='velicina') -> option_id
        if ($sizeTok) {
            $size = \DB::table('options')->where('group', 'velicina')->where('option_sku', $sizeTok)->first();
            if ($size) {
                $optionId = (int)$size->id;
            }
        }

        // Fallback: ako nema veličine, koristi boju kao option_id (da postoji veza)
        if (!$optionId && $parentId) {
            $optionId = $parentId;
        }

        return [$parent, $parentId, $optionId];
    }


    /**
     * Veže nov proizvod na postojeće kategorije:
     *  - prvo traži po category_translations.title (case-insensitive),
     *  - ako ne nađe, pokuša po slug-u.
     */
    /**
     * Povezuje proizvod na postojeće kategorije (po naslovu ili slugu) i,
     * ako nađena kategorija ima parent_id != 0, veže i roditelja.
     *
     * @return int Broj novoupisanih redaka u product_category
     */
    private function attachExistingCategories(
        int $productId,
        string $primary,
        string $secondary,
        string $extraCsv,
        string $locale
    ): int {
        $linked = 0;

        // 1) PRIMARY
        $primaryId = null;
        if (trim($primary) !== '') {
            $cat = $this->findCategoryByTitle(trim($primary), $locale, null);
            if ($cat) {
                $primaryId = (int)$cat->category_id;
                $linked   += $this->attachCategoryWithParent($productId, $primaryId);
            }
        }

        // 2) SECONDARY - prvo pokušaj kao dijete PRIMARY-ja
        if (trim($secondary) !== '') {
            $secondaryTitle = trim($secondary);
            $secondaryCat   = null;

            if ($primaryId) {
                // traži Čarape ispod Trening (ili Nogomet, ovisno što je primary)
                $secondaryCat = $this->findCategoryByTitle($secondaryTitle, $locale, $primaryId);
            }

            // fallback: ako nema pod tim parentom, traži globalno
            if (!$secondaryCat) {
                $secondaryCat = $this->findCategoryByTitle($secondaryTitle, $locale, null);
            }

            if ($secondaryCat) {
                $secondaryId = (int)$secondaryCat->category_id;
                $linked     += $this->attachCategoryWithParent($productId, $secondaryId);
            }
        }

        // 3) EXTRAS – generički, kao što si već imao (bez parent logike)
        $extras = collect(array_filter(array_map(
            static fn($s) => trim($s),
            explode(',', (string)$extraCsv)
        )));

        foreach ($extras as $ex) {
            if ($ex === '') {
                continue;
            }
            $cat = $this->findCategoryByTitle($ex, $locale, null);
            if ($cat) {
                $linked += $this->attachCategoryWithParent($productId, (int)$cat->category_id);
            }
        }

        return $linked;
    }


    private function findCategoryByTitle(string $title, string $locale, ?int $expectedParentId = null): ?stdClass
    {
        $lower = mb_strtolower($title);
        $slug  = Str::slug($title);

        $query = DB::table('category_translations as ct')
                   ->join('categories as c', 'c.id', '=', 'ct.category_id')
                   ->where('ct.lang', $locale);

        if ($expectedParentId !== null) {
            $query->where('c.parent_id', $expectedParentId);
        }

        // 1) točan title (case-insensitive)
        $cat = (clone $query)
            ->whereRaw('LOWER(ct.title) = ?', [$lower])
            ->select('ct.category_id', 'c.parent_id')
            ->first();

        // 2) slug
        if (!$cat) {
            $cat = (clone $query)
                ->where('ct.slug', $slug)
                ->select('ct.category_id', 'c.parent_id')
                ->first();
        }

        // 3) fallback bez lang (ali i dalje po parentu, ako je zadano)
        if (!$cat) {
            $query2 = DB::table('category_translations as ct')
                        ->join('categories as c', 'c.id', '=', 'ct.category_id');

            if ($expectedParentId !== null) {
                $query2->where('c.parent_id', $expectedParentId);
            }

            $cat = (clone $query2)
                ->whereRaw('LOWER(ct.title) = ?', [$lower])
                ->select('ct.category_id', 'c.parent_id')
                ->first();

            if (!$cat) {
                $cat = (clone $query2)
                    ->where('ct.slug', $slug)
                    ->select('ct.category_id', 'c.parent_id')
                    ->first();
            }
        }

        return $cat ?: null;
    }


    private function attachCategoryWithParent(int $productId, int $categoryId): int
    {
        $linked = 0;

        $parentId = (int) (DB::table('categories')
                             ->where('id', $categoryId)
                             ->value('parent_id') ?? 0);

        // osnovna kat.
        $existsMain = DB::table('product_category')
                        ->where('product_id', $productId)
                        ->where('category_id', $categoryId)
                        ->exists();

        if (!$existsMain) {
            DB::table('product_category')->insert([
                'product_id'  => $productId,
                'category_id' => $categoryId,
            ]);
            $linked++;
        }

        // parent
        if ($parentId > 0) {
            $existsParent = DB::table('product_category')
                              ->where('product_id', $productId)
                              ->where('category_id', $parentId)
                              ->exists();

            if (!$existsParent) {
                DB::table('product_category')->insert([
                    'product_id'  => $productId,
                    'category_id' => $parentId,
                ]);
                $linked++;
            }
        }

        return $linked;
    }



    /**
     * Povezuje postojeće atribute na proizvod.
     * Map primjer:
     *   ['Materijal' => '94% Poliester, 6% Elastin', 'Veličina' => 'S|M|L']
     *
     * Pravila:
     * - NE reže po zarezu ako string sadrži postotke (tretira cijeli kao jednu vrijednost).
     * - Inače dijeli po | ili ;  (zarez samo ako nema postotaka).
     * - Match radi robustno (lowercase, bez dijakritike, kolaps non-alnum).
     * - Logira cijeli tok (parsiranje, matchane ID-ove, inserte).
     */
    private function attachExistingAttributes(int $productId, array $map, string $locale): int
    {
        Log::info('attr:START', ['productId' => $productId, 'locale' => $locale, 'groups' => array_keys($map)]);

        // Normalizacije
        $collapseSpace = static function (string $s): string {
            return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
        };
        $stripDiacritics = static function (string $s): string {
            // Str::ascii će skinuti dijakritiku
            return Str::ascii($s);
        };
        $normSoft = static function (string $s) use ($collapseSpace, $stripDiacritics): string {
            $s = $collapseSpace($s);
            $s = mb_strtolower($s, 'UTF-8');
            $s = $stripDiacritics($s);
            return $s;
        };
        $normStrong = static function (string $s) use ($normSoft): string {
            $s = $normSoft($s);
            // makni sve što nije slovo ili broj
            $s = preg_replace('/[^a-z0-9]+/i', '', $s) ?? '';
            return $s;
        };

        $collectIds = [];

        foreach ($map as $groupTitle => $csvValues) {
            $groupTitle = (string)$groupTitle;
            $raw = $collapseSpace((string)$csvValues);

            if ($raw === '') {
                Log::info('attr:skip-empty-values', ['group' => $groupTitle]);
                continue;
            }

            // heuristika za parsiranje vrijednosti
            $hasPercent = (bool) preg_match('/\d+\s*%/u', $raw);
            if (strpos($raw, '|') !== false || strpos($raw, ';') !== false) {
                $parts = preg_split('/[|;]+/u', $raw);
            } elseif (!$hasPercent && strpos($raw, ',') !== false) {
                $parts = preg_split('/,+/u', $raw);
            } else {
                $parts = [$raw];
            }

            $values = collect($parts)
                ->map(fn($v) => $collapseSpace((string)$v))
                ->filter(fn($v) => $v !== '')
                ->unique()
                ->values();

            Log::info('attr:parsed', [
                'groupRaw' => $groupTitle,
                'values'   => $values->all(),
                'raw'      => $raw,
                'hasPercent' => $hasPercent
            ]);

            // Dohvati sve prijevode za danu grupu (prvo po traženom lang-u, fallback bez langa)
            $gSoft = $normSoft($groupTitle);
            $groupRows = DB::table('attributes_translations')
                           ->select('attribute_id', 'group_title', 'title', 'lang')
                           ->whereRaw('LOWER(group_title) = ?', [$gSoft])
                           ->where('lang', $locale)
                           ->get();

            if ($groupRows->isEmpty()) {
                $groupRows = DB::table('attributes_translations')
                               ->select('attribute_id', 'group_title', 'title', 'lang')
                               ->whereRaw('LOWER(group_title) = ?', [$gSoft])
                               ->get();
            }

            Log::info('attr:group-candidates', [
                'group'   => $groupTitle,
                'locale'  => $locale,
                'rows'    => $groupRows->count()
            ]);

            if ($groupRows->isEmpty()) {
                Log::info('attr:group-not-found', ['group' => $groupTitle]);
                continue;
            }

            // Izgradi lookup mapu titleNorm => attribute_id
            $byNormTitle = [];
            foreach ($groupRows as $r) {
                $key = $normStrong((string)$r->title);
                if ($key !== '') {
                    // preferiraj prvi (ako ima duplikata naslova u istoj grupi)
                    $byNormTitle[$key] = (int)$r->attribute_id;
                }
            }

            // Matchaj svaku vrijednost
            foreach ($values as $valueTitle) {
                $needleStrong = $normStrong($valueTitle);
                $needleSoft   = $normSoft($valueTitle);

                $aid = $byNormTitle[$needleStrong] ?? null;

                // Ako nema strong match, probaj soft "contains" (edge slučajevi s postocima/spojnicima)
                if (!$aid) {
                    foreach ($byNormTitle as $normKey => $attrId) {
                        // ako su skoro isti po soft normi (npr. razlika samo u razmacima)
                        if ($normKey === $needleSoft || str_contains($normKey, $needleStrong) || str_contains($needleStrong, $normKey)) {
                            $aid = $attrId;
                            break;
                        }
                    }
                }

                Log::info('attr:match-attempt', [
                    'group'        => $groupTitle,
                    'value'        => $valueTitle,
                    'needleStrong' => $needleStrong,
                    'needleSoft'   => $needleSoft,
                    'found'        => (bool)$aid,
                    'attribute_id' => $aid
                ]);

                if ($aid) {
                    $collectIds[] = (int)$aid;
                } else {
                    // Za lakši debug — pokaži top 5 kandidata iz baze za ovu grupu
                    $sample = [];
                    $i = 0;
                    foreach ($byNormTitle as $k => $v) {
                        $sample[] = ['normTitle' => $k, 'attribute_id' => $v];
                        if (++$i >= 5) break;
                    }
                    Log::info('attr:no-match-debug', [
                        'group'  => $groupTitle,
                        'value'  => $valueTitle,
                        'sample' => $sample
                    ]);
                }
            }
        }

        $ids = array_values(array_unique($collectIds));
        if (empty($ids)) {
            Log::info('attr:END', ['productId' => $productId, 'inserted' => 0, 'reason' => 'no-ids-collected']);
            return 0;
        }

        // Insert u pivot (preskoči postojeće)
        $rows = [];
        foreach ($ids as $aid) {
            $exists = DB::table('product_attribute')
                        ->where('product_id', $productId)
                        ->where('attribute_id', $aid)
                        ->exists();

            if (!$exists) {
                $rows[] = ['product_id' => $productId, 'attribute_id' => $aid];
            }
        }

        if (!empty($rows)) {
            DB::table('product_attribute')->insert($rows);
        }

        Log::info('attr:END', [
            'productId'    => $productId,
            'totalFound'   => count($ids),
            'insertedRows' => count($rows),
            'skippedDupes' => count($ids) - count($rows),
            'ids'          => $ids,
        ]);

        return count($rows);
    }


}
