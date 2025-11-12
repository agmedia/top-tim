<?php

namespace App\Models\Back\Settings\Api;

use App\Helpers\ApiHelper;
use App\Helpers\Csv;
use App\Mail\akmkSendReport;
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
    ];

    protected $coordinate_letters = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'
    ];


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
                           ->whereIn('id', [2674,2682,2691,2694])
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


    /**
     * Router koji prima $data (payload) i $rows (redovi iz Xlsx-a),
     * pa poziva metodu za IMPORT (kreiranje novih artikala).
     */
    public function process(array $data = null, array $rows = null)
    {
        if ( ! $data || ! $rows) {
            return ApiHelper::response(0, 'Nedostaje payload ili redovi za import.');
        }

        $this->request = $data;

        Log::info('Import data: ' . json_encode($data));

        switch ($data['method'] ?? '') {
            case 'import-from-excel':
                return $this->importFromExcel($rows);
        }

        return ApiHelper::response(0, 'Nepoznata metoda za import.');
    }



    /**
     * IMPORT iz Excel tablice koju generira toExcel().
     *
     * Pravila:
     * - Ako PROIZVOD (SKU u stupcu A) već postoji -> PRESKOČI (nema ažuriranja).
     * - Kreiramo SAMO nove proizvode i njihove prijevode.
     * - Vežemo na postojeće kategorije/attribute/opcije (ako postoje).
     * - Ne kreiramo NOVE kategorije/attribute/opcije; ako ih ne nađemo -> preskočimo vezu.
     *
     * Očekivani raspored stupaca:
     * A Šifra (sku) | B Šifra opcije | C Barkod | D Naziv | E Opis | F Slug
     * G Meta naziv | H Meta opis | I Cijena | J Količina | K PDV | L Aktivan
     * M Slike (zarezom) | N Proizvođač (ne diramo) | O Primarna skupina | P Sekundarna skupina
     * Q Tablica veličina (ne diramo) | R Materijal | S Spol | T Tip rukava | U Kroj | V Dimenzije
     * W Dodatna kategorizacija (zarezom)
     */

    /**
     * IMPORT iz Excel tablice (create-only).
     * - Ako SKU već postoji -> preskoči (bez update-a).
     * - Vežemo samo na postojeće kategorije/atribute/opcije (bez kreiranja novih).
     */
    private function importFromExcel(array $rows)
    {
        [$valid, $errors] = $this->validateExcelStructure($rows);
        if (!$valid) {
            return ApiHelper::response(0, "Excel tablica nije ispravna:\n" . implode("\n", $errors));
        }


        $createdProducts = 0;
        $skippedProducts = 0;
        $linkedCats      = 0;
        $linkedAttrs     = 0;
        $createdImgs     = 0;

        $locale = config('app.locale', 'hr');
        $createdProductIdsBySku = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) continue;

            $sku       = trim((string)($row[0] ?? '')); // A
            $optionSku = trim((string)($row[1] ?? '')); // B
            if ($sku === '' || $optionSku !== '') continue; // samo glavni artikli

            $ean         = trim((string)($row[2] ?? ''));
            $name        = (string)($row[3] ?? '');
            $description = (string)($row[4] ?? '');
            $slugIn      = (string)($row[5] ?? '');
            $metaTitle   = (string)($row[6] ?? '');
            $metaDesc    = (string)($row[7] ?? '');
            $priceCell   = (float)($row[8] ?? 0);
            $qtyCell     = (int)($row[9] ?? 0);
            $activeCell  = (string)($row[11] ?? '');
            $imagesCsv   = (string)($row[12] ?? '');
            $catPrimary  = (string)($row[14] ?? '');
            $catSecondary = (string)($row[15] ?? '');
            $attrMaterial = (string)($row[17] ?? '');
            $attrGender   = (string)($row[18] ?? '');
            $attrSleeve   = (string)($row[19] ?? '');
            $attrFit      = (string)($row[20] ?? '');
            $attrDims     = (string)($row[21] ?? '');
            $catsExtraCsv = (string)($row[22] ?? '');

            if (Product::query()->where('sku', $sku)->exists()) {
                $skippedProducts++;
                continue;
            }

            DB::transaction(function () use (
                $sku,
                $ean,
                $name,
                $description,
                $slugIn,
                $metaTitle,
                $metaDesc,
                $priceCell,
                $qtyCell,
                $activeCell,
                $imagesCsv,
                $catPrimary,
                $catSecondary,
                $catsExtraCsv,
                $attrMaterial,
                $attrGender,
                $attrSleeve,
                $attrFit,
                $attrDims,
                $locale,
                &$createdProducts,
                &$createdImgs,
                &$linkedCats,
                &$linkedAttrs,
                &$createdProductIdsBySku
            ) {
                $product = Product::query()->create([
                    'brand_id' => 0,
                    'action_id' => 0,
                    'sku' => $sku,
                    'ean' => $ean ?: null,
                    'price' => $priceCell ?: 0,
                    'quantity' => $qtyCell,
                    'tax_id' => 0,
                    'status' => ($activeCell === '' ? 0 : (int)$activeCell),
                ]);

                $slug = $slugIn ? Str::slug($slugIn) : Str::slug($name ?: $sku);

                ProductTranslation::query()->create([
                    'product_id' => $product->id,
                    'lang' => $locale,
                    'name' => $name ?: $sku,
                    'description' => $description ?: null,
                    'meta_title' => $metaTitle ?: null,
                    'meta_description' => $metaDesc ?: null,
                    'slug' => $slug,
                    'url' => $slug,
                ]);

                $createdImgs += $this->initImages($product->id, $imagesCsv);
                $linkedCats += $this->attachExistingCategories($product->id, $catPrimary, $catSecondary, $catsExtraCsv, $locale);
                $linkedAttrs += $this->attachExistingAttributes($product->id, [
                    'Materijal'  => $attrMaterial,
                    'Spol'       => $attrGender,
                    'Tip rukava' => $attrSleeve,
                    'Kroj'       => $attrFit,
                    'Dimenzije'  => $attrDims,
                ], $locale);

                $createdProducts++;
                $createdProductIdsBySku[$sku] = $product->id;
            });
        }

        [$createdOpts, $skippedOpts, $optErrors] = array_values(
            $this->attachOptionsFromExcel($rows, $createdProductIdsBySku)
        );

        $msg = "Kreirano artikala: {$createdProducts}, preskočeno: {$skippedProducts}. "
               . "Slike: {$createdImgs}, Kategorije: {$linkedCats}, Atributi: {$linkedAttrs}, "
               . "Opcije dodane: {$createdOpts}, preskočene: {$skippedOpts}.";

        if (!empty($optErrors)) {
            $msg .= " Napomene: " . implode(' | ', array_slice($optErrors, 0, 5));
        }

        return ApiHelper::response(1, $msg);
    }


    /**
     * Provjerava ispravnost Excel tablice prije importa.
     *
     * Vraća [bool $valid, array $errors]
     * - Ako je $valid = false → import se ne smije pokrenuti.
     * - $errors sadrži opis svih problema (ograničeno na 50 redova).
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

        // 1) Provjeri header
        $header = array_map('trim', $rows[0] ?? []);
        $missingHeaders = array_diff($headerExpected, $header);
        if (!empty($missingHeaders)) {
            $errors[] = 'Nedostaju stupci: ' . implode(', ', $missingHeaders);
            return [false, $errors];
        }

        // 2) Mapiraj indekse stupaca (da ne ovisimo o redoslijedu)
        $col = array_flip($header);

        $seenSkus = [];
        $valid = true;

        foreach ($rows as $i => $row) {
            if ($i === 0) continue;

            $sku       = trim((string)($row[$col['Šifra']] ?? ''));
            $optionSku = trim((string)($row[$col['Šifra opcije']] ?? ''));
            $price     = (string)($row[$col['Cijena']] ?? '');
            $qty       = (string)($row[$col['Količina']] ?? '');
            $images    = trim((string)($row[$col['Slike']] ?? ''));

            // Red broj u Excelu (1-based)
            $rowNum = $i + 1;

            // === Osnovne provjere ===
            if ($sku === '') {
                $errors[] = "Red {$rowNum}: nedostaje Šifra (SKU).";
                $valid = false;
                continue;
            }

            if (isset($seenSkus[$sku]) && $optionSku === '') {
                $errors[] = "Red {$rowNum}: ponovljen glavni SKU '{$sku}'.";
                $valid = false;
            }
            $seenSkus[$sku] = true;

            // Ako je opcijski red (ima Šifra opcije)
            if ($optionSku !== '') {
                if (!Str::startsWith($optionSku, $sku . '-')) {
                    $errors[] = "Red {$rowNum}: Šifra opcije '{$optionSku}' ne pripada SKU '{$sku}'.";
                    $valid = false;
                }

                if ($price === '' || !is_numeric($price)) {
                    $errors[] = "Red {$rowNum}: neispravna cijena '{$price}'.";
                    $valid = false;
                }
                if ($qty === '' || !is_numeric($qty)) {
                    $errors[] = "Red {$rowNum}: neispravna količina '{$qty}'.";
                    $valid = false;
                }
            }

            // Ako je glavni red (nema Šifra opcije)
            if ($optionSku === '') {
                if ($price === '' || !is_numeric($price)) {
                    $errors[] = "Red {$rowNum}: neispravna cijena glavnog artikla '{$price}'.";
                    $valid = false;
                }
                /*if ($images === '') {
                    $errors[] = "Red {$rowNum}: glavni proizvod '{$sku}' nema sliku.";
                    $valid = false;
                }*/
            }

            // === Dodatne logičke provjere ===
            /*if ($optionSku && $images && !Str::contains($images, $optionSku)) {
                $errors[] = "Red {$rowNum}: slika '{$images}' ne sadrži kod opcije '{$optionSku}'.";
                $valid = false;
            }*/
        }

        if (count($errors) > 50) {
            $errors = array_slice($errors, 0, 50);
            $errors[] = '...';
        }

        return [$valid, $errors];
    }


    /**
     * Drugi prolaz: veže OPCIJE iz Excela uz tek kreirane artikle.
     * - očekuje mapu $createdProductIdsBySku koju puniš u prvom prolazu
     * - NE kreira nove opcije; veže samo postojeće
     */
    private function attachOptionsFromExcel(array $rows, array $createdProductIdsBySku): array
    {
        $createdOpts = 0;
        $skippedOpts = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) continue;

            $sku = trim((string)($row[0] ?? ''));
            $optCode = trim((string)($row[1] ?? ''));
            if ($sku === '' || $optCode === '') continue;

            $productId = $createdProductIdsBySku[$sku] ?? null;
            if (!$productId) { $skippedOpts++; continue; }

            $priceCell = (float)($row[8] ?? 0);
            $qtyCell = (int)($row[9] ?? 0);
            $imagesCsv = (string)($row[12] ?? '');

            try {
                [$colorTok, $sizeTok] = $this->parseExcelOptionCode($optCode, $sku);

                $colorId = $colorTok ? $this->findOptionId('boja', $colorTok) : null;
                $sizeId  = $sizeTok  ? $this->findOptionId('velicina', $sizeTok) : null;

                if (!$colorId && !$sizeId) { $skippedOpts++; continue; }

                $parentId = $colorId ?: 0;
                $optionIdForPivot = $sizeId ?: $colorId;
                $parentType = $colorId ? 'option' : 'single';

                $basePrice = (float) DB::table('products')->where('id', $productId)->value('price');
                $priceDelta = max(0, $priceCell - $basePrice);

                $exists = DB::table('product_option')
                            ->where('product_id', $productId)
                            ->where('option_id', $optionIdForPivot)
                            ->where('sku', $optCode)
                            ->exists();

                if ($exists) { $skippedOpts++; continue; }

                DB::table('product_option')->insert([
                    'product_id' => $productId,
                    'option_id'  => $optionIdForPivot,
                    'image_id'   => 0,
                    'sku'        => $optCode,
                    'parent'     => $parentType,
                    'parent_id'  => $parentId,
                    'quantity'   => $qtyCell,
                    'price'      => $priceDelta,
                    'data'       => json_encode([]),
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdOpts++;

                // Dodaj sliku za opciju (ako postoji)
                if ($imagesCsv && $parentId) {
                    $this->initImagesForOption($productId, $parentId, $imagesCsv, $optCode);
                }

            } catch (\Throwable $e) {
                $errors[] = "Red #{$i} ({$sku}/{$optCode}): " . $e->getMessage();
                $skippedOpts++;
            }
        }

        return compact('createdOpts', 'skippedOpts', 'errors');
    }


    /**
     * Parsira kod iz Excela (stupac B) u [colorToken|null, sizeToken|null].
     *
     * Primjeri koje podržavamo:
     *  - "33-L"           → ['33', 'L']
     *  - "ZS00000-33-l"   → ['33', 'l']  (ignoriramo leading SKU)
     *  - "XL"             → [null, 'XL'] (nema boje → single)
     *  - "106-3XS"        → ['106', '3XS']
     */
    private function parseExcelOptionCode(string $code, string $productSku): array
    {
        $code = trim($code);
        if ($code === '') return [null, null];

        $parts = array_values(array_filter(explode('-', $code), fn($p) => $p !== ''));

        if (count($parts) === 1) {
            // samo veličina
            return [null, strtoupper($parts[0])];
        }

        if (count($parts) >= 2) {
            // Ako je prvi dio jednak SKU-u proizvoda, preskoči ga (čest slučaj "SKU-boja-velicina")
            if (Str::startsWith($code, $productSku . '-')) {
                $parts = array_slice($parts, 1);
            }
            // Pretpostavka: prvi preostali dio je boja (brojčani ili string kod), zadnji je veličina
            $colorToken = trim($parts[0]);
            $sizeToken  = strtoupper(trim(end($parts))); // veličina normalizirana
            return [$colorToken, $sizeToken];
        }

        return [null, null];
    }

    /**
     * Pronalazi ID opcije u tablici `options` za zadanu grupu i token.
     * - Za grupu 'boja' token je obično broj (npr. "33") i točno se mapira na options.option_sku = '33'
     * - Za grupu 'velicina' token je npr. "L", "XL", "3XS"… (pokušavamo option_sku i title prijevoda)
     *
     * Vraća: int|null (options.id)
     */
    private function findOptionId(string $group, string $token): ?int
    {
        $tokenNorm = trim($token);
        if ($tokenNorm === '') return null;

        // 1) exact match na options.option_sku unutar grupe
        $opt = DB::table('options')
                 ->where('group', $group)
                 ->where('option_sku', $tokenNorm)
                 ->first();

        if ($opt) return (int)$opt->id;

        // 2) pokušaj preko prijevoda (options_translations.title), case-insensitive
        $opt = DB::table('options')
                 ->join('options_translations as ot', 'ot.option_id', '=', 'options.id')
                 ->where('options.group', $group)
                 ->whereRaw('LOWER(ot.title) = ?', [mb_strtolower($tokenNorm)])
                 ->select('options.id')
                 ->first();

        if ($opt) return (int)$opt->id;

        // 3) fallback za veličine: ponekad su u option_sku bez/sa kosom crtom; pokušaj grublje
        if ($group === 'velicina') {
            $opt = DB::table('options')
                     ->where('group', $group)
                     ->where(function($q) use ($tokenNorm) {
                         $q->where('option_sku', 'LIKE', $tokenNorm)
                           ->orWhere('option_sku', 'LIKE', '%' . $tokenNorm . '%');
                     })
                     ->orderBy('sort_order')
                     ->first();
            if ($opt) return (int)$opt->id;
        }

        return null;
    }



    /**
     * Inicijalni set slika za novi proizvod.
     * Prva slika postaje default i upiše se u products.image.
     */
    private function initImages(int $productId, string $imagesCsv): int
    {
        $images = collect(array_filter(array_map('trim', explode(',', $imagesCsv))))
            ->reject(fn($img) => Str::contains($img, '-')); // samo default slike

        if ($images->isEmpty()) return 0;

        $sort = 1;
        $first = null;

        foreach ($images as $img) {
            DB::table('product_images')->insert([
                'product_id' => $productId,
                'image'      => $img,
                'default'    => $sort === 1 ? 1 : 0,
                'published'  => 1,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $first = $first ?: $img;
        }

        if ($first) {
            DB::table('products')->where('id', $productId)->update(['image' => $first]);
        }

        return $images->count();
    }


    private function initImagesForOption(int $productId, int $parentId, string $imagesCsv, string $optCode): int
    {
        $images = collect(array_filter(array_map('trim', explode(',', $imagesCsv))));
        if ($images->isEmpty()) return 0;

        $matched = $images->first(fn($img) => Str::contains($img, $optCode . '.jpg'));
        if (!$matched) return 0;

        $exists = DB::table('product_images')
                    ->where('product_id', $productId)
                    ->where('option_id', $parentId)
                    ->where('image', $matched)
                    ->exists();

        if (!$exists) {
            DB::table('product_images')->insert([
                'product_id' => $productId,
                'option_id'  => $parentId, // ključ: koristi parent_id iz product_option
                'image'      => $matched,
                'default'    => 1,
                'published'  => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return 1;
    }


    /**
     * Veže nov proizvod na postojeće kategorije:
     *  - prvo traži po category_translations.title (case-insensitive),
     *  - ako ne nađe, pokuša po slug-u.
     */
    private function attachExistingCategories(int $productId, string $primary, string $secondary, string $extraCsv, string $locale): int
    {
        $titles = collect();

        if (trim($primary) !== '') {
            $titles->push(trim($primary));
        }
        if (trim($secondary) !== '') {
            $titles->push(trim($secondary));
        }

        $extras = collect(array_filter(array_map(fn($s) => trim($s), explode(',', (string) $extraCsv))));
        foreach ($extras as $ex) {
            $titles->push($ex);
        }

        $titles = $titles->unique()->values();
        if ($titles->isEmpty()) {
            return 0;
        }

        $linked = 0;

        foreach ($titles as $title) {
            $lower = mb_strtolower($title);

            $cat = DB::table('category_translations')
                     ->whereRaw('LOWER(title) = ?', [$lower])
                     ->select('category_id')
                     ->first();

            if ( ! $cat) {
                $slug = Str::slug($title);
                $cat  = DB::table('category_translations')
                          ->where('slug', $slug)
                          ->select('category_id')
                          ->first();
            }

            if ( ! $cat) {
                continue;
            }

            $exists = DB::table('product_category')
                        ->where('product_id', $productId)
                        ->where('category_id', $cat->category_id)
                        ->exists();

            if ($exists) {
                continue;
            }

            DB::table('product_category')->insert([
                'product_id'  => $productId,
                'category_id' => $cat->category_id,
            ]);

            $linked++;
        }

        return $linked;
    }


    /**
     * Veže nov proizvod na postojeće atribute.
     * Traži attribute_id preko attributes_translations (group_title + title), case-insensitive.
     */
    private function attachExistingAttributes(int $productId, array $map, string $locale): int
    {
        $ids = [];

        foreach ($map as $groupTitle => $csvValues) {
            $values = collect(array_filter(array_map(fn($s) => trim($s), explode(',', (string) $csvValues))))
                ->unique()
                ->values();

            foreach ($values as $valueTitle) {
                $g = mb_strtolower($groupTitle);
                $t = mb_strtolower($valueTitle);

                $attrTr = DB::table('attributes_translations')
                            ->whereRaw('LOWER(group_title) = ?', [$g])
                            ->whereRaw('LOWER(title) = ?', [$t])
                            ->select('attribute_id')
                            ->first();

                if ($attrTr) {
                    $ids[] = (int) $attrTr->attribute_id;
                }
            }
        }

        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return 0;
        }

        $rows = [];
        foreach ($ids as $aid) {
            $exists = DB::table('product_attribute')
                        ->where('product_id', $productId)
                        ->where('attribute_id', $aid)
                        ->exists();
            if ($exists) {
                continue;
            }

            $rows[] = [
                'product_id'   => $productId,
                'attribute_id' => $aid,
            ];
        }

        if ( ! empty($rows)) {
            DB::table('product_attribute')->insert($rows);
        }

        return count($rows);
    }

}
