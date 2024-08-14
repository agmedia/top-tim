<?php

namespace App\Models\Back\Settings\Api;

use App\Helpers\ApiHelper;
use App\Helpers\Csv;
use App\Helpers\Helper;
use App\Helpers\Import;
use App\Helpers\ProductHelper;
use App\Helpers\Query;
use App\Mail\akmkSendReport;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Back\Jobs;
use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\Settings;
use App\Models\Back\TempTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    protected $excel_keys = ['Šifra artikla', 'Naziv artikla', 'Maloprodajna cijena', 'Veleprodajna cijena', 'Nabavna cijena', 'Novčana valuta', 'Jedinica mjere', 'Stopa PDV-a', 'Bar kod', 'Kataloški broj', 'Šifra dobavljača', 'Dobavljač', 'Vrsta artikla', 'Opis artikla', 'Marka'];

    protected $coordinate_letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];


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
            $active_sheet->setCellValue('I' . $row, $product->ean);
            $active_sheet->setCellValue('J' . $row, $product->ean);
            $active_sheet->setCellValue('K' . $row, '');
            $active_sheet->setCellValue('L' . $row, '');
            $active_sheet->setCellValue('M' . $row, '');
            $active_sheet->setCellValue('N' . $row, $product->translation->description);
            $active_sheet->setCellValue('O' . $row, $product->brand ? $product->brand->translation->title : '');
            
            $row++;
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
     * @return string
     */
    public function sendExcelReport()
    {
        $job = new Jobs();
        $job->start('cron', 'Pošalji excel report', '', ApiHelper::response(0, 'Nije završen'));

        $orders = Order::query()->whereDate('created_at', today()->subDay())->with('products')->get();
        $products = collect();

        if ($orders->count()) {
            foreach ($orders as $order) {
                foreach ($order->products as $product) {
                    $products->push($product);
                }
            }

            $products->groupBy('product_id')->all();
        }

        $to_send = [];

        foreach ($products->groupBy('product_id')->all() as $group) {
            $qty = 0;

            foreach ($group as $product) {
                $qty += $product->quantity;
            }

            $to_send[] = [
                'sku' => $product->product->sku,
                'isbn' => $product->product->isbn,
                'title' => $product->product->name,
                'quantity' => $qty,
            ];
        }

        try {
            $csv = new Csv();
            $csv->createExcelFile('akmk_report.xlsx', $to_send, $this->excel_keys);

            dispatch(function () {
                Mail::to('aleksandar@aleksandarpavlovski.com')->send(new akmkSendReport());
            });

        } catch (\Exception $exception) {
            $job->finish(0, 0, ApiHelper::response(0, $exception->getMessage()));

            return 0;
        }

        $job->finish(1, 1, ApiHelper::response(1, 'Excel je poslan.'));

        return 1;
    }

}
