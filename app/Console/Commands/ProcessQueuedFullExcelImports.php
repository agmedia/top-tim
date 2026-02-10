<?php

namespace App\Console\Commands;

use App\Models\Back\Jobs;
use Illuminate\Console\Command;

class ProcessQueuedFullExcelImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:queued-full-excel-imports {--limit=1 : Number of queued imports to process per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process queued full-excel imports stored in jobs table.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = max(1, (int) $this->option('limit'));

        $queued = Jobs::query()
            ->where('type', 'cron_queue')
            ->where('target', 'Import Full Excel')
            ->where('send_report', 1)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($queued->isEmpty()) {
            return Command::SUCCESS;
        }

        foreach ($queued as $queueJob) {
            $claimed = Jobs::query()
                ->where('id', $queueJob->id)
                ->where('send_report', 1)
                ->update([
                    'send_report' => 2, // processing lock
                    'response' => 'Cron obrađuje job...',
                ]);

            if (!$claimed) {
                continue;
            }

            $startedAt = microtime(true);
            $filePath = '';

            try {
                $payload = json_decode((string) $queueJob->payload, true, 512, JSON_THROW_ON_ERROR);
                $filePath = (string) ($payload['file'] ?? '');
                $imagesDir = (string) ($payload['images_dir'] ?? '');

                if ($filePath === '' || !is_file($filePath)) {
                    throw new \RuntimeException('Queue payload nema valjanu Excel putanju.');
                }

                if ($imagesDir === '' || !is_dir($imagesDir)) {
                    throw new \RuntimeException("Queue payload nema valjanu 'images_dir' putanju.");
                }

                $exitCode = $this->call('import:full-excel', [
                    'file' => $filePath,
                    '--images_dir' => $imagesDir,
                ]);

                $seconds = number_format(microtime(true) - $startedAt, 2, ',', '.');

                Jobs::query()->where('id', $queueJob->id)->update([
                    'time' => $seconds,
                    'success' => $exitCode === Command::SUCCESS ? 1 : 0,
                    'response' => $exitCode === Command::SUCCESS ? 'Queue job obrađen.' : 'Queue job pao. Provjeri CRON report.',
                    'send_report' => 0,
                ]);
            } catch (\Throwable $e) {
                $seconds = number_format(microtime(true) - $startedAt, 2, ',', '.');

                if ($filePath !== '' && is_file($filePath)) {
                    @unlink($filePath);
                }

                Jobs::query()->where('id', $queueJob->id)->update([
                    'time' => $seconds,
                    'success' => 0,
                    'response' => $e->getMessage(),
                    'send_report' => 0,
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
