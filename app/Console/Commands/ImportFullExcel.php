<?php

namespace App\Console\Commands;

use App\Helpers\ApiHelper;
use App\Helpers\Csv;
use App\Mail\ImportFullExcelFinished;
use App\Models\Back\Jobs;
use App\Models\Back\Settings\Api\Export;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ImportFullExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:full-excel
                            {file : Absolute path to uploaded Excel file}
                            {--images_dir= : Absolute path to uploaded images directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run full product Excel import in background (CLI).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filePath  = (string) $this->argument('file');
        $imagesDir = (string) $this->option('images_dir');

        $job = new Jobs();
        $job->start('cron', 'Import Full Excel', '', ApiHelper::response(0, 'Nije završen'));

        try {
            if ($filePath === '' || !is_file($filePath)) {
                throw new \RuntimeException('Excel datoteka ne postoji ili nije dostupna.');
            }

            if ($imagesDir === '' || !is_dir($imagesDir)) {
                throw new \RuntimeException("Import slika: 'images_dir' nije postavljen ili direktorij ne postoji.");
            }

            $excel  = new Csv($filePath, 'Xlsx');
            $export = new Export();
            $result = $export->process([
                'target' => 'export-excel',
                'method' => 'import-from-excel',
                'images_dir' => $imagesDir
            ], $excel->csv->toArray());
            $report = $export->getLastImportReport();

            if (!$result) {
                $response = ApiHelper::response(0, 'Import nije izvršen.');
                $job->finish(0, $this->buildJobPayload(false, $filePath, $report, 'Import nije izvršen.'), $response);
                $this->sendImportFinishedEmail(false, 'Import nije izvršen.', $filePath);
                return Command::FAILURE;
            }

            $response = is_string($result) ? $result : ApiHelper::response(1, 'Import je završen.');
            $job->finish(1, $this->buildJobPayload(true, $filePath, $report), $response);
            $this->sendImportFinishedEmail(true, $response, $filePath);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('ImportFullExcel command failed', [
                'file' => $filePath,
                'images_dir' => $imagesDir,
                'error' => $e->getMessage(),
            ]);

            $response = ApiHelper::response(0, $e->getMessage());
            $job->finish(0, $this->buildJobPayload(false, $filePath, [], $e->getMessage()), $response);
            $this->sendImportFinishedEmail(false, $e->getMessage(), $filePath);

            return Command::FAILURE;
        } finally {
            if ($filePath !== '' && is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }


    private function sendImportFinishedEmail(bool $success, string $summary, string $filePath): void
    {
        try {
            $to = (string) config('mail.from.address');

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Log::warning('ImportFullExcel email skipped - invalid MAIL_FROM_ADDRESS', ['address' => $to]);
                return;
            }

            Mail::to($to)->send(new ImportFullExcelFinished($success, $summary, $filePath));
        } catch (\Throwable $e) {
            Log::error('ImportFullExcel email notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }


    private function buildJobPayload(bool $success, string $filePath, array $report = [], string $error = ''): string
    {
        $payload = [
            'kind' => 'import_full_excel_report',
            'success' => $success,
            'file_path' => $filePath,
            'error' => $error,
            'summary' => (string) ($report['summary'] ?? ''),
            'counts' => $report['counts'] ?? [],
            'detail_counts' => $report['detail_counts'] ?? [],
            'details' => $report['details'] ?? [],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '';
    }
}
