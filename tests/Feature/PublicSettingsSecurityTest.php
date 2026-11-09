<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicSettingsSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('key');
            $table->boolean('json');
            $table->text('value');
        });
    }

    public function test_guest_settings_return_only_safe_currency_formatting_and_ignore_old_cache(): void
    {
        $currency = [
            'id' => 1, 'title' => ['hr' => 'Euro'], 'code' => 'EUR', 'symbol_left' => '',
            'symbol_right' => ' €', 'value' => 1, 'decimal_places' => 2,
            'status' => 1, 'main' => 1, 'sort_order' => 0,
        ];
        $this->setting('currency', 'list', [array_merge($currency, ['secret_key' => 'currency-secret'])]);
        $this->setting('payment', 'list.keks', [['data' => ['secret_key' => 'payment-secret']]]);
        $this->setting('payment', 'list.mypos', [['data' => ['mypos_cnf_key' => 'private-rsa-key']]]);
        $this->setting('shipping', 'list.gls', [['data' => ['password' => 'shipping-password']]]);
        $this->setting('currency', 'credentials', ['token' => 'unexpected-secret']);
        Helper::resolveCache('set')->put('cart', ['payment.list.keks' => 'cached-secret'], 600);

        $this->getJson('/api/v2/settings/get')
            ->assertOk()->assertExactJson(['currency.list' => [$currency]])
            ->assertDontSee('secret')->assertDontSee('private-rsa-key')->assertDontSee('password');
    }

    private function setting(string $code, string $key, array $value): void
    {
        DB::table('settings')->insert([
            'code' => $code, 'key' => $key, 'json' => true, 'value' => json_encode($value),
        ]);
    }
}
