<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use App\Services\PriceListPublisher;
use App\Services\RecordedPrices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EveryoneDiscountPublicPriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->travelTo(now()->setDate(2026, 11, 23)->setTime(12, 0));
        Storage::fake('local');
        session(['locale' => 'hr']);
        (require database_path('migrations/2026_11_12_180000_create_customer_discounts_table.php'))->up();
        (require database_path('migrations/2026_11_23_190000_add_everyone_to_customer_discounts.php'))->up();
        (require database_path('migrations/2026_11_16_180000_create_product_price_observations.php'))->up();
        (require database_path('migrations/2026_11_12_190000_create_anchor_price_tables.php'))->up();
        (require database_path('migrations/2026_11_18_080000_version_webshop_price_lists.php'))->up();
        DB::statement('create table products (id integer primary key, brand_id integer, sku text, ean text, price real, status integer, quantity integer)');
        DB::statement('create table product_translations (id integer primary key, product_id integer, lang text, name text)');
        DB::statement('create table brand_translations (id integer primary key, brand_id integer, lang text, title text)');
        DB::statement('create table product_option (id integer primary key, product_id integer, parent_id integer, option_id integer, sku text, price real, quantity integer)');
        DB::statement('create table options_translations (option_id integer, lang text, title text)');
        DB::statement('create table product_actions (id integer primary key, status integer, "group" text, user_group_id integer, "lock" integer, links text, type text, discount real, coupon text, date_start text, date_end text, logged integer)');
        DB::statement('create table product_actions_translations (id integer primary key, product_action_id integer, lang text, title text)');
        DB::table('products')->insert([
            ['id' => 1, 'brand_id' => 1, 'sku' => 'PUBLIC-A', 'ean' => '', 'price' => 100, 'status' => 1, 'quantity' => 5],
            ['id' => 2, 'brand_id' => 2, 'sku' => 'PUBLIC-B', 'ean' => '', 'price' => 50, 'status' => 1, 'quantity' => 5],
        ]);
        foreach ([1, 2] as $id) {
            DB::table('product_translations')->insert(['product_id' => $id, 'lang' => 'hr', 'name' => 'Public product ' . $id]);
            DB::table('brand_translations')->insert(['brand_id' => $id, 'lang' => 'hr', 'title' => 'Public brand ' . $id]);
            DB::table('anchor_prices')->insert(['product_id' => $id, 'price' => 150, 'reference_date' => '2026-09-10',
                'status' => 'confirmed', 'note' => 'Synthetic evidence']);
        }
        DB::table('product_actions')->insert(['id' => 1, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 10,
            'user_group_id' => null, 'coupon' => null, 'logged' => 0]);
        DB::table('product_actions_translations')->insert(['product_action_id' => 1, 'lang' => 'hr', 'title' => 'Public campaign']);
        foreach ([
            ['applies_to_everyone' => true, 'percentage' => 20],
            ['applies_to_everyone' => true, 'min_quantity' => 2, 'percentage' => 90],
            ['user_id' => 7, 'percentage' => 99],
            ['user_group_id' => 8, 'percentage' => 98],
            ['percentage' => 97], // Legacy null/null must remain private/inert.
            ['applies_to_everyone' => true, 'user_id' => 7, 'percentage' => 96], // Malformed scope is never public.
            ['applies_to_everyone' => true, 'active' => false, 'percentage' => 95],
        ] as $rule) {
            DB::table('customer_discounts')->insert(array_replace(['user_id' => null, 'user_group_id' => null,
                'applies_to_everyone' => false, 'brand_id' => 1, 'min_quantity' => 1, 'percentage' => 10,
                'active' => true], $rule));
        }
        $user = (new User())->forceFill(['id' => 7]);
        $user->setRelation('details', new UserDetail(['user_group_id' => 8, 'status' => 1]));
        $this->actingAs($user);
        session([config('session.cart') . '_coupon' => 'PRIVATE', config('session.cart') => 'private-cart']);
    }

    public function test_capture_records_only_unconditional_public_rules_and_preserves_earlier_history(): void
    {
        DB::table('product_price_observations')->insert(['product_id' => 1, 'price' => 90,
            'observed_at' => now()->subDay(), 'last_seen_at' => now()->subDay(), 'source' => 'existing_observation']);
        $before = DB::table('product_price_observations')->first();
        $this->assertSame(2, app(RecordedPrices::class)->capture());
        $this->assertSame(80.0, (float) DB::table('product_price_observations')->where('product_id', 1)->orderByDesc('id')->value('price'));
        $this->assertSame(45.0, (float) DB::table('product_price_observations')->where('product_id', 2)->value('price'));
        $this->assertEquals($before, DB::table('product_price_observations')->where('id', $before->id)->first());
        $this->assertDatabaseHas('products', ['id' => 1, 'price' => 100]);
    }

    public function test_public_xml_applies_the_same_rule_to_the_whole_variant_price_without_private_or_quantity_two_rules(): void
    {
        $this->variant();
        $publication = app(PriceListPublisher::class)->publish();
        $xml = simplexml_load_string(Storage::disk('local')->get($publication->path));
        $this->assertSame('PUBLIC-A-M', (string) $xml->products->product[0]->sku);
        $this->assertSame('120.00', (string) $xml->products->product[0]->regular_price);
        $this->assertSame('96.00', (string) $xml->products->product[0]->current_price);
        $this->assertSame('96.00', (string) $xml->products->product[0]->unit_price);
        $this->assertSame('da', (string) $xml->products->product[0]->special_offer);
        $this->assertSame('Popust za sve kupce', (string) $xml->products->product[0]->special_offer_name);
        $this->assertSame('45.00', (string) $xml->products->product[1]->current_price);
        $this->assertSame('Public campaign', (string) $xml->products->product[1]->special_offer_name);
    }

    public function test_better_campaign_wins_in_capture_and_variant_xml_without_stacking(): void
    {
        DB::table('product_actions')->where('id', 1)->update(['discount' => 30]);
        app(RecordedPrices::class)->capture();
        $this->assertSame(70.0, (float) DB::table('product_price_observations')->where('product_id', 1)->value('price'));
        $this->variant();
        $publication = app(PriceListPublisher::class)->publish();
        $xml = simplexml_load_string(Storage::disk('local')->get($publication->path));
        $this->assertSame('84.00', (string) $xml->products->product[0]->current_price);
        $this->assertSame('Public campaign', (string) $xml->products->product[0]->special_offer_name);
    }

    private function variant(): void
    {
        DB::table('product_option')->insert(['id' => 11, 'product_id' => 1, 'parent_id' => 0, 'option_id' => 11,
            'sku' => 'PUBLIC-A-M', 'price' => 20, 'quantity' => 3]);
        DB::table('options_translations')->insert(['option_id' => 11, 'lang' => 'hr', 'title' => 'M']);
        DB::table('anchor_prices')->where('product_id', 1)->update(['variant_prices' => json_encode([
            '0:11' => ['price' => 150, 'status' => 'confirmed', 'reference_date' => '2026-09-10'],
        ])]);
    }
}
