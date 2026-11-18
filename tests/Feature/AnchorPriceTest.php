<?php

namespace Tests\Feature;

use App\Models\AnchorPrice;
use App\Models\Front\Catalog\Product;
use App\Models\PriceListPublication;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\AnchorPrices;
use App\Services\PriceListPublisher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnchorPriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->travelTo(now()->setDate(2026, 11, 12)->setTime(8, 0));
        session(['locale' => 'hr']);
        Storage::fake('local');
        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        require_once database_path('migrations/2020_01_02_223800_create_bouncer_tables.php');
        (new \CreateUsersTable)->up();
        (new \CreateBouncerTables)->up();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('user_details', fn (Blueprint $table) => $table->unsignedInteger('user_group_id')->nullable());
        (require database_path('migrations/2026_11_12_190000_create_anchor_price_tables.php'))->up();
        (require database_path('migrations/2026_11_18_080000_version_webshop_price_lists.php'))->up();
        DB::statement('create table products (id integer primary key, brand_id integer, sku text, ean text, price real, status integer, quantity integer)');
        DB::statement('create table product_option (id integer primary key, product_id integer, parent_id integer, option_id integer, sku text, price numeric, quantity integer)');
        DB::statement('create table options_translations (option_id integer, lang text, title text)');
        DB::statement('create table product_translations (id integer primary key, product_id integer, lang text, name text)');
        DB::statement('create table brand_translations (id integer primary key, brand_id integer, lang text, title text)');
        DB::statement('create table product_actions (id integer primary key, status integer, "group" text, user_group_id integer, "lock" integer, links text, type text, discount real, coupon text, date_start text, date_end text, logged integer)');
        DB::statement('create table product_actions_translations (id integer primary key, product_action_id integer, lang text, title text)');
        DB::statement('create table pages (id integer primary key, status integer, "group" text)');
        DB::statement('create table page_translations (id integer primary key, page_id integer, lang text, title text, slug text)');
        DB::statement('create table settings (id integer primary key, code text, "key" text, value text, json integer, created_at text, updated_at text)');
        DB::table('products')->insert([
            ['id' => 1, 'brand_id' => 1, 'sku' => 'TT-001', 'ean' => '4006381333931', 'price' => 100, 'status' => 1, 'quantity' => 3],
            ['id' => 2, 'brand_id' => 1, 'sku' => 'TT-002', 'ean' => '', 'price' => 50, 'status' => 1, 'quantity' => 0],
            ['id' => 3, 'brand_id' => 1, 'sku' => 'TT-003', 'ean' => '', 'price' => 70, 'status' => 0, 'quantity' => 2],
        ]);
        DB::table('product_translations')->insert([
            ['product_id' => 1, 'lang' => 'hr', 'name' => 'Dres <Pro> & "Tim"'],
            ['product_id' => 2, 'lang' => 'hr', 'name' => 'Lopta'],
            ['product_id' => 3, 'lang' => 'hr', 'name' => 'Skriveni proizvod'],
        ]);
        DB::table('brand_translations')->insert(['brand_id' => 1, 'lang' => 'hr', 'title' => 'Brand & Co']);
    }

    public function test_migration_preserves_existing_catalog_schema_and_prices(): void
    {
        $migration = require database_path('migrations/2026_11_12_190000_create_anchor_price_tables.php');
        $before = DB::table('products')->get()->toArray();
        $columns = Schema::getColumns('products');
        $migration->down();
        $migration->up();
        $this->assertEquals($before, DB::table('products')->get()->toArray());
        $this->assertEquals($columns, Schema::getColumns('products'));
        $this->assertDatabaseCount('anchor_prices', 0);
    }

    public function test_upgrade_preserves_legacy_downloads_and_backfills_replacement_times(): void
    {
        $versioning = require database_path('migrations/2026_11_18_080000_version_webshop_price_lists.php');
        $versioning->down();
        foreach (['2026-10-01', '2026-11-11'] as $day) {
            $filename = "webshop_{$day}.xml";
            Storage::disk('local')->put($filename, '<original/>');
            PriceListPublication::create(['publication_date' => $day, 'published_at' => "$day 07:00:00",
                'product_count' => 1, 'sha256' => hash('sha256', '<original/>'), 'filename' => $filename, 'path' => $filename]);
        }
        $versioning->up();
        $this->assertDatabaseHas('price_list_publications', ['id' => 1, 'replaced_at' => '2026-11-11 07:00:00']);
        $this->assertDatabaseHas('price_list_publications', ['id' => 2, 'replaced_at' => null, 'revision' => 1]);
        $this->get(route('price-lists.download', 1))->assertDownload('webshop_2026-10-01.xml');
        $this->get(route('price-lists.latest'))->assertDownload('webshop_2026-11-11.xml');
        $this->save(1);
        $this->save(2);
        $this->artisan('prices:publish')->assertSuccessful();
        $this->assertDatabaseCount('price_list_publications', 3);
    }

    public function test_reference_date_matches_the_confirmed_group_and_first_offer(): void
    {
        $this->actingAs($this->user('admin'));
        $url = route('anchor-prices.update', 1);
        $this->patchJson($url, $this->payload(1, ['reference_group' => 'general', 'first_offered_on' => '2026-10-16']))
            ->assertUnprocessable();
        $this->patch($url, $this->payload(1, ['reference_group' => 'household', 'first_offered_on' => '2025-04-01',
            'reference_date' => '2025-05-02']))->assertSessionHasNoErrors();
        $this->assertSame('2025-05-02', AnchorPrice::where('product_id', 1)->firstOrFail()->reference_date->toDateString());
        $this->patchJson($url, $this->payload(1))->assertUnprocessable();
        $this->patch($url, $this->payload(1, ['reference_group' => 'general', 'first_offered_on' => '2026-10-16',
            'reference_date' => '2026-10-16']))->assertSessionHasNoErrors();
    }

    public function test_admin_edits_prices_and_revision_history_without_changing_sale_prices(): void
    {
        $admin = $this->user('admin');
        $payload = $this->payload(1);
        $this->actingAs($admin)->get(route('anchor-prices.index'))->assertOk()->assertSee('Bazne cijene i XML cjenici');
        $this->get(route('anchor-prices.edit', 1))->assertOk();
        $this->patch(route('anchor-prices.update', 1), $payload)->assertRedirect();
        $this->assertDatabaseHas('anchor_prices', ['product_id' => 1, 'price' => 125, 'updated_by' => $admin->id]);
        $this->assertDatabaseHas('products', ['id' => 1, 'price' => 100]);
        $payload['price'] = '130,50';
        $this->patch(route('anchor-prices.update', 1), $payload)->assertRedirect();
        $this->get(route('anchor-prices.edit', 1))->assertOk()->assertSee('130,50');
        $this->assertDatabaseCount('anchor_price_revisions', 2);
        $before = json_decode(DB::table('anchor_price_revisions')->orderByDesc('id')->value('before'), true);
        $this->assertEquals(125, $before['price']);
    }

    public function test_only_confirmed_prices_display_and_lookup_is_reused_across_cards(): void
    {
        $this->save(1, ['status' => 'pending']);
        $this->assertNull(Product::find(1)->anchor_price);
        $this->save(1);
        $this->assertSame(['price' => 125.0, 'formatted' => '125,00 €', 'date' => '10.09.2026.'], Product::find(1)->anchor_price);
        $product = Product::find(1);
        DB::enableQueryLog();
        DB::flushQueryLog();
        for ($i = 0; $i < 40; $i++) {
            $this->assertSame(125.0, $product->anchor_price['price']);
        }
        $this->assertCount(0, DB::getQueryLog());
        $this->assertStringContainsString('Bazna cijena na 10.09.2026.', view('front.catalog.anchor-price', compact('product'))->render());
        $this->save(1, ['status' => 'pending']);
        $this->assertNull($product->anchor_price);
        $this->assertNull(Product::find(2)->anchor_price);
    }

    public function test_front_remains_available_before_the_new_migration(): void
    {
        (require database_path('migrations/2026_11_12_190000_create_anchor_price_tables.php'))->down();
        $this->assertNull(Product::find(1)->anchor_price);
        $this->get(route('price-lists.index'))->assertOk()->assertSee('Cjenici su u pripremi.');
        $this->get(route('price-lists.latest'))->assertNotFound();
        $this->get(route('price-lists.download', 1))->assertNotFound();
    }

    public function test_information_pages_load_navigation_translations_and_existing_footer_links(): void
    {
        DB::table('pages')->insert(['id' => 1, 'status' => 1, 'group' => 'page']);
        DB::table('page_translations')->insert([
            'page_id' => 1, 'lang' => 'hr', 'title' => 'Opći uvjeti poslovanja', 'slug' => 'opci-uvjeti-poslovanja',
        ]);

        foreach (['price-lists.index', 'product-return', 'legal-guarantee'] as $route) {
            // Each request must supply its own shared data, as on the live site.
            \Illuminate\Support\Facades\View::share(['pages' => null, 'js_lang' => null]);
            $this->get(route($route))->assertOk()
                ->assertSee('window.trans =', false)
                ->assertSee('window.locale = "hr"', false)
                ->assertSee('Opći uvjeti poslovanja')
                ->assertSee(route('catalog.route.page', ['page' => 'opci-uvjeti-poslovanja']), false);
        }
    }

    public function test_old_disabled_setting_cannot_hide_confirmed_base_prices(): void
    {
        DB::table('settings')->insert(['code' => 'anchor_prices', 'key' => 'display_enabled', 'value' => '0']);
        $this->save(1);
        $this->actingAs($this->user('admin'))->patchJson(route('anchor-prices.settings'), ['display_enabled' => 0])
            ->assertUnprocessable();
        $this->assertSame(125.0, Product::find(1)->anchor_price['price']);
        $this->assertNull(Product::find(2)->anchor_price);
    }

    public function test_guests_customers_and_editors_cannot_change_or_export_prices(): void
    {
        $this->patchJson(route('anchor-prices.update', 1), $this->payload(1))->assertUnauthorized();
        $this->patchJson(route('anchor-prices.settings'), ['display_enabled' => 0])->assertUnauthorized();
        foreach (['customer', 'editor'] as $role) {
            $this->actingAs($this->user($role));
            $this->getJson(route('anchor-prices.index'))->assertForbidden();
            $this->getJson(route('anchor-prices.export'))->assertForbidden();
            $this->patchJson(route('anchor-prices.update', 1), $this->payload(1))->assertForbidden();
            $this->postJson(route('anchor-prices.publish'))->assertForbidden();
            $this->patchJson(route('anchor-prices.settings'), ['display_enabled' => 0])->assertForbidden();
        }
        $this->assertDatabaseCount('anchor_prices', 0);
    }

    public function test_invalid_values_are_rejected_without_revisions(): void
    {
        $this->actingAs($this->user('admin'));
        foreach ([['price' => -1], ['price' => 0], ['price' => 'NaN'], ['price' => '1.12345'],
            ['reference_date' => '2026-11-13'], ['reference_date' => 'nema'], ['status' => 'published'], ['note' => '']] as $invalid) {
            $this->patchJson(route('anchor-prices.update', 1), array_replace($this->payload(1), $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('anchor_price_revisions', 0);
    }

    public function test_csv_preview_import_and_export_preserve_status_and_protect_formulas(): void
    {
        $this->actingAs($this->user('admin'));
        $csv = "product_id;sku;price;reference_date;status\n1;TT-001;125,50;2026-09-10;confirmed\n2;TT-002;50;2026-09-10;pending\n";
        $this->post(route('anchor-prices.import'), ['file' => UploadedFile::fake()->createWithContent('prices.csv', $csv), 'note' => 'Provjeren arhivirani cjenik', 'dry_run' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('anchor_prices', 0);
        $this->post(route('anchor-prices.import'), ['file' => UploadedFile::fake()->createWithContent('prices.csv', $csv), 'note' => 'Provjeren arhivirani cjenik'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('anchor_prices', ['product_id' => 1, 'price' => 125.5, 'status' => 'confirmed']);
        $this->assertDatabaseHas('anchor_prices', ['product_id' => 2, 'status' => 'pending']);
        DB::table('product_translations')->where('product_id', 1)->update(['name' => '=HYPERLINK("example")']);
        $response = $this->get(route('anchor-prices.export'))->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
        $this->assertStringContainsString('confirmed', $response->streamedContent());
    }

    public function test_malformed_csv_does_not_partially_import(): void
    {
        $this->actingAs($this->user('admin'));
        foreach ([
            "1;125;2026-09-10;confirmed\n2;-1;2026-09-10;confirmed\n",
            "1;125;2026-09-10;confirmed\n1;130;2026-09-10;confirmed\n",
            "1;125;2026-09-10;confirmed\n999;130;2026-09-10;confirmed\n",
        ] as $rows) {
            $this->postJson(route('anchor-prices.import'), ['file' => UploadedFile::fake()->createWithContent('prices.csv', "product_id;price;reference_date;status\n" . $rows), 'note' => 'Evidencija'])->assertUnprocessable();
        }
        $this->assertDatabaseCount('anchor_prices', 0);
        $this->assertDatabaseCount('anchor_price_revisions', 0);
    }

    public function test_publication_contains_public_campaign_prices_escaping_and_unavailable_items(): void
    {
        $this->save(1);
        $this->save(2, ['price' => 60, 'reference_date' => '2026-11-12']);
        DB::table('product_actions')->insert([
            ['id' => 1, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 10, 'user_group_id' => null, 'coupon' => null, 'logged' => 0],
            ['id' => 2, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 99, 'user_group_id' => 1, 'coupon' => null, 'logged' => 0],
            ['id' => 3, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 95, 'user_group_id' => null, 'coupon' => 'PRIVATE', 'logged' => 0],
        ]);
        DB::table('product_actions_translations')->insert(['product_action_id' => 1, 'lang' => 'hr', 'title' => 'Jesenska akcija']);
        session([config('session.cart') . '_coupon' => 'PRIVATE']);
        $this->actingAs($this->user('admin'));
        $publication = app(PriceListPublisher::class)->publish();
        $xml = simplexml_load_string(Storage::disk('local')->get($publication->path));
        $this->assertCount(2, $xml->products->product);
        $this->assertSame('Dres <Pro> & "Tim"', (string) $xml->products->product[0]->name);
        $this->assertSame('90.00', (string) $xml->products->product[0]->current_price);
        $this->assertSame('125.00', (string) $xml->products->product[0]->anchor_price);
        $this->assertSame('2026-09-10', (string) $xml->products->product[0]->anchor_date);
        $this->assertSame('Jesenska akcija', (string) $xml->products->product[0]->special_offer_name);
        $this->assertSame('nedostupno', (string) $xml->products->product[1]->availability);
        $this->assertSame(1, $publication->missing_barcodes);
        $this->assertTrue(app(PriceListPublisher::class)->valid($publication));
        $this->get(route('price-lists.index'))->assertOk()->assertSee('XML cjenik');
        $this->get(route('price-lists.latest'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->get(route('price-lists.download', $publication))->assertOk();
    }

    public function test_incomplete_catalog_is_not_published_and_temporary_files_are_removed(): void
    {
        $this->save(1);
        $this->artisan('prices:publish')->assertExitCode(1);
        $this->assertDatabaseCount('price_list_publications', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->save(2);
        DB::table('product_translations')->where('product_id', 2)->delete();
        $this->artisan('prices:publish')->assertExitCode(1);
        $this->assertDatabaseCount('price_list_publications', 0);
    }

    public function test_republishes_changes_in_the_same_minute_and_keeps_old_file(): void
    {
        $this->save(1);
        $this->save(2);
        $publisher = app(PriceListPublisher::class);
        $first = $publisher->publish();
        $this->assertSame($first->id, $publisher->publish()->id);
        DB::table('products')->where('id', 1)->update(['price' => 200]);
        $second = $publisher->publish();
        $this->assertNotEquals($first->id, $second->id);
        $this->assertStringEndsWith('_ROBA_2_20261112_0800.xml', $second->filename);
        $this->assertSame($second->id, $publisher->publish()->id);
        $this->assertStringContainsString('<regular_price>100.00</regular_price>', Storage::disk('local')->get($first->path));
        $this->assertStringContainsString('<regular_price>200.00</regular_price>', Storage::disk('local')->get($second->path));
        $this->get(route('price-lists.latest'))->assertDownload($second->filename);
        $this->get(route('price-lists.download', $first))->assertDownload($first->filename);
        $this->assertDatabaseCount('price_list_publications', 2);
    }

    public function test_archive_expires_from_replacement_and_current_never_ages_out(): void
    {
        $this->save(1);
        $this->save(2);
        $publisher = app(PriceListPublisher::class);
        $first = $publisher->publish();
        $this->travel(40)->days();
        $this->get(route('price-lists.latest'))->assertDownload($first->filename);
        $this->assertSame($first->id, $publisher->publish()->id);
        DB::table('products')->where('id', 1)->update(['price' => 105]);
        $second = $publisher->publish();
        $this->travel(30)->days();
        $this->get(route('price-lists.download', $first))->assertOk();
        $this->travel(1)->minutes();
        $this->get(route('price-lists.download', $first))->assertNotFound();
        $this->get(route('price-lists.latest'))->assertDownload($second->filename);
        Storage::disk('local')->put($second->path, '<bad/>');
        $this->get(route('price-lists.latest'))->assertNotFound();
        $this->artisan('prices:publish')->assertExitCode(1);
    }

    public function test_published_field_order_packaging_and_independent_barcodes(): void
    {
        $this->actingAs($this->user('admin'));
        $this->patch(route('anchor-prices.update', 1), $this->payload(1) + [
            'net_quantity' => '0,25', 'measure_unit' => 'l', 'goods_barcode' => 'TT-CODE',
        ])->assertSessionHasNoErrors();
        DB::table('products')->where('id', 2)->update(['sku' => 'YKM100011']);
        $this->save(2);
        $pub = app(PriceListPublisher::class)->publish();
        $xml = simplexml_load_string(Storage::disk('local')->get($pub->path));
        $row = $xml->products->product[0];
        $this->assertSame(['name','sku','manufacturer','net_quantity','unit','current_price','special_offer',
            'unit_price','barcode','ean','anchor_price'], array_slice(array_keys((array) $row), 0, 11));
        $this->assertSame('20925110769', (string) $xml->metadata->oib);
        $this->assertStringStartsWith('20925110769_internetska-trgovina_', $pub->filename);
        $this->assertSame('0.25', (string) $row->net_quantity);
        $this->assertSame('400.00', (string) $row->unit_price);
        $this->assertSame('TT-CODE', (string) $row->barcode);
        $this->assertSame('4006381333931', (string) $row->ean);
        $this->assertSame('5903137554086', (string) $xml->products->product[1]->ean);
        $this->patchJson(route('anchor-prices.update', 1), $this->payload(1) + ['measure_unit' => 'l'])
            ->assertUnprocessable();
    }

    public function test_option_surcharge_requires_its_own_evidence_and_public_prices_follow_the_selected_sku(): void
    {
        $this->save(1);
        $this->save(2);
        DB::table('product_option')->insert([
            ['id' => 4, 'product_id' => 1, 'parent_id' => 90, 'option_id' => 91, 'price' => 5, 'quantity' => 2, 'sku' => 'TT-001-XL'],
            ['id' => 5, 'product_id' => 1, 'parent_id' => 90, 'option_id' => 92, 'price' => 0, 'quantity' => 1, 'sku' => 'TT-001-L'],
        ]);
        $this->artisan('prices:publish')->assertExitCode(1);
        $this->actingAs($this->user('admin'))->patch(route('anchor-prices.variant', [1, 4]), [
            'price' => '119,50', 'reference_date' => '2026-10-10', 'note' => 'Prva javna ponuda veličine XL',
            'status' => 'confirmed', 'ean' => '4006381333931',
        ])->assertSessionHasNoErrors();
        $pub = app(PriceListPublisher::class)->publish();
        $xml = simplexml_load_string(Storage::disk('local')->get($pub->path));
        $this->assertCount(3, $xml->products->product);
        $xl = $xml->products->product[0];
        $this->assertSame('TT-001-XL', (string) $xl->sku);
        $this->assertSame('105.00', (string) $xl->current_price);
        $this->assertSame('119.50', (string) $xl->anchor_price);
        $this->assertSame('2026-10-10', (string) $xl->anchor_date);
        $this->assertSame('4006381333931', (string) $xl->ean);
        $this->assertSame('125.00', (string) $xml->products->product[1]->anchor_price);
        $this->assertSame('', (string) $xml->products->product[1]->ean);
    }

    public function test_footer_links_and_legal_guarantee_page_are_public(): void
    {
        $this->get(route('legal-guarantee'))->assertOk()->assertSee('Zakonsko jamstvo – najmanje 2 godine')
            ->assertSee('legal-guarantee-notice-hr.svg')->assertSee(route('price-lists.index'))
            ->assertSee(route('product-return'))->assertSee('id="legal-guarantee-modal"', false);
    }

    private function payload(int $product, array $changes = []): array
    {
        return array_replace(['product_id' => $product, 'price' => 125, 'reference_date' => '2026-09-10',
            'status' => 'confirmed', 'note' => 'Arhivirani cjenik od 10.09.2026.'], $changes);
    }

    private function save(int $product, array $changes = []): AnchorPrice
    {
        return app(AnchorPrices::class)->save($this->payload($product, $changes), null);
    }

    private function user(string $role): User
    {
        $id = User::count() + 1;
        $user = User::create(['name' => 'Test ' . $id, 'email' => 'test' . $id . '@example.test', 'password' => 'unused']);
        UserDetail::create(['user_id' => $user->id, 'fname' => $user->name, 'role' => $role, 'status' => 1]);
        $user->assign($role);
        return $user;
    }
}
