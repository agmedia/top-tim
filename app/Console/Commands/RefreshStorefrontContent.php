<?php

namespace App\Console\Commands;

use App\Services\CategoryNavigation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class RefreshStorefrontContent extends Command
{
    protected $signature = 'storefront:refresh-content {--apply : Spremi izmjene nakon sigurnosne kopije}';
    protected $description = 'Jednokratno usklađivanje dostave, FAQ-a, uvjeta i FAN SHOP kategorije (16.11.2026.).';
    private const MARKER = 'content_2026_11_16';

    public function handle(): int
    {
        if (DB::table('settings')->where('code', 'storefront')->where('key', self::MARKER)->exists()) {
            $this->info('Ova izmjena je već primijenjena. Naknadne admin izmjene ostaju sačuvane.');
            return self::SUCCESS;
        }

        $changes = [];
        foreach (['informacije-o-dostavi' => 'delivery', 'opci-uvjeti-poslovanja' => 'terms'] as $slug => $view) {
            $rows = DB::table('page_translations')->where('lang', 'hr')->where('slug', $slug)->get();
            if ($rows->count() !== 1) {
                $this->error('Očekivana je jedna stranica: ' . $slug . '. Ništa nije promijenjeno.');
                return self::FAILURE;
            }
            $changes[] = ['table' => 'page_translations', 'before' => (array) $rows->first(),
                'description' => view('front.content.' . $view)->render()];
        }

        $faq = [
            'Koliko dugo moram čekati pošiljku?' => '<p>Artikli iz kategorije RASPRODAJA: <strong>3–7 radnih dana</strong>. Svi ostali artikli: <strong>15–30 radnih dana</strong>. Rok se računa od potvrde narudžbe, odnosno zaprimljene uplate kod plaćanja virmanom. Za zajedničku isporuku narudžbe s različitim rokovima vrijedi dulji rok.</p>',
            'Kolika je cijena dostave?' => '<p>Standardna dostava u Hrvatskoj iznosi 5,99 EUR, a za narudžbe iznad 150 EUR je besplatna. Za rekvizite i glomaznu robu cijena ovisi o težini i dimenzijama; poseban trošak dogovaramo s vama prije slanja. Konačan trošak prikazuje se pri naručivanju.</p>',
        ];
        foreach ($faq as $title => $description) {
            $rows = DB::table('faq_translations')->where('lang', 'hr')->where('title', $title)->get();
            if ($rows->count() !== 1) {
                $this->error('Očekivano je jedno FAQ pitanje: ' . $title . '. Ništa nije promijenjeno.');
                return self::FAILURE;
            }
            $changes[] = ['table' => 'faq_translations', 'before' => (array) $rows->first(), 'description' => $description];
        }

        $categories = DB::table('categories')->where('parent_id', 0)->where('group', 'kategorija-proizvoda')->orderBy('sort_order')->orderBy('id')->get();
        $translations = DB::table('category_translations')->whereIn('category_id', $categories->pluck('id'))->get();
        $sale = $translations->first(fn ($row) => $row->lang === 'hr' && Str::slug($row->title) === 'rasprodaja');
        $fan = $translations->first(fn ($row) => $row->slug === config('storefront.fan_shop_slug'));
        if (!$sale) {
            $this->error('Nije pronađena glavna kategorija RASPRODAJA. Ništa nije promijenjeno.');
            return self::FAILURE;
        }
        $this->table(['Tablica', 'ID', 'Sadržaj'], array_map(fn ($change) => [$change['table'], $change['before']['id'], $change['before']['title']], $changes));
        $this->line($fan ? 'FAN SHOP već postoji; čuvamo sadržaj i klubove.' : 'Dodaje se aktivna kategorija FAN SHOP odmah ispod RASPRODAJE.');
        if (!$this->option('apply')) {
            $this->info('Probna provjera: nema promjena. Za primjenu dodati --apply.');
            return self::SUCCESS;
        }

        $backup = 'storefront-backups/' . now()->format('Ymd-His') . '-' . Str::uuid() . '.json';
        if (!Storage::disk('local')->put($backup, json_encode([
            'changes' => $changes, 'categories' => $categories, 'category_translations' => $translations,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Sigurnosna kopija nije spremljena; prekid izmjene.');
        }

        DB::transaction(function () use ($changes, $categories, $translations, $sale, $fan, $backup) {
            foreach ($changes as $change) {
                $row = DB::table($change['table'])->where('id', $change['before']['id'])->lockForUpdate()->first();
                if (!$row || $row->description !== $change['before']['description']) {
                    throw new RuntimeException('Sadržaj se promijenio tijekom pripreme; ništa nije spremljeno.');
                }
                DB::table($change['table'])->where('id', $row->id)->update(['description' => $change['description'], 'updated_at' => now()]);
            }
            if (!$fan) {
                $fanId = DB::table('categories')->insertGetId([
                    'parent_id' => 0, 'group' => 'kategorija-proizvoda', 'sort_order' => 0,
                    'status' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($translations->pluck('lang')->unique() as $lang) {
                    DB::table('category_translations')->insert([
                        'category_id' => $fanId, 'lang' => $lang, 'title' => 'FAN SHOP',
                        'slug' => config('storefront.fan_shop_slug'), 'description' => '',
                        'meta_title' => 'FAN SHOP | Top Tim', 'meta_description' => '',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $order = 0;
                foreach ($categories as $category) {
                    if ((int) DB::table('categories')->where('id', $category->id)->value('sort_order') !== (int) $category->sort_order) {
                        throw new RuntimeException('Redoslijed kategorija je promijenjen tijekom pripreme.');
                    }
                    DB::table('categories')->where('id', $category->id)->update(['sort_order' => $order += 10]);
                    if ($category->id === $sale->category_id) {
                        DB::table('categories')->where('id', $fanId)->update(['sort_order' => $order += 10]);
                    }
                }
            }
            DB::table('settings')->insert([
                'code' => 'storefront', 'key' => self::MARKER, 'value' => $backup, 'json' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        CategoryNavigation::invalidate();
        $this->info('Sadržaj spremljen. Sigurnosna kopija: ' . $backup);
        return self::SUCCESS;
    }
}
