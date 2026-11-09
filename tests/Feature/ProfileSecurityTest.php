<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
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

        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        require_once database_path('migrations/2020_01_02_223800_create_bouncer_tables.php');
        (new \CreateUsersTable())->up();
        (new \CreateBouncerTables())->up();
        Schema::table('user_details', function (Blueprint $table) {
            $table->unsignedInteger('user_group_id')->nullable();
            $table->string('company')->nullable();
            $table->string('oib')->nullable();
        });
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
        });
    }

    public function test_customer_profile_ignores_administrative_fields_and_updates_contact_details(): void
    {
        $customer = $this->user('customer');
        $password = $customer->password;

        $this->actingAs($customer)->patch(route('moj-racun.snimi', $customer), array_merge($this->profile(), [
            'role' => 'superadmin',
            'username' => 'Injected administrator',
            'email' => 'attacker@example.test',
            'password' => 'injected-password',
            'loyalty_points' => 100000,
            'user_id' => $customer->id,
            'user_group' => 999,
            'status' => 'off',
        ]))->assertRedirect(route('moj-racun'))->assertSessionHas('success');

        $customer->refresh();
        $this->assertSame('Test User', $customer->name);
        $this->assertSame('user1@example.test', $customer->email);
        $this->assertSame($password, $customer->password);
        $this->assertSame(['customer'], $customer->roles()->pluck('name')->all());
        $this->assertDatabaseHas('user_details', [
            'user_id' => $customer->id,
            'fname' => 'Updated',
            'address' => 'New address 1',
            'role' => 'customer',
            'status' => 1,
            'user_group_id' => null,
        ]);
    }

    public function test_customer_cannot_edit_another_account_or_replace_its_password(): void
    {
        $customer = $this->user('customer');
        $target = $this->user('admin');
        $password = $target->password;

        $this->actingAs($customer)->patchJson(route('moj-racun.snimi', $target), array_merge($this->profile(), [
            'role' => 'customer',
            'password' => 'replacement-password',
        ]))->assertForbidden();

        $this->assertSame($password, $target->fresh()->password);
        $this->assertSame(['admin'], $target->roles()->pluck('name')->all());
        $this->assertDatabaseHas('user_details', ['user_id' => $target->id, 'fname' => 'Original']);
    }

    public function test_staff_saving_own_customer_profile_keeps_staff_role(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->patch(route('moj-racun.snimi', $admin), $this->profile())
            ->assertRedirect(route('moj-racun'));

        $this->assertSame(['admin'], $admin->roles()->pluck('name')->all());
        $this->assertDatabaseHas('user_details', ['user_id' => $admin->id, 'role' => 'admin', 'fname' => 'Updated']);
    }

    /** @dataProvider untrustedRoles */
    public function test_only_explicit_staff_roles_can_use_administrative_apis(?string $role): void
    {
        $this->actingAs($this->user($role));

        foreach ($this->administrativeRequests() as [$method, $name]) {
            $this->json($method, route($name), ['order_id' => 1, 'status' => 2])
                ->assertForbidden();
        }
        $this->get(route('dashboard'))->assertRedirect(route('moj-racun'));
    }

    public function test_guests_cannot_use_administrative_apis(): void
    {
        foreach ($this->administrativeRequests() as [$method, $name]) {
            $this->json($method, route($name))->assertUnauthorized();
        }
    }

    /** @dataProvider staffRoles */
    public function test_existing_staff_can_reach_administrative_api(string $role): void
    {
        $this->actingAs($this->user($role))
            ->postJson(route('api.order.status.change'), [])
            ->assertOk()->assertJsonStructure(['error']);
    }

    public function test_editor_cannot_promote_self_through_user_editor(): void
    {
        $editor = $this->user('editor');

        $this->actingAs($editor)->patchJson(route('users.update', $editor), [
            'username' => 'Test User', 'email' => $editor->email, 'role' => 'admin',
        ])->assertForbidden();

        $this->assertSame(['editor'], $editor->roles()->pluck('name')->all());
    }

    public function test_admin_cannot_assign_superadmin_or_edit_existing_superadmin(): void
    {
        $admin = $this->user('admin');
        $customer = $this->user('customer');
        $superadmin = $this->user('superadmin');

        $this->actingAs($admin)->patchJson(route('users.update', $customer), [
            'username' => 'Test User', 'email' => $customer->email, 'role' => 'superadmin',
        ])->assertUnprocessable()->assertJsonValidationErrors('role');

        $this->patchJson(route('users.update', $superadmin), [
            'username' => 'Test User', 'email' => $superadmin->email, 'role' => 'admin',
        ])->assertForbidden();

        $this->assertSame(['customer'], $customer->roles()->pluck('name')->all());
        $this->assertSame(['superadmin'], $superadmin->roles()->pluck('name')->all());
    }

    public function test_admin_can_assign_an_allowed_staff_role(): void
    {
        $admin = $this->user('admin');
        $customer = $this->user('customer');

        $this->actingAs($admin)->patch(route('users.update', $customer), array_merge($this->profile(), [
            'username' => 'Test User', 'email' => $customer->email, 'role' => 'editor', 'status' => 'on',
        ]))->assertRedirect(route('users.edit', ['user' => $customer->id]));

        $this->assertSame(['editor'], $customer->roles()->pluck('name')->all());
        $this->assertDatabaseHas('user_details', ['user_id' => $customer->id, 'role' => 'editor']);
    }

    public static function untrustedRoles(): array
    {
        return [['customer'], [null], ['custom-role']];
    }

    public static function staffRoles(): array
    {
        return [['editor'], ['admin'], ['superadmin']];
    }

    private function administrativeRequests(): array
    {
        return [
            ['POST', 'api.order.status.change'],
            ['POST', 'api.order.send.gls'],
            ['POST', 'products.change.status'],
            ['GET', 'products.bulk.action'],
            ['POST', 'api.payment.store'],
            ['POST', 'api.application.google-api.store.key'],
            ['POST', 'api.api.upload'],
            ['POST', 'blogs.upload.image'],
            ['POST', 'widget.destroy'],
        ];
    }

    private function user(?string $role): User
    {
        $number = User::count() + 1;
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user' . $number . '@example.test',
            'password' => Hash::make('original-password'),
        ]);
        UserDetail::create([
            'user_id' => $user->id, 'fname' => 'Original', 'role' => $role ?? '', 'status' => 1,
        ]);
        if ($role !== null) {
            $user->assign($role);
        }

        return $user;
    }

    private function profile(): array
    {
        return [
            'fname' => 'Updated', 'lname' => 'User', 'address' => 'New address 1',
            'city' => 'Zagreb', 'zip' => '10000', 'state' => 'HR', 'phone' => '0912345678',
            'company' => 'Test company', 'oib' => '12345678901',
        ];
    }
}
