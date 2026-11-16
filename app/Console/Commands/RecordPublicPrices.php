<?php

namespace App\Console\Commands;

use App\Services\RecordedPrices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecordPublicPrices extends Command
{
    protected $signature = 'prices:record {--missing-only : Dodaj trenutnu cijenu samo artiklima bez evidencije}';
    protected $description = 'Bilježi trenutne javne cijene bez prepisivanja povijesnih iznosa.';

    public function handle(RecordedPrices $prices): int
    {
        $count = Cache::lock('recorded_prices.capture', 1800)->get(fn () => $prices->capture((bool) $this->option('missing-only')));
        if ($count === false) {
            $this->warn('Bilježenje cijena već je u tijeku.');
            return self::FAILURE;
        }
        $this->info('Novi zapisi cijena: ' . $count . '. Postojeći iznosi nisu prepisani.');
        return self::SUCCESS;
    }
}
