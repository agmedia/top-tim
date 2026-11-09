<?php

namespace Tests\Feature;

use App\Models\Front\Checkout\Shipping\Gls;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\GlsAvailability;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OptionalGlsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'services.gls.labels_enabled' => false,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        require_once database_path('migrations/2020_01_02_223800_create_bouncer_tables.php');
        (new \CreateUsersTable())->up();
        (new \CreateBouncerTables())->up();
        Schema::table('users', function (Blueprint $table) { $table->softDeletes(); });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->boolean('printed')->default(false);
            $table->timestamps();
        });
        DB::table('orders')->insert(['id' => 1, 'printed' => false, 'created_at' => now(), 'updated_at' => now()]);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => Hash::make('test-password')]);
        UserDetail::create(['user_id' => $admin->id, 'fname' => 'Admin', 'role' => 'admin', 'status' => 1]);
        $admin->assign('admin');
        $this->actingAs($admin);
    }

    public function test_labels_are_disabled_by_default_even_if_soap_is_installed(): void
    {
        $this->instance(GlsAvailability::class, new class extends GlsAvailability {
            public function supportsSoap(): bool { return true; }
        });
        $this->postJson(route('api.order.send.gls'), ['order_id' => 1])
            ->assertStatus(503)->assertJsonPath('message', 'GLS izrada naljepnica trenutačno nije dostupna.');
        $this->assertDatabaseHas('orders', ['id' => 1, 'printed' => false]);
    }

    public function test_missing_soap_rejects_opted_in_label_requests_before_any_label_change(): void
    {
        config(['services.gls.labels_enabled' => true]);
        $this->instance(GlsAvailability::class, new class extends GlsAvailability {
            public function supportsSoap(): bool { return false; }
        });
        $this->postJson(route('api.order.send.gls'), ['order_id' => 1])->assertStatus(503);
        $this->assertDatabaseHas('orders', ['id' => 1, 'printed' => false]);
    }

    public function test_direct_service_calls_are_guarded_before_settings_or_external_calls(): void
    {
        config(['services.gls.labels_enabled' => true]);
        $this->instance(GlsAvailability::class, new class extends GlsAvailability {
            public function supportsSoap(): bool { return false; }
        });
        $memory = ini_get('memory_limit');
        try {
            (new Gls([]))->resolve();
            $this->fail('GLS service continued without SOAP.');
        } catch (HttpException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
        $this->assertSame($memory, ini_get('memory_limit'));
        $this->assertDatabaseHas('orders', ['id' => 1, 'printed' => false]);
    }

    public function test_explicit_opt_in_and_soap_allow_the_existing_integration_guard_to_pass(): void
    {
        config(['services.gls.labels_enabled' => true]);
        $availability = new class extends GlsAvailability {
            public function supportsSoap(): bool { return true; }
        };
        $availability->ensureAvailable();
        $this->assertTrue($availability->supportsSoap());
    }
}
