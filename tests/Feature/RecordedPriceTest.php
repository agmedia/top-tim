<?php

namespace Tests\Feature;

use App\Models\Front\Catalog\Product;
use App\Services\RecordedPrices;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecordedPriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->travelTo(now()->setDate(2026, 11, 16)->setTime(12, 0));
        (require database_path('migrations/2026_11_16_180000_create_product_price_observations.php'))->up();
        DB::statement('create table products (id integer primary key, brand_id integer, price real, status integer)');
        DB::statement('create table product_actions (id integer primary key, status integer, "group" text, user_group_id integer, "lock" integer, links text, type text, discount real, coupon text, date_start text, date_end text, logged integer)');
        DB::table('products')->insert([
            ['id' => 1, 'brand_id' => 1, 'price' => 100, 'status' => 1],
            ['id' => 2, 'brand_id' => 2, 'price' => 50, 'status' => 1],
            ['id' => 3, 'brand_id' => 1, 'price' => 70, 'status' => 0],
            ['id' => 4, 'brand_id' => 1, 'price' => 0, 'status' => 1],
        ]);
    }

    public function test_seed_uses_current_public_campaign_prices_not_private_discounts(): void
    {
        DB::table('product_actions')->insert([
            ['id' => 1, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 10, 'user_group_id' => null, 'coupon' => null, 'logged' => 0],
            ['id' => 2, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 99, 'user_group_id' => 1, 'coupon' => null, 'logged' => 0],
            ['id' => 3, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 95, 'user_group_id' => null, 'coupon' => 'PRIVATE', 'logged' => 0],
            ['id' => 4, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 90, 'user_group_id' => null, 'coupon' => null, 'logged' => 1],
        ]);
        $this->artisan('prices:record', ['--missing-only' => true])->assertSuccessful();
        $this->assertDatabaseCount('product_price_observations', 2);
        $this->assertDatabaseHas('product_price_observations', ['product_id' => 1, 'price' => 90, 'source' => 'current_price_seed', 'observed_at' => now()->toDateTimeString()]);
        $this->assertDatabaseHas('products', ['id' => 1, 'price' => 100]);
        $value = Product::find(1)->recorded_lowest_price;
        $this->assertSame(90.0, $value['price']);
        $this->assertSame('Evidencija od 16.11.2026.', $value['note']);
        $html = view('front.catalog.recorded-price', ['product' => Product::find(1)])->render();
        $this->assertStringContainsString('Najniža zabilježena cijena', $html);
        $this->assertStringContainsString('90,00 €', $html);
        $this->assertStringNotContainsString('u zadnjih 30 dana', $html);
    }

    public function test_missing_only_preserves_existing_values_and_timestamps(): void
    {
        $service = app(RecordedPrices::class);
        $this->assertSame(2, $service->capture(true));
        $before = DB::table('product_price_observations')->get()->toArray();
        DB::table('products')->where('id', 1)->update(['price' => 80]);
        DB::table('products')->where('id', 3)->update(['status' => 1]);
        $this->travel(1)->days();
        $this->assertSame(1, $service->capture(true));
        $this->assertEquals($before, DB::table('product_price_observations')->whereIn('product_id', [1, 2])->get()->toArray());
        $this->assertSame(100.0, $service->forProduct(1)['price']);
    }

    public function test_regular_capture_keeps_old_prices_and_records_changes_without_duplicates(): void
    {
        $service = app(RecordedPrices::class);
        $service->capture();
        $this->travel(1)->days();
        $this->assertSame(0, $service->capture());
        $this->assertDatabaseCount('product_price_observations', 2);
        $this->assertDatabaseHas('product_price_observations', ['product_id' => 1, 'last_seen_at' => now()->toDateTimeString()]);
        DB::table('products')->where('id', 1)->update(['price' => 80]);
        $this->assertSame(1, $service->capture());
        $this->assertSame(80.0, $service->forProduct(1)['price']);
        DB::table('products')->where('id', 1)->update(['price' => 120]);
        $service->capture();
        $this->assertSame(80.0, $service->forProduct(1)['price']);
        $this->assertSame([100.0, 80.0, 120.0], DB::table('product_price_observations')->where('product_id', 1)->orderBy('id')->pluck('price')->map(fn ($p) => (float) $p)->all());
        $this->travel(31)->days();
        $service->capture();
        $this->assertSame(120.0, $service->forProduct(1)['price']);
        $this->assertSame('Zabilježene javne cijene u zadnjih 30 dana.', $service->forProduct(1)['note']);
        $this->assertSame(50.0, $service->forProduct(2)['price']);
    }

    public function test_uncaptured_or_stale_product_has_no_made_up_history(): void
    {
        $service = app(RecordedPrices::class);
        $this->assertNull($service->forProduct(1));
        $service->capture();
        $this->assertNull($service->forProduct(3));
        $this->travel(31)->days();
        $service->forget();
        $this->assertNull($service->forProduct(1));
    }
}
