<?php

namespace Tests\Feature;

use App\Helpers\Currency;
use App\Models\CustomerDiscount;
use App\Models\Front\AgCart;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Checkout\Order;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\QuantityDiscounts;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuantityDiscountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        session(['locale' => current_locale(), config('session.cart') => 'quantity-test']);
        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        require_once database_path('migrations/2020_01_02_223800_create_bouncer_tables.php');
        (new \CreateUsersTable)->up();
        (new \CreateBouncerTables)->up();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('user_details', fn (Blueprint $table) => $table->unsignedInteger('user_group_id')->nullable());
        (require database_path('migrations/2026_11_12_180000_create_customer_discounts_table.php'))->up();
        DB::statement('create table user_group (id integer primary key, status integer)');
        DB::statement('create table user_group_translations (id integer primary key, user_group_id integer, lang text, title text)');
        DB::statement('create table brands (id integer primary key, status integer)');
        DB::statement('create table brand_translations (id integer primary key, brand_id integer, lang text, title text)');
        DB::statement('create table products (id integer primary key, brand_id integer, price real, status integer, quantity integer)');
        DB::statement('create table product_translations (id integer primary key, product_id integer, lang text, title text)');
        DB::statement('create table product_option (id integer primary key, product_id integer, price real, sku text)');
        DB::statement('create table product_actions (id integer primary key, status integer, "group" text, user_group_id integer, "lock" integer, links text, type text, discount real, coupon text, date_start text, date_end text)');
        DB::statement('create table pages (id integer primary key)');
        DB::statement('create table settings (id integer primary key, code text, "key" text, value text, json integer)');
        DB::statement('create table order_products (id integer primary key, order_id integer, product_id integer, sku text, image text, name text, quantity integer, org_price real, discount real, price real, total real, created_at text, updated_at text)');
        DB::table('user_group')->insert(['id' => 1, 'status' => 1]);
        DB::table('user_group_translations')->insert(['user_group_id' => 1, 'lang' => current_locale(), 'title' => 'VIP']);
        DB::table('brands')->insert([['id' => 1, 'status' => 1], ['id' => 2, 'status' => 1]]);
        DB::table('brand_translations')->insert(['brand_id' => 1, 'lang' => current_locale(), 'title' => 'Macron']);
        DB::table('products')->insert([
            ['id' => 1, 'brand_id' => 1, 'price' => 100, 'status' => 1, 'quantity' => 100],
            ['id' => 2, 'brand_id' => 1, 'price' => 50, 'status' => 1, 'quantity' => 100],
            ['id' => 3, 'brand_id' => 2, 'price' => 100, 'status' => 1, 'quantity' => 100],
        ]);
        Cache::put('currency_main', (object) ['symbol_left' => '', 'symbol_right' => ' €', 'value' => 1, 'decimal_places' => 2]);
    }

    public function test_admin_can_create_edit_disable_and_delete_rules_and_search_users(): void
    {
        $admin = $this->user('admin');
        $customer = $this->user('customer');
        $data = ['target' => 'user', 'user_id' => $customer->id, 'brand_id' => 1, 'min_quantity' => 10, 'percentage' => 12.5, 'active' => 1];
        $this->actingAs($admin)->post(route('discounts.store'), $data)->assertRedirect(route('discounts'));
        $rule = CustomerDiscount::firstOrFail();
        $this->assertSame($customer->id, $rule->user_id);
        $this->assertNull($rule->user_group_id);
        $this->get(route('discounts'))->assertOk()->assertSee('Macron')->assertSee('12,50');
        $this->get(route('discounts.edit', $rule))->assertOk()->assertSee($customer->email);
        $this->getJson(route('discounts.users', ['q' => $customer->email]))->assertOk()->assertJsonPath('results.0.id', $customer->id);
        $this->patch(route('discounts.update', $rule), array_merge($data, ['target' => 'group', 'user_group_id' => 1, 'active' => 0]))->assertRedirect();
        $this->assertDatabaseHas('customer_discounts', ['id' => $rule->id, 'user_id' => null, 'user_group_id' => 1, 'active' => 0]);
        $this->delete(route('discounts.destroy', $rule))->assertRedirect();
        $this->assertDatabaseCount('customer_discounts', 0);
    }

    public function test_migration_only_creates_its_own_table_and_preserves_existing_data_and_schema(): void
    {
        $this->user('customer', 1);
        $migration = require database_path('migrations/2026_11_12_180000_create_customer_discounts_table.php');
        $migration->down();
        $tables = ['users', 'user_details', 'user_group', 'brands', 'products', 'product_option', 'product_actions', 'order_products'];
        $snapshot = fn () => collect($tables)->mapWithKeys(fn ($table) => [$table => [
            'columns' => Schema::getColumns($table),
            'rows' => DB::table($table)->orderBy('id')->get()->toArray(),
        ]])->all();
        $before = $snapshot();

        $migration->up();
        $this->assertTrue(Schema::hasTable('customer_discounts'));
        $this->assertEquals($before, $snapshot());
        $this->assertDatabaseCount('customer_discounts', 0);

        $migration->down();
        $this->assertFalse(Schema::hasTable('customer_discounts'));
        $this->assertEquals($before, $snapshot());
    }

    public function test_invalid_rules_and_unprivileged_access_are_rejected(): void
    {
        $data = ['target' => 'group', 'user_group_id' => 1, 'brand_id' => 1, 'min_quantity' => 10, 'percentage' => 5, 'active' => 1];
        $this->postJson(route('discounts.store'), $data)->assertUnauthorized();
        foreach (['customer', 'editor'] as $role) {
            $this->actingAs($this->user($role))->postJson(route('discounts.store'), $data)->assertForbidden();
            $this->getJson(route('discounts.users'))->assertForbidden();
        }
        $this->actingAs($this->user('admin'));
        foreach ([['min_quantity' => 0], ['min_quantity' => 1.5], ['percentage' => -1], ['percentage' => 101],
            ['percentage' => 5.123], ['brand_id' => 999], ['user_group_id' => 999], ['target' => 'user', 'user_id' => 999]] as $invalid) {
            $this->postJson(route('discounts.store'), array_merge($data, $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('customer_discounts', 0);
    }

    public function test_best_personal_or_group_tier_is_scoped_by_brand_customer_and_quantity(): void
    {
        $customer = $this->user('customer', 1);
        $this->rule(['user_group_id' => 1, 'min_quantity' => 5, 'percentage' => 10]);
        $this->rule(['user_id' => $customer->id, 'min_quantity' => 10, 'percentage' => 20]);
        $this->rule(['user_id' => $customer->id, 'min_quantity' => 10, 'percentage' => 99, 'active' => 0]);
        $this->actingAs($customer);
        $pricing = app(QuantityDiscounts::class);
        $this->assertSame(100.0, $pricing->price(100, 100, 1, 4));
        $this->assertSame(90.0, $pricing->price(100, 100, 1, 5));
        $this->assertSame(80.0, $pricing->price(100, 100, 1, 10));
        $this->assertSame(70.0, $pricing->price(100, 70, 1, 10));
        $this->assertSame(100.0, $pricing->price(100, 100, 2, 100));
        $this->assertCount(2, $pricing->tiers(1));
        $this->actingAs($this->user('customer', 1));
        $this->assertSame(90.0, $pricing->price(100, 100, 1, 10));
        $this->actingAs($this->user('customer'));
        $this->assertSame([], $pricing->tiers(1));
        Auth::logout();
        $this->assertSame(100.0, $pricing->price(100, 100, 1, 100));
    }

    public function test_inactive_group_and_changed_membership_take_effect_on_the_next_request(): void
    {
        $customer = $this->user('customer', 1);
        $this->rule(['user_group_id' => 1]);
        $this->actingAs($customer);
        $this->assertSame(90.0, app(QuantityDiscounts::class)->price(100, 100, 1, 10));
        DB::table('user_group')->update(['status' => 0]);
        $this->app->instance('request', Request::create('/'));
        $this->assertSame(100.0, app(QuantityDiscounts::class)->price(100, 100, 1, 10));
        DB::table('user_group')->update(['status' => 1]);
        $customer->details()->update(['user_group_id' => null]);
        $this->app->instance('request', Request::create('/'));
        $this->assertSame(100.0, app(QuantityDiscounts::class)->price(100, 100, 1, 10));
    }

    public function test_catalog_reuses_one_rule_query_and_admin_changes_apply_on_the_next_request(): void
    {
        $customer = $this->user('customer');
        $rule = $this->rule(['user_id' => $customer->id, 'min_quantity' => 1]);
        $this->actingAs($customer);
        DB::enableQueryLog();
        DB::flushQueryLog();
        for ($i = 0; $i < 50; $i++) {
            $this->assertSame(90.0, app(QuantityDiscounts::class)->price(100, 100, 1, 1));
        }
        $this->assertCount(1, DB::getQueryLog());
        $rule->update(['percentage' => 25]);
        $this->app->instance('request', Request::create('/'));
        $this->assertSame(75.0, app(QuantityDiscounts::class)->price(100, 100, 1, 1));
        $rule->update(['active' => false]);
        $this->app->instance('request', Request::create('/'));
        $this->assertSame(100.0, app(QuantityDiscounts::class)->price(100, 100, 1, 1));
    }

    public function test_cart_reprices_every_line_on_crossing_threshold_removal_and_logout(): void
    {
        $customer = $this->user('customer', 1);
        $this->rule(['user_group_id' => 1]);
        $this->actingAs($customer);
        $cart = Cart::session('quantity-test');
        $this->line('first', 1, 4);
        $this->line('second', 2, 5);
        $this->line('other-brand', 3, 50);
        $adapter = new QuantityTestCart('quantity-test');
        $before = $adapter->get();
        $this->assertSame(100.0, $before['items']['first']->attributes['unit_price']);
        $cart->update('second', ['quantity' => ['relative' => false, 'value' => 6]]);
        $after = $adapter->get();
        $this->assertSame(90.0, $after['items']['first']->attributes['unit_price']);
        $this->assertSame(45.0, $after['items']['second']->attributes['unit_price']);
        $this->assertSame(100.0, $after['items']['other-brand']->attributes['unit_price']);
        $this->assertEquals(5630, $after['subtotal']);
        $this->assertSame(90.0, (float) Product::find(1)->special());
        $removed = $adapter->remove('second');
        $this->assertSame(100.0, $removed['items']['first']->attributes['unit_price']);
        $this->line('second', 2, 6);
        $this->assertSame(90.0, $adapter->get()['items']['first']->attributes['unit_price']);
        Auth::logout();
        $this->assertSame(100.0, $adapter->get()['items']['first']->attributes['unit_price']);
    }

    public function test_variant_surcharge_and_order_lines_use_the_same_discounted_price(): void
    {
        $customer = $this->user('customer');
        $this->rule(['user_id' => $customer->id, 'min_quantity' => 2, 'percentage' => 12.5]);
        $this->actingAs($customer);
        DB::table('product_option')->insert(['id' => 10, 'product_id' => 1, 'price' => 6.90, 'sku' => 'variant']);
        $this->line('variant', 1, 2, ['options' => ['option' => ['id' => 10]]]);
        $response = (new QuantityTestCart('quantity-test'))->get();
        $this->assertSame(93.54, $response['items']['variant']->attributes['unit_price']);
        $this->assertEquals(187.08, $response['subtotal']);
        $order = new Order(['cart' => $response]);
        (new \ReflectionMethod($order, 'updateProducts'))->invoke($order, 42);
        $this->assertDatabaseHas('order_products', ['order_id' => 42, 'org_price' => 106.9, 'price' => 93.54, 'total' => 187.08]);
    }

    public function test_existing_campaign_is_preserved_when_better_and_free_items_format_as_zero(): void
    {
        $customer = $this->user('customer');
        $this->rule(['user_id' => $customer->id, 'min_quantity' => 1]);
        $this->actingAs($customer);
        DB::table('product_actions')->insert(['id' => 1, 'status' => 1, 'group' => 'all', 'type' => 'P', 'discount' => 25]);
        $this->line('first', 1, 1);
        $this->assertSame(75.0, (new QuantityTestCart('quantity-test'))->get()['items']['first']->attributes['unit_price']);
        $this->assertSame(75.0, (float) Product::find(1)->special());
        $this->rule(['user_id' => $customer->id, 'min_quantity' => 1, 'percentage' => 100]);
        $this->app->instance('request', Request::create('/'));
        $response = (new QuantityTestCart('quantity-test'))->get();
        $this->assertSame(0.0, $response['items']['first']->attributes['unit_price']);
        $this->assertEquals(0, $response['total']);
        $this->assertSame('0,00 €', Currency::main(0, true));
    }

    public function test_cart_check_returns_the_repriced_cart_expected_by_the_storefront(): void
    {
        $cart = \Mockery::mock(AgCart::class);
        $cart->shouldReceive('check')->once()->andReturn(null);
        $cart->shouldReceive('get')->once()->andReturn(['items' => [], 'total' => 0, 'count' => 0]);
        $controller = new \App\Http\Controllers\Api\v2\CartController;
        (new \ReflectionProperty($controller, 'cart'))->setValue($controller, $cart);
        $response = $controller->check(new Request(['ids' => []]));
        $this->assertSame(['cart' => ['items' => [], 'total' => 0, 'count' => 0], 'message' => null], $response->getData(true));
    }

    private function user(string $role, ?int $group = null): User
    {
        $count = User::count() + 1;
        $user = User::create(['name' => 'User ' . $count, 'email' => 'user' . $count . '@example.test', 'password' => 'unused']);
        UserDetail::create(['user_id' => $user->id, 'fname' => $user->name, 'role' => $role, 'status' => 1, 'user_group_id' => $group]);
        $user->assign($role);
        return $user;
    }

    private function rule(array $data): CustomerDiscount
    {
        return CustomerDiscount::create(array_merge(['brand_id' => 1, 'min_quantity' => 10, 'percentage' => 10, 'active' => true], $data));
    }

    private function line(string $sku, int $product, int $quantity, array $attributes = []): void
    {
        Cart::session('quantity-test')->add([
            'id' => $sku, 'name' => $sku, 'price' => 1, 'quantity' => $quantity,
            'associatedModel' => Product::find($product)->setAppends([]),
            'attributes' => array_merge(['slika' => '', 'options' => []], $attributes),
        ]);
    }
}

class QuantityTestCart extends AgCart
{
    public function getEur() { return null; }
    public function hasLoyalty(): int { return 0; }
    public function setCartConditions() { return []; }
}
