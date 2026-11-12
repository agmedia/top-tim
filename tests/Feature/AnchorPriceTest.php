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
        DB::statement('create table products (id integer primary key, brand_id integer, sku text, ean text, price real, status integer, quantity integer)');
        DB::statement('create table product_translations (id integer primary key, product_id integer, lang text, name text)');
        DB::statement('create table brand_translations (id integer primary key, brand_id integer, lang text, title text)');
        DB::statement('create table product_actions (id integer primary key, status integer, "group" text, user_group_id integer, "lock" integer, links text, type text, discount real, coupon text, date_start text, date_end text, logged integer)');
        DB::statement('create table product_actions_translations (id integer primary key, product_action_id integer, lang text, title text)');
        DB::statement('create table pages (id integer primary key)');
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

    public function test_admin_edits_prices_and_revision_history_without_changing_sale_prices(): void
    {
        $admin = $this->user('admin');
        $payload = $this->payload(1);
        $this->actingAs($admin)->get(route('anchor-prices.index'))->assertOk()->assertSee('Sidrene cijene i XML cjenici');
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
        $this->assertStringContainsString('Cijena na 10.09.2026.', view('front.catalog.anchor-price', compact('product'))->render());
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

    public function test_admin_can_switch_frontend_display_off_and_on_without_losing_prices_or_xml(): void
    {
        $this->save(1);
        $this->save(2);
        $this->save(3, ['status' => 'pending']);
        $prices = app(AnchorPrices::class);
        $product = Product::find(1)->setAppends(['anchor_price']);
        $this->assertTrue($prices->displayEnabled());
        $this->assertNotNull($product->toArray()['anchor_price']);
        $this->actingAs($this->user('admin'))
            ->patch(route('anchor-prices.settings'), ['display_enabled' => 0])
            ->assertRedirect(route('anchor-prices.index'))->assertSessionHasNoErrors();
        $this->get(route('anchor-prices.index'))->assertOk()->assertViewHas('displayEnabled', false)
            ->assertSee('Prikazuj sidrene cijene na webshopu');
        $this->assertNull($product->toArray()['anchor_price']);
        $this->assertStringNotContainsString('Cijena na', view('front.catalog.anchor-price', compact('product'))->render());
        $this->assertDatabaseHas('settings', ['code' => 'anchor_prices', 'key' => 'display_enabled', 'value' => '0']);
        $this->assertDatabaseCount('anchor_prices', 3);
        $this->assertDatabaseCount('anchor_price_revisions', 3);
        $publication = app(PriceListPublisher::class)->publish();
        $this->assertStringContainsString('<anchor_price>125.00</anchor_price>', Storage::disk('local')->get($publication->path));
        $this->get(route('price-lists.latest'))->assertOk();

        $this->patch(route('anchor-prices.settings'), ['display_enabled' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('anchor-prices.index'))->assertOk()->assertViewHas('displayEnabled', true);
        $this->assertSame(125.0, $product->anchor_price['price']);
        $this->assertStringContainsString('Cijena na 10.09.2026.', view('front.catalog.anchor-price', compact('product'))->render());
        $this->assertNull(Product::find(3)->anchor_price);
        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseHas('products', ['id' => 1, 'price' => 100]);
    }

    public function test_display_setting_rejects_missing_or_invalid_boolean(): void
    {
        $this->actingAs($this->user('admin'));
        foreach ([[], ['display_enabled' => 'yes'], ['display_enabled' => 2]] as $data) {
            $this->patchJson(route('anchor-prices.settings'), $data)->assertUnprocessable();
        }
        $this->assertTrue(app(AnchorPrices::class)->displayEnabled());
        $this->assertDatabaseCount('settings', 0);
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

    public function test_daily_publication_is_immutable_and_new_day_uses_new_prices(): void
    {
        $this->save(1);
        $this->save(2);
        $publisher = app(PriceListPublisher::class);
        $first = $publisher->publish();
        DB::table('products')->where('id', 1)->update(['price' => 200]);
        $this->save(1, ['price' => 135]);
        $again = $publisher->publish();
        $this->assertEquals($first->id, $again->id);
        $this->assertSame($first->sha256, $again->sha256);
        $this->travel(1)->days();
        $next = $publisher->publish();
        $this->assertNotEquals($first->id, $next->id);
        $this->assertStringContainsString('<regular_price>200.00</regular_price>', Storage::disk('local')->get($next->path));
        $this->assertStringContainsString('<regular_price>100.00</regular_price>', Storage::disk('local')->get($first->path));
    }

    public function test_corrupt_and_expired_publications_cannot_be_downloaded(): void
    {
        $this->save(1);
        $this->save(2);
        $publication = app(PriceListPublisher::class)->publish();
        $this->travel(31)->days();
        $this->get(route('price-lists.download', $publication))->assertNotFound();
        $this->get(route('price-lists.latest'))->assertNotFound();
        $this->travelTo($publication->published_at);
        Storage::disk('local')->put($publication->path, '<bad/>');
        $this->get(route('price-lists.download', $publication))->assertNotFound();
        $this->artisan('prices:publish')->assertExitCode(1);
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
