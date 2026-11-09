<?php

namespace Tests\Feature;

use App\Helpers\Session\CheckoutSession;
use App\Http\Controllers\Front\CheckoutController;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\Transaction;
use App\Models\Front\Checkout\Order as CheckoutOrder;
use App\Models\Front\Checkout\Payment\MyPos;
use App\Models\Front\Checkout\Payment\Wspay;
use App\Services\CheckoutCompletion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckoutPaymentSecurityTest extends TestCase
{
    private static $privateKey;
    private static $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Bus::fake();

        if (! self::$privateKey) {
            $key = openssl_pkey_new(['private_key_bits' => 2048]);
            openssl_pkey_export($key, self::$privateKey);
            self::$publicKey = openssl_pkey_get_details($key)['key'];
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0);
            $table->integer('order_status_id')->default(8);
            $table->string('payment_code');
            $table->string('payment_email')->default('customer@example.test');
            $table->decimal('total', 15, 4)->default(100);
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity')->default(10);
            $table->boolean('decrease')->default(true);
            $table->timestamps();
        });
        Schema::create('product_option', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->integer('quantity')->default(10);
            $table->timestamps();
        });
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('product_id');
            $table->integer('quantity')->default(2);
            $table->string('sku')->default('security-test');
            $table->timestamps();
        });
        Schema::create('order_history', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('user_id')->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();
        });
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->boolean('success');
            $table->decimal('amount', 10, 2);
            foreach (['signature', 'payment_type', 'payment_plan', 'payment_partner', 'datetime',
                'approval_code', 'pg_order_id', 'lang', 'stan', 'error'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('key');
            $table->boolean('json')->default(true);
            $table->text('value');
        });
        $this->setting('mypos', [
            'test' => true,
            'mypos_virtual_configuration_package_test' => base64_encode(json_encode([
                'sid' => '000000000000010', 'pc' => self::$publicKey,
            ])),
        ]);
        $this->setting('wspay', ['shop_id' => 'TESTSHOP', 'secret_key' => 'test-only-secret', 'test' => true, 'callback' => route('checkout')]);
        $this->order(1, 'mypos');
    }

    public function test_real_notification_route_authenticates_and_settles_the_receipt(): void
    {
        $this->post(route('mypos.notify'), $this->signedMypos())->assertOk();
        $this->assertDatabaseHas('orders', ['id' => 1, 'order_status_id' => 3]);
        $this->assertDatabaseHas('products', ['id' => 1, 'quantity' => 8]);
    }

    public function test_real_browser_return_route_rejects_unsigned_order_id(): void
    {
        $this->post(route('checkout.mypos.success'), ['OrderID' => 'ABCD1'])->assertForbidden();
        $this->assertUnpaidAndUnchanged();
    }

    public function test_get_cannot_submit_a_manual_order(): void
    {
        CheckoutSession::setOrder(['id' => 1]);
        $this->get(route('checkout', ['order_number' => 1]))->assertStatus(405);
        $this->assertUnpaidAndUnchanged();
    }

    public function test_signed_notify_settles_once_including_inventory_and_confirmation(): void
    {
        $request = new Request($this->signedMypos());
        $gateway = new MyPos();

        $this->assertSame(200, $gateway->notify($request)->getStatusCode());
        $this->assertSame(200, $gateway->notify($request)->getStatusCode());
        $this->assertDatabaseHas('orders', ['id' => 1, 'order_status_id' => 3]);
        $this->assertDatabaseHas('products', ['id' => 1, 'quantity' => 8]);
        $this->assertDatabaseCount('order_transactions', 1);
        $this->assertDatabaseCount('order_history', 1);
        Bus::assertDispatchedAfterResponseTimes(CallQueuedClosure::class, 1);
        $this->assertDatabaseHas('order_transactions', ['approval_code' => 'gateway-transaction-1']);
        $this->assertSame(64, strlen(Transaction::first()->signature));
    }

    public function test_authenticated_rollback_cancels_payment_and_late_notify_cannot_repay_it(): void
    {
        $gateway = new MyPos();
        $gateway->notify(new Request($this->signedMypos()));
        $rollback = $this->signedMypos(['IPCmethod' => 'IPCPurchaseRollback']);
        $forged = array_merge($rollback, ['Signature' => 'forged']);
        $this->assertSame(400, $gateway->notify(new Request($forged))->getStatusCode());
        $this->assertDatabaseHas('orders', ['id' => 1, 'order_status_id' => 3]);
        $this->assertSame(200, $gateway->notify(new Request($rollback))->getStatusCode());
        $this->assertSame(200, $gateway->notify(new Request($rollback))->getStatusCode());
        $this->assertSame(409, $gateway->notify(new Request($this->signedMypos()))->getStatusCode());
        $this->assertDatabaseHas('orders', ['id' => 1, 'order_status_id' => 5]);
        $this->assertDatabaseHas('products', ['id' => 1, 'quantity' => 8]);
        $this->assertDatabaseCount('order_transactions', 1);
        $this->assertDatabaseCount('order_history', 2);
        Bus::assertDispatchedAfterResponseTimes(CallQueuedClosure::class, 1);
    }

    /** @dataProvider rejectedMyposReceipts */
    public function test_untrusted_or_mismatched_receipts_never_settle(array $changes, bool $signed = true): void
    {
        $payload = $this->signedMypos($changes);
        if (! $signed) {
            $payload['Signature'] = 'forged';
        }
        (new MyPos())->notify(new Request($payload));
        $this->assertUnpaidAndUnchanged();
    }

    public static function rejectedMyposReceipts(): array
    {
        return [
            'invalid signature' => [[], false],
            'wrong merchant' => [['SID' => '000000000000999']],
            'underpayment' => [['Amount' => '0.01']],
            'wrong currency' => [['Currency' => 'USD']],
            'wrong order' => [['OrderID' => 'ABCD999']],
            'malformed reference' => [['OrderID' => '1']],
            'failed status' => [['Status' => '21']],
            'browser success' => [['IPCmethod' => 'IPCPurchaseOK']],
            'cancellation' => [['IPCmethod' => 'IPCPurchaseCancel']],
            'pending status' => [['IPCmethod' => 'IPCGetTxnStatus']],
        ];
    }

    public function test_browser_return_requires_signature_and_does_not_settle_even_when_signed(): void
    {
        $gateway = new MyPos();
        $this->assertNull($gateway->returnedOrder(new Request(['OrderID' => 'ABCD1'])));
        $this->assertNotNull($gateway->returnedOrder(new Request($this->signedMypos(['IPCmethod' => 'IPCPurchaseOK']))));
        $this->assertUnpaidAndUnchanged();
    }

    public function test_public_success_page_cannot_turn_unfinished_checkout_into_paid_order(): void
    {
        CheckoutSession::setOrder(['id' => 1]);
        $controller = new class extends CheckoutController {
            public function __construct() {}
        };
        $response = $controller->success(new Request());
        $this->assertSame(route('kosarica'), $response->getTargetUrl());
        $this->assertUnpaidAndUnchanged();
    }

    public function test_order_submission_cannot_select_another_sessions_order(): void
    {
        CheckoutSession::setOrder(['id' => 1]);
        $controller = new class extends CheckoutController {
            public function __construct() {}
        };
        try {
            $controller->order(Request::create('/narudzba', 'POST', ['order_number' => 2]));
            $this->fail('An order outside the checkout session was accepted.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertUnpaidAndUnchanged();
    }

    public function test_generic_order_submission_cannot_forge_online_success(): void
    {
        CheckoutSession::setOrder(['id' => 1]);
        $controller = new class extends CheckoutController {
            public function __construct() {}
        };
        try {
            $controller->order(Request::create('/narudzba', 'POST', ['order_number' => 1, 'IPCmethod' => 'IPCPurchaseOK']));
            $this->fail('A browser request was accepted as a gateway receipt.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertUnpaidAndUnchanged();
    }

    public function test_a_finalized_order_cannot_be_rewritten_from_checkout(): void
    {
        DB::table('orders')->where('id', 1)->update(['order_status_id' => 3]);
        $this->assertNull((new CheckoutOrder())->updateData(['id' => 1]));
        $this->assertDatabaseHas('orders', ['id' => 1, 'order_status_id' => 3, 'total' => 100]);
    }

    /** @dataProvider offlineProviders */
    public function test_offline_orders_stay_unpaid_and_complete_once(string $provider): void
    {
        DB::table('orders')->where('id', 1)->update(['payment_code' => $provider]);
        $completion = app(CheckoutCompletion::class);
        $this->assertTrue($completion->offline(1));
        $this->assertTrue($completion->offline(1));
        $this->assertDatabaseHas('orders', ['id' => 1, 'order_status_id' => 1]);
        $this->assertDatabaseHas('products', ['id' => 1, 'quantity' => 8]);
        $this->assertDatabaseCount('order_history', 1);
        $this->assertDatabaseCount('order_transactions', 0);
        Bus::assertDispatchedAfterResponseTimes(CallQueuedClosure::class, 1);
    }

    public static function offlineProviders(): array
    {
        return [['bank'], ['cod'], ['pickup']];
    }

    public function test_wspay_requires_signed_response_and_the_recorded_outgoing_amount(): void
    {
        $this->wspayAttempt();
        $order = Order::find(1);
        $gateway = new Wspay($order);
        $payload = $this->signedWspay();
        $forged = array_merge($payload, ['Signature' => str_repeat('0', 128)]);
        $this->assertFalse($gateway->finishOrder($order, new Request($forged)));
        $this->assertUnpaidAndUnchanged(1);

        $this->assertTrue($gateway->finishOrder($order, new Request($payload)));
        $this->assertTrue($gateway->finishOrder($order, new Request($payload)));
        $this->assertDatabaseHas('orders', ['id' => 1, 'order_status_id' => 3]);
        $this->assertDatabaseHas('products', ['id' => 1, 'quantity' => 8]);
        $this->assertDatabaseCount('order_transactions', 2);
        $this->assertDatabaseCount('order_history', 1);
        Bus::assertDispatchedAfterResponseTimes(CallQueuedClosure::class, 1);
    }

    public function test_wspay_cannot_replay_cheap_receipt_after_cart_value_changes(): void
    {
        $this->wspayAttempt();
        DB::table('orders')->where('id', 1)->update(['total' => 200]);
        $order = Order::find(1);
        // Amount is not signed by WSPay, so changing it must not bypass the stored attempt.
        $payload = array_merge($this->signedWspay(), ['Amount' => '200.00']);
        $this->assertFalse((new Wspay($order))->finishOrder($order, new Request($payload)));
        $this->assertUnpaidAndUnchanged(1);
    }

    private function signedMypos(array $changes = []): array
    {
        $data = array_merge([
            'IPCmethod' => 'IPCPurchaseNotify', 'SID' => '000000000000010',
            'Amount' => '100.00', 'Currency' => 'EUR', 'OrderID' => 'ABCD1',
            'IPC_Trnref' => 'gateway-transaction-1', 'RequestSTAN' => '000001',
            'RequestDateTime' => '2026-11-09 13:00:00',
        ], $changes);
        openssl_sign(base64_encode(implode('-', array_values($data))), $signature, self::$privateKey, OPENSSL_ALGO_SHA256);
        $data['Signature'] = base64_encode($signature);
        return $data;
    }

    private function signedWspay(): array
    {
        $reference = Transaction::where('success', 0)->first()->pg_order_id;
        return [
            'ShoppingCartID' => $reference, 'Success' => '1', 'ApprovalCode' => 'APPROVED', 'Amount' => '100,00',
            'Signature' => hash('sha512', 'TESTSHOPtest-only-secret' . $reference
                . 'test-only-secret1test-only-secretAPPROVEDtest-only-secret'),
        ];
    }

    private function wspayAttempt(): void
    {
        DB::table('orders')->where('id', 1)->update(['payment_code' => 'wspay']);
        $settings = \App\Models\Back\Settings\Settings::get('payment', 'list.wspay');
        (new Wspay(Order::find(1)))->resolveFormView($settings);
    }

    private function setting(string $code, array $data): void
    {
        DB::table('settings')->insert([
            'code' => 'payment', 'key' => 'list.' . $code, 'json' => true,
            'value' => json_encode([[
                'code' => $code, 'title' => $code, 'status' => true, 'sort_order' => 1, 'data' => $data,
            ]]),
        ]);
    }

    private function order(int $id, string $provider): void
    {
        DB::table('orders')->insert(['id' => $id, 'payment_code' => $provider, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('products')->insert(['id' => $id]);
        DB::table('order_products')->insert(['order_id' => $id, 'product_id' => $id]);
    }

    private function assertUnpaidAndUnchanged(int $transactionCount = 0): void
    {
        $this->assertDatabaseHas('orders', ['id' => 1, 'order_status_id' => 8]);
        $this->assertDatabaseHas('products', ['id' => 1, 'quantity' => 10]);
        $this->assertDatabaseCount('order_transactions', $transactionCount);
        $this->assertDatabaseCount('order_history', 0);
        Bus::assertNotDispatchedAfterResponse(CallQueuedClosure::class);
    }
}
