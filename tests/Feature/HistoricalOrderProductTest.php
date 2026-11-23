<?php

namespace Tests\Feature;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class HistoricalOrderProductTest extends TestCase
{
    private string $views;

    protected function setUp(): void
    {
        parent::setUp();
        $this->views = sys_get_temp_dir() . '/top-tim-order-view-' . bin2hex(random_bytes(6));
        // Render the real order templates with only their unrelated page shells isolated.
        foreach ([
            'back/layouts/backend' => "@yield('content')",
            'front/layouts/app' => "@yield('content')",
            'back/layouts/partials/session' => '',
            'front/customer/layouts/header' => '',
            'front/customer/layouts/sidebar' => '',
        ] as $name => $content) {
            $path = $this->views . '/' . $name . '.blade.php';
            if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
            file_put_contents($path, $content);
        }
        $this->app['view']->getFinder()->prependLocation($this->views);
        $this->app['view']->getFinder()->flush();
    }

    protected function tearDown(): void
    {
        if (isset($this->views)) $this->removeTree($this->views);
        parent::tearDown();
    }

    public function test_admin_order_keeps_snapshot_names_skus_prices_and_images_after_product_removal(): void
    {
        $saved = $this->snapshot('Retired box', 'BOX-SNAPSHOT');
        $legacy = $this->snapshot('Legacy box', null);
        $order = $this->order([$saved, $legacy]);
        $before = $saved->getAttributes();
        $html = view('back.order.show', ['order' => $order, 'statuses' => collect()])->render();

        $this->assertStringContainsString('Retired box -  BOX-SNAPSHOT', $html);
        $this->assertStringContainsString('Legacy box -  —', $html);
        $this->assertStringContainsString('media/img/products/77/snapshot.jpg', $html);
        $this->assertStringContainsString('20,00', $html);
        $this->assertStringContainsString('30,00', $html);
        $this->assertSame($before, $saved->getAttributes());
    }

    public function test_customer_order_renders_removed_product_as_snapshot_and_preserves_existing_product_links(): void
    {
        $deleted = $this->snapshot('Retired box', 'BOX-SNAPSHOT');
        $existing = $this->snapshot('Current box', 'CURRENT-SNAPSHOT');
        $existing->setRelation('real', (object) ['url' => 'fan-shop/current-box']);
        $order = $this->order([$deleted, $existing]);
        $orders = new LengthAwarePaginator([$order], 1, 10);
        $html = view('front.customer.moje-narudzbe', compact('orders'))->render();
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);

        $this->assertSame(0, $xpath->query('//a[contains(., "Retired box")]')->length);
        $this->assertSame(1, $xpath->query('//h3[contains(., "Retired box")]')->length);
        $this->assertSame(1, $xpath->query('//a[contains(., "Current box") and contains(@href, "fan-shop/current-box")]')->length);
        $this->assertStringContainsString('media/img/products/77/snapshot.jpg', $html);
        $this->assertStringContainsString('15,00', $html);
        $this->assertStringContainsString('30,00', $html);
    }

    public function test_stock_decrease_skips_removed_catalog_items_without_changing_the_order_snapshot(): void
    {
        $deleted = $this->snapshot('Retired box', 'BOX-SNAPSHOT');
        $before = $deleted->getAttributes();
        $order = new Order();
        $this->assertSame($order, $order->decreaseItems(new Collection([$deleted])));
        $this->assertSame($before, $deleted->getAttributes());
    }

    private function snapshot(string $name, ?string $sku): OrderProduct
    {
        return (new OrderProduct([
            'product_id' => 77, 'name' => $name, 'sku' => $sku,
            'image' => 'media/img/products/77/snapshot.jpg', 'quantity' => 2,
            'org_price' => 20, 'price' => 15, 'discount' => 25, 'total' => 30,
        ]))->setRelation('product', null)->setRelation('real', null);
    }

    private function order(array $products): object
    {
        return (object) array_merge(array_fill_keys([
            'shipping_fname', 'shipping_lname', 'shipping_address', 'shipping_zip', 'shipping_city',
            'shipping_state', 'company', 'oib', 'shipping_phone', 'shipping_email', 'comment',
        ], ''), [
            'id' => 9, 'products' => collect($products), 'totals' => collect(), 'history' => collect(),
            'created_at' => '2026-11-20 10:00:00', 'total' => 60,
            'status' => (object) ['title' => (object) [current_locale() => 'Received']],
        ]);
    }

    private function removeTree(string $path): void
    {
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $item) {
            if ($item->isDir()) $this->removeTree($item->getPathname());
            else unlink($item->getPathname());
        }
        rmdir($path);
    }
}
