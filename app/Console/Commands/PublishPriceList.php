<?php

namespace App\Console\Commands;

use App\Services\PriceListPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishPriceList extends Command
{
    protected $signature = 'prices:publish';
    protected $description = 'Objavi izmjene XML cjenika s potvrđenim baznim cijenama.';

    public function handle(PriceListPublisher $publisher): int
    {
        try {
            $publication = $publisher->publish();
            $this->info($publication->filename . ' — ' . $publication->product_count . ' proizvoda.');
            if ($publication->missing_barcodes) {
                $this->warn('Bez valjanog barkoda: ' . $publication->missing_barcodes);
            }
            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Objava XML cjenika nije uspjela.', ['error' => $exception->getMessage()]);
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
