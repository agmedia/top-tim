<?php

namespace Tests\Feature;

use App\Helpers\Session\CheckoutSession;
use App\Http\Controllers\Front\CustomerController;
use App\Http\Livewire\Front\Checkout;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\CheckoutMethodResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
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
        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        require_once database_path('migrations/2020_01_02_223800_create_bouncer_tables.php');
        (new \CreateUsersTable())->up();
        (new \CreateBouncerTables())->up();
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('payment_email');
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
        Schema::create('pages', function (Blueprint $table) { $table->id(); });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('key');
            $table->boolean('json');
            $table->text('value');
        });
    }

    public function test_checkout_login_redirects_into_normal_two_factor_flow_and_preserves_checkout(): void
    {
        $user = $this->user();
        $user->forceFill([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['test-recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $checkout = new Checkout();
        $checkout->login = ['email' => $user->email, 'pass' => 'original-password', 'remember' => false];
        $response = $checkout->authUser();

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertFalse(Auth::check());
        $this->assertSame('', $checkout->login['pass']);
        $this->assertSame(route('naplata'), session('url.intended'));
        $this->post('/login', ['email' => $user->email, 'password' => 'original-password'])
            ->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_start_a_new_session(): void
    {
        $user = $this->user();
        $user->details()->update(['status' => 0]);
        $this->post('/login', ['email' => $user->email, 'password' => 'original-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_flash_message_does_not_dump_other_session_values(): void
    {
        $this->withSession(['message' => 'Login notice', 'private_probe' => 'must-not-render'])
            ->get('/login')->assertOk()->assertSee('Login notice')->assertDontSee('must-not-render');
    }

    public function test_inactive_account_loses_existing_session_and_tokens(): void
    {
        $user = $this->user();
        $user->createToken('existing');
        $this->actingAs($user);
        $user->details()->update(['status' => 0]);

        $this->getJson('/api/v2/products/autocomplete')->assertForbidden();
        $this->assertGuest();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_bearer_token_is_rejected_after_sanctum_authentication(): void
    {
        $user = $this->user();
        $token = $user->createToken('api')->plainTextToken;
        $user->details()->update(['status' => 0]);

        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$token])->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_soft_deleted_user_is_hidden_but_orders_are_preserved(): void
    {
        $user = $this->user();
        $token = $user->createToken('api')->plainTextToken;
        $this->order(10, $user->id, $user->email);
        $user->delete();

        $this->assertNull(User::find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id));
        $this->assertDatabaseHas('orders', ['id' => 10, 'user_id' => $user->id]);
        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
    }

    public function test_unverified_email_never_links_another_or_guest_order(): void
    {
        $user = $this->user('guest-buyer@example.test');
        $this->order(10, 0, $user->email);
        $this->order(11, 999, $user->email);
        $this->order(12, $user->id, 'different@example.test');
        $this->actingAs($user);
        $controller = new class extends CustomerController { public function __construct() {} };
        $data = $controller->orders(new Request())->getData();

        $this->assertSame([12], $data['orders']->pluck('id')->all());
    }

    public function test_password_reset_uses_hash_expiry_single_use_and_revokes_existing_token(): void
    {
        $user = $this->user();
        $user->createToken('existing');
        $token = Password::createToken($user);
        $stored = DB::table('password_resets')->where('email', $user->email)->value('token');
        $this->assertNotSame($token, $stored);
        $this->assertTrue(Hash::check($token, $stored));
        $this->post('/reset-password', $this->resetData($user, $token))->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-test-password', $user->fresh()->password));
        $this->assertDatabaseCount('password_resets', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->post('/reset-password', $this->resetData($user, $token))->assertSessionHasErrors('email');
    }

    public function test_expired_and_legacy_raw_reset_tokens_cannot_change_password(): void
    {
        $user = $this->user();
        $password = $user->password;
        $token = Password::createToken($user);
        DB::table('password_resets')->update(['created_at' => now()->subMinutes(61)]);
        $this->post('/reset-password', $this->resetData($user, $token))->assertSessionHasErrors('email');
        $this->assertSame($password, $user->fresh()->password);
        DB::table('password_resets')->update(['token' => 'legacy-raw-token', 'created_at' => now()]);
        $this->post('/reset-password', $this->resetData($user, 'legacy-raw-token'))->assertSessionHasErrors('email');
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_reset_requests_use_generic_response_and_broker_throttling(): void
    {
        $user = $this->user();
        Notification::fake();
        $first = $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        $message = session('status');
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        $this->assertDatabaseCount('password_resets', 1);
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertRedirect();
        $this->assertSame($message, session('status'));
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        $this->post('/forgot-password', ['email' => $user->email])->assertTooManyRequests();
    }

    public function test_first_country_in_geo_zone_is_matched_and_shipping_code_is_validated(): void
    {
        $this->checkoutSettings();
        $resolver = app(CheckoutMethodResolver::class);
        $this->assertSame(['flat'], $resolver->shipping('Hrvatska')->pluck('code')->all());
        $this->assertSame(['international'], $resolver->shipping('Germany')->pluck('code')->all());
        $checkout = new Checkout();
        $checkout->address['state'] = 'Germany';
        try {
            $checkout->selectShipping('flat');
            $this->fail('An out-of-zone shipping method was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('shipping', $exception->errors());
        }
        $this->assertFalse(CheckoutSession::hasShipping());
        $checkout->selectShipping('international');
        $this->assertSame('international', CheckoutSession::getShipping());
        [$shipping, $payment] = $resolver->resolve('Germany', 'international', 'bank');
        $this->assertSame('international', $shipping->code);
        $this->assertSame('bank', $payment->code);
    }

    public function test_resolver_rejects_out_of_zone_or_disabled_payment_methods(): void
    {
        $this->checkoutSettings();
        try {
            app(CheckoutMethodResolver::class)->resolve('Germany', 'international', 'domestic');
            $this->fail('An out-of-zone payment method was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }
        $checkout = new Checkout();
        $checkout->address['state'] = 'Hrvatska';
        $checkout->shipping = 'flat';
        $this->expectException(ValidationException::class);
        $checkout->selectPayment('disabled');
    }

    private function user(string $email = 'test@example.test'): User
    {
        $user = User::create(['name' => 'Test User', 'email' => $email, 'password' => Hash::make('original-password')]);
        UserDetail::create(['user_id' => $user->id, 'fname' => 'Test', 'role' => 'customer', 'status' => 1]);
        return $user;
    }

    private function order(int $id, int $userId, string $email): void
    {
        DB::table('orders')->insert(['id' => $id, 'user_id' => $userId, 'payment_email' => $email, 'total' => 100, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function resetData(User $user, string $token): array
    {
        return ['email' => $user->email, 'token' => $token, 'password' => 'new-test-password', 'password_confirmation' => 'new-test-password'];
    }

    private function checkoutSettings(): void
    {
        $this->setting('geo_zone', 'list', [
            ['id' => 1, 'title' => 'Croatia', 'state' => ['Hrvatska'], 'status' => 1, 'sort_order' => 1],
            ['id' => 2, 'title' => 'Germany', 'state' => ['Germany'], 'status' => 1, 'sort_order' => 2],
            ['id' => 3, 'title' => 'All', 'state' => [], 'status' => 1, 'sort_order' => 3],
        ]);
        foreach ([['flat',1,2], ['international',2,15]] as [$code,$zone,$price]) {
            $this->setting('shipping', 'list.'.$code, [['code'=>$code,'title'=>$code,'geo_zone'=>$zone,'status'=>1,'sort_order'=>1,'data'=>['price'=>$price]]]);
        }
        foreach ([['bank',3,1], ['domestic',1,1], ['disabled',3,0]] as [$code,$zone,$active]) {
            $this->setting('payment', 'list.'.$code, [['code'=>$code,'title'=>$code,'geo_zone'=>$zone,'status'=>$active,'sort_order'=>1,'data'=>[]]]);
        }
    }

    private function setting(string $code, string $key, array $value): void
    {
        DB::table('settings')->insert(['code'=>$code,'key'=>$key,'json'=>true,'value'=>json_encode($value)]);
    }
}
