<?php

namespace App\Models\Back\Settings\Api;

use App\Helpers\ApiHelper;
use App\Helpers\Csv;
use App\Mail\akmkSendReport;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Jobs;
use App\Models\Back\Orders\Order;
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
     * @var string[]
     */
    protected $excel_keys = ['Šifra artikla',
                              'Naziv artikla',
                              'Maloprodajna cijena',
                              'Veleprodajna cijena',
                              'Nabavna cijena',
                              'Novčana valuta',
                              'Jedinica mjere',
                              'Stopa PDV-a',
                              'Bar kod',
                              'Kataloški broj', // J
                              'Šifra dobavljača',
                              'Dobavljač',
                              'Vrsta artikla',
                              'Primarna skupina artikala',
                              'Promjena cijene je dopuštena',
                              'Promjena opisa je dopuštena', //P
                              'Dopuštena promjena stope PDV-a',
                              'Artikal je raspoloživ (aktivan)',
                              'Uporaba serijskih brojeva',
                              'Opis artikla', // T
                              'Sekundarna skupina artikala',
                              'Marka', // V
                              'Konto za knjiž. prihoda od prodaje domaćim kupcima',
                              'Konto za knjiž. prihoda od prodaje u treće zemlje',
                              'Konto za knjiženje nabave',
                              'MT', // Z
                             //
                              'Težina artikla', // AA
                              'Zaliha se vodi po LOT brojevima',
                              'Minimalna zaliha',
                              'Jamstveni rok',
                              'Količina pakiranja',
                              'Uporaba cjenika',
                              'Blokiraj dodavanje na otpremnicu ako nema zalihe na skladištu',
                              'Blokiraj dodavanje na otpremnicu ako nema zalihe ni na jednom skladištu',
                              'Dobavljačeva cijena',
                              'Postotak VP marže',
                              'Iznos VP marže',
                              'Postotak MP marže',
                              'Iznos MP marže',
                              'Iznos PDV-a',
                              'Država porijekla', // O
                              'Preuzeta lokacija u skladištu',
                              'Povratna naknada',
                              'Konto za knjiž. prihoda od prodaje u EU',
                              'Širina artikla',
                              'Visina artikla',
                              'Dubina artikla',
                              'Carinska tarifa',
                              'Vrsta prodaje - općenito',
                              'Vrsta prodaje za domaće PDV obveznike (kupce)',
                              'Vrsta prodaje za PDV obveznike (kupce) iz EU',
                              'Vrsta prodaje za potrošače u EU', // Z
                             //
                              'Vrsta prodaje za kupce izvan EU',
                              'Interni naziv',
                              'Vidljivost u online trgovini',
                              'Internetska tržnica (Više trgovina razdvaja se zarezom)',
                              'URL web stranice artikla',
                              'Komercijalno tehnični uvjeti ili druge napomene koje će se ispisati na dnu dokumenta',
                              'Zaliha se vodi po VIN brojevima',
                              'Naziv artikla na eng. jeziku',
                              'Opis artikla na eng. jeziku',
                              'Naziv artikla na njem. jeziku',
                              'Opis artikla na njem. jeziku',
                              'Naziv artikla na tal. jeziku',
                              'Opis artikla na tal. jeziku',
                              'Naziv artikla na slo. jeziku',
                              'Opis artikla na slo. jeziku',
                              'Naziv artikla na mađ. jeziku',
                              'Opis artikla na mađ. jeziku'
    ];

    protected $coordinate_letters = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ'
    ];


    /**
     * @return int
     */
    public function toExcel()
    {
        //dd(collect($this->excel_keys)->count(), count($this->coordinate_letters));

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

        //
        $products = Product::query()->with('translation')->get();
        
        foreach ($products as $product) {
            $active_sheet->setCellValue('A' . $row, $product->sku);
            $active_sheet->setCellValue('B' . $row, $product->translation->name);
            $active_sheet->setCellValue('C' . $row, $product->price);
            $active_sheet->setCellValue('D' . $row, $product->price);
            $active_sheet->setCellValue('E' . $row, $product->price);
            $active_sheet->setCellValue('F' . $row, 'EUR');
            $active_sheet->setCellValue('G' . $row, 'kom');
            $active_sheet->setCellValue('H' . $row, '25');
            $active_sheet->setCellValue('I' . $row, '');
            $active_sheet->setCellValue('J' . $row, '');
            $active_sheet->setCellValue('K' . $row, '');
            $active_sheet->setCellValue('L' . $row, '');
            $active_sheet->setCellValue('M' . $row, '');
            $active_sheet->setCellValue('N' . $row, '');
            $active_sheet->setCellValue('O' . $row, '');
            $active_sheet->setCellValue('P' . $row, '');
            $active_sheet->setCellValue('Q' . $row, '');
            $active_sheet->setCellValue('R' . $row, $product->status);
            $active_sheet->setCellValue('S' . $row, '');
            $active_sheet->setCellValue('T' . $row, $product->translation->description);
            $active_sheet->setCellValue('U' . $row, '');
            $active_sheet->setCellValue('V' . $row, $product->brand ? $product->brand->translation->title : '');
            $active_sheet->setCellValue('W' . $row, '');
            $active_sheet->setCellValue('X' . $row, '');
            $active_sheet->setCellValue('Y' . $row, '');
            $active_sheet->setCellValue('Z' . $row, '');
            //
            $active_sheet->setCellValue('AA' . $row, '');
            $active_sheet->setCellValue('AB' . $row, '');
            $active_sheet->setCellValue('AC' . $row, '');
            $active_sheet->setCellValue('AD' . $row, '');
            $active_sheet->setCellValue('AE' . $row, '');
            $active_sheet->setCellValue('AF' . $row, '');
            $active_sheet->setCellValue('AG' . $row, '');
            $active_sheet->setCellValue('AH' . $row, '');
            $active_sheet->setCellValue('AI' . $row, '');
            $active_sheet->setCellValue('AJ' . $row, '');
            $active_sheet->setCellValue('AK' . $row, '');
            $active_sheet->setCellValue('AL' . $row, '');
            $active_sheet->setCellValue('AM' . $row, '');
            $active_sheet->setCellValue('AN' . $row, '');
            $active_sheet->setCellValue('AO' . $row, '');
            $active_sheet->setCellValue('AP' . $row, '');
            $active_sheet->setCellValue('AQ' . $row, '');
            $active_sheet->setCellValue('AR' . $row, '');
            $active_sheet->setCellValue('AS' . $row, '');
            $active_sheet->setCellValue('AT' . $row, '');
            $active_sheet->setCellValue('AU' . $row, '');
            $active_sheet->setCellValue('AV' . $row, '');
            $active_sheet->setCellValue('AW' . $row, '');
            $active_sheet->setCellValue('AX' . $row, '');
            $active_sheet->setCellValue('AY' . $row, '');
            $active_sheet->setCellValue('AZ' . $row, '');
            //
            $active_sheet->setCellValue('BA' . $row, '');
            $active_sheet->setCellValue('BB' . $row, '');
            $active_sheet->setCellValue('BC' . $row, $product->status);
            $active_sheet->setCellValue('BD' . $row, '');
            $active_sheet->setCellValue('BE' . $row, url($product->translation->url));
            $active_sheet->setCellValue('BF' . $row, '');
            $active_sheet->setCellValue('BG' . $row, '');
            $active_sheet->setCellValue('BH' . $row, '');
            $active_sheet->setCellValue('BI' . $row, '');
            $active_sheet->setCellValue('BJ' . $row, '');
            $active_sheet->setCellValue('BK' . $row, '');
            $active_sheet->setCellValue('BL' . $row, '');
            $active_sheet->setCellValue('BM' . $row, '');
            $active_sheet->setCellValue('BN' . $row, '');
            $active_sheet->setCellValue('BO' . $row, '');
            $active_sheet->setCellValue('BP' . $row, '');
            $active_sheet->setCellValue('BQ' . $row, '');
            
            $row++;

            if ($product->options()->count() > 0) {
                foreach ($product->options()->get() as $option) {
                    $active_sheet->setCellValue('A' . $row, $product->sku . '-' . $option->sku);
                    $active_sheet->setCellValue('B' . $row, $product->translation->name . ', ' . $option->option->translation->group_title . ': ' . $option->option->translation->title);
                    $active_sheet->setCellValue('C' . $row, $product->price + $option->price);
                    $active_sheet->setCellValue('D' . $row, $product->price + $option->price);
                    $active_sheet->setCellValue('E' . $row, $product->price + $option->price);
                    $active_sheet->setCellValue('F' . $row, 'EUR');
                    $active_sheet->setCellValue('G' . $row, 'kom');
                    $active_sheet->setCellValue('H' . $row, '25');
                    $active_sheet->setCellValue('I' . $row, '');
                    $active_sheet->setCellValue('J' . $row, '');
                    $active_sheet->setCellValue('K' . $row, '');
                    $active_sheet->setCellValue('L' . $row, '');
                    $active_sheet->setCellValue('M' . $row, '');
                    $active_sheet->setCellValue('N' . $row, '');
                    $active_sheet->setCellValue('O' . $row, '');
                    $active_sheet->setCellValue('P' . $row, '');
                    $active_sheet->setCellValue('Q' . $row, '');
                    $active_sheet->setCellValue('R' . $row, $option->status);
                    $active_sheet->setCellValue('S' . $row, '');
                    $active_sheet->setCellValue('T' . $row, $product->translation->description);
                    $active_sheet->setCellValue('U' . $row, '');
                    $active_sheet->setCellValue('V' . $row, $product->brand ? $product->brand->translation->title : '');
                    $active_sheet->setCellValue('W' . $row, '');
                    $active_sheet->setCellValue('X' . $row, '');
                    $active_sheet->setCellValue('Y' . $row, '');
                    $active_sheet->setCellValue('Z' . $row, '');
                    //
                    $active_sheet->setCellValue('AA' . $row, '');
                    $active_sheet->setCellValue('AB' . $row, '');
                    $active_sheet->setCellValue('AC' . $row, '');
                    $active_sheet->setCellValue('AD' . $row, '');
                    $active_sheet->setCellValue('AE' . $row, '');
                    $active_sheet->setCellValue('AF' . $row, '');
                    $active_sheet->setCellValue('AG' . $row, '');
                    $active_sheet->setCellValue('AH' . $row, '');
                    $active_sheet->setCellValue('AI' . $row, '');
                    $active_sheet->setCellValue('AJ' . $row, '');
                    $active_sheet->setCellValue('AK' . $row, '');
                    $active_sheet->setCellValue('AL' . $row, '');
                    $active_sheet->setCellValue('AM' . $row, '');
                    $active_sheet->setCellValue('AN' . $row, '');
                    $active_sheet->setCellValue('AO' . $row, '');
                    $active_sheet->setCellValue('AP' . $row, '');
                    $active_sheet->setCellValue('AQ' . $row, '');
                    $active_sheet->setCellValue('AR' . $row, '');
                    $active_sheet->setCellValue('AS' . $row, '');
                    $active_sheet->setCellValue('AT' . $row, '');
                    $active_sheet->setCellValue('AU' . $row, '');
                    $active_sheet->setCellValue('AV' . $row, '');
                    $active_sheet->setCellValue('AW' . $row, '');
                    $active_sheet->setCellValue('AX' . $row, '');
                    $active_sheet->setCellValue('AY' . $row, '');
                    $active_sheet->setCellValue('AZ' . $row, '');
                    //
                    $active_sheet->setCellValue('BA' . $row, '');
                    $active_sheet->setCellValue('BB' . $row, '');
                    $active_sheet->setCellValue('BC' . $row, $option->status);
                    $active_sheet->setCellValue('BD' . $row, '');
                    $active_sheet->setCellValue('BE' . $row, url($product->translation->url));
                    $active_sheet->setCellValue('BF' . $row, '');
                    $active_sheet->setCellValue('BG' . $row, '');
                    $active_sheet->setCellValue('BH' . $row, '');
                    $active_sheet->setCellValue('BI' . $row, '');
                    $active_sheet->setCellValue('BJ' . $row, '');
                    $active_sheet->setCellValue('BK' . $row, '');
                    $active_sheet->setCellValue('BL' . $row, '');
                    $active_sheet->setCellValue('BM' . $row, '');
                    $active_sheet->setCellValue('BN' . $row, '');
                    $active_sheet->setCellValue('BO' . $row, '');
                    $active_sheet->setCellValue('BP' . $row, '');
                    $active_sheet->setCellValue('BQ' . $row, '');

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


}
