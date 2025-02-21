<?php

namespace App\Models\Back\Settings\Api;

use App\Helpers\ApiHelper;
use App\Helpers\Csv;
use App\Mail\akmkSendReport;
use App\Models\Back\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Jobs;
use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\SizeGuide;
use Illuminate\Support\Facades\Mail;
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
        $spreadsheet = new Spreadsheet();
        $active_sheet = $spreadsheet->getActiveSheet();

        $active_sheet->setTitle('TopTim_Export');

        for ($i = 0; $i < count($this->excel_keys); $i++) {
            $active_sheet->setCellValue($this->coordinate_letters[$i] . '1', $this->excel_keys[$i]);
        }

        $row = 2;

        $products = Product::query()
                           ->with('translation', 'images', 'categories', 'subcategories', 'options', 'attributes')
                           ->take(200)
                           ->get();

        foreach ($products as $product) {
            $images = $this->setImagesString($product);
            $sizeguide_id = $this->getSizeGuideID($product);
            $attributes = $this->getAttributes($product);

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
                    $sku = $option->option->option_sku;

                    if ($option->parent_id) {
                        $sku = $option->top->option_sku . '-' . $sku;
                    }

                    $active_sheet->setCellValue('A' . $row, $product->sku);
                    $active_sheet->setCellValue('B' . $row, $sku);
                    $active_sheet->setCellValue('C' . $row, '');
                    $active_sheet->setCellValue('D' . $row, '');
                    $active_sheet->setCellValue('E' . $row, '');
                    $active_sheet->setCellValue('F' . $row, '');
                    $active_sheet->setCellValue('G' . $row, '');
                    $active_sheet->setCellValue('H' . $row, '');
                    $active_sheet->setCellValue('I' . $row, $product->price + $option->price);
                    $active_sheet->setCellValue('J' . $row, intval($option->quantity));
                    $active_sheet->setCellValue('K' . $row, '');
                    $active_sheet->setCellValue('L' . $row, '');
                    $active_sheet->setCellValue('M' . $row, '');
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
            header('Content-Disposition: attachment; filename="'. urlencode('TopTim_Excel_Export').'"');

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
    public function setImagesString(Product $product): string
    {
        $count = $product->images()->count();
        $iterator = 1;

        if ( ! $count) {
            return '';
        }

        $string = '';
        foreach ($product->images()->get() as $image) {
            if ($image->option_id) {
                $option = Options::query()->find($image->option_id);

                $image_temp = str_replace('.jpg', '', asset($image->image));
                $image_temp = $image_temp . '-' . $option->option_sku . '.jpg';

            } else {
                $image_temp = asset($image->image);
            }

            $string .= $image_temp;

            if ($count > 1 && $iterator < $count) {
                $string .= $this->delimiter;
            }
        }

        return $string;
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
        $response['Materijal'] = '';
        $response['Spol'] = '';
        $response['Tip rukava'] = '';
        $response['Kroj'] = '';
        $response['Dimenzije'] = '';
        $response['Dodatna kategorizacija'] = '';

        foreach ($product->attributes()->get() as $attribute) {
            $response[$attribute->group] .= $attribute->translation->title . ',';
        }

        foreach ($product->attributes()->get() as $attribute) {
            $response[$attribute->group] = substr($response[$attribute->group], 0, -1);
        }

        return $response;
    }
}
