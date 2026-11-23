<?php

namespace Tests\Feature;

use App\Models\Front\AgCart;
use App\Models\Front\Catalog\Product;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeletedCartProductTest extends TestCase
{
    public function test_cart_get_removes_deleted_products_by_model_id_and_preserves_other_skus_prices_and_options(): void
    {
        config([
            'database.default' => 'sqlite', 'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('create table products (id integer primary key, brand_id integer, sku text, price real, quantity integer, status integer)');
        DB::statement('create table product_translations (id integer primary key, product_id integer, lang text, name text)');
        DB::statement('create table product_option (id integer primary key, product_id integer, sku text, price real)');
        DB::statement('create table product_actions (id integer primary key, status integer, "group" text, user_group_id integer, date_start text, date_end text)');
        DB::table('products')->insert([
            ['id' => 1, 'brand_id' => 1, 'sku' => 'REMOVED-SKU', 'price' => 20, 'quantity' => 5, 'status' => 1],
            ['id' => 2, 'brand_id' => 1, 'sku' => 'SURVIVING-SKU', 'price' => 30, 'quantity' => 5, 'status' => 1],
        ]);
        DB::table('product_option')->insert(['id' => 17, 'product_id' => 2, 'sku' => 'SURVIVING-RED-M', 'price' => 7]);
        Cache::put('currency_main', (object) ['symbol_left' => '', 'symbol_right' => ' €', 'value' => 1, 'decimal_places' => 2]);
        $id = 'deleted-product-regression';
        $sessionCart = Cart::session($id);
        $options = ['option' => ['id' => 17, 'color' => 'red', 'size' => 'M']];
        foreach ([['REMOVED-SKU', 1, 20, 1, []], ['SURVIVING-SKU', 2, 30, 1, []], ['SURVIVING-RED-M', 2, 37, 2, $options]] as [$sku, $product, $price, $quantity, $selected]) {
            $sessionCart->add([
                'id' => $sku, 'name' => $sku, 'price' => $price, 'quantity' => $quantity,
                'associatedModel' => Product::findOrFail($product)->setAppends([]),
                'attributes' => ['slika' => '/media/snapshot.jpg', 'options' => $selected],
            ]);
        }
        $this->assertCount(3, $sessionCart->getContent());
        DB::table('products')->where('id', 1)->delete();
        // Exercise the real AgCart::get and CartPricing::refresh path. Only unrelated totals are isolated.
        $cart = new class($id) extends AgCart {
            public function getEur() { return null; }
            public function hasLoyalty(): int { return 0; }
            public function setCartConditions() { return []; }
        };
        $result = $cart->get();

        $this->assertFalse($result['items']->has('REMOVED-SKU'));
        $this->assertSame(['SURVIVING-RED-M', 'SURVIVING-SKU'], $result['items']->keys()->sort()->values()->all());
        $this->assertFalse(Cart::session($id)->getContent()->has('REMOVED-SKU'));
        $this->assertSame(3, $result['count']);
        $this->assertSame(30.0, (float) $result['items']->get('SURVIVING-SKU')->price);
        $this->assertSame(37.0, (float) $result['items']->get('SURVIVING-RED-M')->price);
        $this->assertSame(2, $result['items']->get('SURVIVING-RED-M')->quantity);
        $this->assertSame($options, $result['items']->get('SURVIVING-RED-M')->attributes['options']);
        $this->assertSame(2, $result['items']->get('SURVIVING-RED-M')->associatedModel->id);
    }
}
