<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'cache.default' => 'array']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Storage::fake('local');
        DB::statement('create table page_translations (id integer primary key, lang text, title text, slug text, description text, updated_at text)');
        DB::statement('create table faq_translations (id integer primary key, lang text, title text, description text, updated_at text)');
        DB::statement('create table categories (id integer primary key, parent_id integer, "group" text, sort_order integer, status integer, image text, created_at text, updated_at text)');
        DB::statement('create table category_translations (id integer primary key, category_id integer, lang text, title text, slug text, description text, meta_title text, meta_description text, created_at text, updated_at text)');
        DB::statement('create table settings (id integer primary key, code text, "key" text, value text, json integer, created_at text, updated_at text)');
        foreach (['informacije-o-dostavi', 'opci-uvjeti-poslovanja', 'o-nama'] as $i => $slug) {
            DB::table('page_translations')->insert(['id' => $i + 1, 'lang' => 'hr', 'title' => $slug, 'slug' => $slug, 'description' => '<p>Stari tekst</p>']);
        }
        foreach (['Koliko dugo moram čekati pošiljku?', 'Kolika je cijena dostave?', 'Drugo pitanje'] as $i => $title) {
            DB::table('faq_translations')->insert(['id' => $i + 1, 'lang' => 'hr', 'title' => $title, 'description' => 'Stari FAQ']);
        }
        foreach (['Rasprodaja', 'Dresovi'] as $i => $title) {
            DB::table('categories')->insert(['id' => $i + 1, 'parent_id' => 0, 'group' => 'kategorija-proizvoda', 'sort_order' => $i, 'status' => 1]);
            DB::table('category_translations')->insert(['category_id' => $i + 1, 'lang' => 'hr', 'title' => $title, 'slug' => strtolower($title)]);
        }
    }

    public function test_preview_does_not_write_and_apply_is_backed_up_and_idempotent(): void
    {
        $this->artisan('storefront:refresh-content')->assertSuccessful();
        $this->assertDatabaseCount('categories', 2);
        $this->assertCount(0, Storage::disk('local')->allFiles());
        $this->artisan('storefront:refresh-content', ['--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('categories', 3);
        $this->assertDatabaseHas('category_translations', ['title' => 'FAN SHOP', 'slug' => 'fan-shop']);
        $this->assertSame([1, 3, 2], DB::table('categories')->orderBy('sort_order')->pluck('id')->all());
        $this->assertStringContainsString('3–7 radnih dana', DB::table('page_translations')->where('id', 1)->value('description'));
        $this->assertStringContainsString('15–30 radnih dana', DB::table('faq_translations')->where('id', 1)->value('description'));
        $this->assertDatabaseHas('page_translations', ['id' => 3, 'description' => '<p>Stari tekst</p>']);
        $this->assertDatabaseHas('faq_translations', ['id' => 3, 'description' => 'Stari FAQ']);
        $files = Storage::disk('local')->allFiles('storefront-backups');
        $this->assertCount(1, $files);
        $backup = json_decode(Storage::disk('local')->get($files[0]), true);
        $this->assertSame('<p>Stari tekst</p>', $backup['changes'][0]['before']['description']);
        DB::table('page_translations')->where('id', 1)->update(['description' => 'Nova admin izmjena']);
        $this->artisan('storefront:refresh-content', ['--apply' => true])->assertSuccessful();
        $this->assertDatabaseHas('page_translations', ['id' => 1, 'description' => 'Nova admin izmjena']);
        $this->assertDatabaseCount('categories', 3);
        $this->assertCount(1, Storage::disk('local')->allFiles('storefront-backups'));
    }

    public function test_missing_expected_content_stops_before_any_write(): void
    {
        DB::table('faq_translations')->where('id', 1)->delete();
        $this->artisan('storefront:refresh-content', ['--apply' => true])->assertFailed();
        $this->assertDatabaseHas('page_translations', ['id' => 1, 'description' => '<p>Stari tekst</p>']);
        $this->assertDatabaseCount('categories', 2);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_club_cards_show_images_and_existing_category_urls(): void
    {
        session(['locale' => 'hr']);
        $cat = new \App\Models\Front\Catalog\Category(['id' => 3, 'group' => 'kategorija-proizvoda']);
        $cat->setRelation('translation', new \App\Models\Front\Catalog\CategoryTranslation(['slug' => 'fan-shop', 'title' => 'FAN SHOP']));
        $club = new \App\Models\Front\Catalog\Category(['id' => 4, 'parent_id' => 3, 'image' => 'media/club.png']);
        $club->setRelation('translation', new \App\Models\Front\Catalog\CategoryTranslation(['slug' => 'klub', 'title' => 'Klub']));
        $html = view('front.catalog.category.fan-clubs', ['cat' => $cat, 'clubs' => collect([$club])])->render();
        $this->assertStringContainsString('/kategorija-proizvoda/fan-shop/klub', $html);
        $this->assertStringContainsString('Grb kluba Klub', $html);
        $this->assertStringContainsString('media/club.png', $html);
    }

    public function test_legacy_hrk_is_suppressed_and_euro_cents_are_preserved(): void
    {
        \Illuminate\Support\Facades\Cache::forever('currency_secondary', (object) [
            'code' => 'HRK', 'value' => 7.5345, 'decimal_places' => 2, 'symbol_left' => '', 'symbol_right' => ' kn',
        ]);
        $this->assertFalse(\App\Helpers\Currency::secondary(12.95, true));
        $product = new \App\Models\Front\Catalog\Product(['price' => 12.95]);
        $this->assertSame('12,95 €', $product->priceString());
        $this->assertSame('0,00 €', $product->priceString('0'));
    }
}
