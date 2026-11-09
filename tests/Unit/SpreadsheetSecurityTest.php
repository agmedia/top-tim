<?php

namespace Tests\Unit;

use App\Helpers\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SpreadsheetSecurityTest extends TestCase
{
    public function test_normal_xlsx_import_still_reads_cells(): void
    {
        $path = $this->workbook();
        try {
            $this->assertSame('Import check', (new Csv($path, 'Xlsx'))->getCsv()->getCell('A1')->getValue());
        } finally {
            unlink($path);
        }
    }

    public function test_xlsx_external_dtd_scanner_bypass_is_rejected(): void
    {
        $path = $this->workbook();
        $dtd = tempnam(sys_get_temp_dir(), 'top-tim-security-dtd-');
        file_put_contents($dtd, '<!ENTITY audit "LOCAL_TEST_SENTINEL">');

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path));
            $xml = $zip->getFromName('xl/workbook.xml');
            $xml = preg_replace('/\?>/', '?><!DOCTYPE workbook SYSTEM "file://' . $dtd . '">', $xml, 1);
            $xml = str_replace('encoding="UTF-8"', 'encoding="UCS-4BE"', $xml);
            $zip->addFromString('xl/workbook.xml', mb_convert_encoding($xml, 'UTF-32BE', 'UTF-8'));
            $zip->close();

            $this->expectException(Exception::class);
            new Csv($path, 'Xlsx');
        } finally {
            unlink($path);
            unlink($dtd);
        }
    }

    private function workbook(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'top-tim-security-xlsx-');
        $sheet = new Spreadsheet();
        $sheet->getActiveSheet()->setCellValue('A1', 'Import check');
        (new Xlsx($sheet))->save($path);
        $sheet->disconnectWorksheets();

        return $path;
    }
}
