<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\User;
use App\Models\UserDetail;
use App\Queries\AdminUserQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        require_once database_path('migrations/2020_01_02_223800_create_bouncer_tables.php');
        require_once database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php');
        require_once database_path('migrations/2021_05_16_200208_create_sessions_table.php');
        (new \CreateUsersTable())->up();
        (new \CreateBouncerTables())->up();
        (new \CreatePersonalAccessTokensTable())->up();
        (new \CreateSessionsTable())->up();
        Schema::table('users', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('personal_access_tokens', function (Blueprint $table) { $table->timestamp('expires_at')->nullable(); });
        Schema::table('user_details', function (Blueprint $table) {
            $table->unsignedInteger('user_group_id')->nullable();
            $table->string('company')->nullable();
            $table->string('oib')->nullable();
        });
        Schema::create('pages', function (Blueprint $table) { $table->id(); });
        Schema::create('user_group', function (Blueprint $table) { $table->id(); });
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); $table->string('code'); $table->string('key');
            $table->text('value'); $table->boolean('json')->default(true);
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('total', 15, 2);
        });
    }

    public function test_sort_and_filters_use_real_roles_instead_of_untrusted_detail_labels(): void
    {
        $customer = $this->user('customer', 'Alice');
        $editor = $this->user('editor', 'Bea');
        $admin = $this->user('admin', 'Charlie');
        $superadmin = $this->user('superadmin', 'Dora');
        $roleless = $this->user(null, 'Zoe');
        $customer->details()->update(['role' => 'superadmin']);

        $this->assertSame([$superadmin->id, $admin->id, $editor->id, $customer->id, $roleless->id],
            AdminUserQuery::build(['sort' => 'role', 'direction' => 'asc'])->pluck('users.id')->all());
        $this->assertSame([$customer->id, $editor->id, $admin->id, $superadmin->id, $roleless->id],
            AdminUserQuery::build(['sort' => 'role', 'direction' => 'desc'])->pluck('users.id')->all());
        $this->assertSame([$superadmin->id, $admin->id],
            AdminUserQuery::build(['role' => 'administrators'])->pluck('users.id')->all());
        $this->assertSame([$roleless->id], AdminUserQuery::build(['role' => 'none'])->pluck('users.id')->all());
        $this->assertSame([], AdminUserQuery::build(['role' => 'administrators', 'search' => $customer->email])->pluck('users.id')->all());
    }

    public function test_admin_page_renders_sort_filter_status_and_delete_controls(): void
    {
        $admin = $this->user('admin', 'Acting Admin');
        $customer = $this->user('customer', 'Customer');
        $customer->details()->update(['status' => 0]);

        $this->actingAs($admin)->get(route('users'))
            ->assertOk()->assertSee(__('back/user.show_administrators'))
            ->assertSee('aria-sort="ascending"', false)
            ->assertSee(__('back/user.inactive'))
            ->assertSee(route('users.destroy', $customer), false)
            ->assertDontSee('action="' . route('users.destroy', $admin) . '"', false);
    }

    public function test_deleting_a_customer_revokes_access_and_preserves_orders_and_password(): void
    {
        $admin = $this->user('admin', 'Acting Admin');
        $customer = $this->user('customer', 'Customer');
        $password = $customer->password;
        $customer->createToken('test');
        DB::table('orders')->insert(['id' => 9, 'user_id' => $customer->id, 'total' => 42]);
        DB::table('sessions')->insert(['id' => 'customer-session', 'user_id' => $customer->id, 'payload' => '', 'last_activity' => time()]);
        config(['session.driver' => 'database']);

        $this->actingAs($admin)->delete(route('users.destroy', $customer), ['role' => 'administrators'])
            ->assertRedirect(route('users', ['search' => '', 'role' => 'administrators', 'sort' => 'role', 'direction' => 'asc']));

        $this->assertNull(User::find($customer->id));
        $this->assertSame($password, User::withTrashed()->findOrFail($customer->id)->password);
        $this->assertDatabaseHas('user_details', ['user_id' => $customer->id, 'status' => 0]);
        $this->assertDatabaseHas('orders', ['id' => 9, 'user_id' => $customer->id, 'total' => 42]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $customer->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'customer-session']);
    }

    public function test_user_cannot_delete_self_or_a_superadmin_as_an_admin(): void
    {
        $admin = $this->user('admin', 'Acting Admin');
        $superadmin = $this->user('superadmin', 'Super Admin');
        $this->actingAs($admin)->deleteJson(route('users.destroy', $admin))->assertForbidden();
        $this->deleteJson(route('users.destroy', $superadmin))->assertForbidden();
        $this->assertFalse($admin->fresh()->trashed());
        $this->assertFalse($superadmin->fresh()->trashed());
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_editor_and_customer_cannot_delete_users(string $role): void
    {
        $target = $this->user('customer', 'Target');
        $this->actingAs($this->user($role, $role), 'web')->deleteJson(route('users.destroy', $target))->assertForbidden();
        $this->assertNotNull($target->fresh());
    }

    public static function unprivilegedRoles(): array
    {
        return [['customer'], ['editor']];
    }

    public function test_guest_cannot_delete_users(): void
    {
        $target = $this->user('customer', 'Target');
        $this->deleteJson(route('users.destroy', $target))->assertUnauthorized();
        $this->assertNotNull($target->fresh());
    }

    public function test_invalid_sort_is_rejected_before_any_user_is_deleted(): void
    {
        $admin = $this->user('admin', 'Acting Admin');
        $target = $this->user('customer', 'Target');
        $this->actingAs($admin)->deleteJson(route('users.destroy', $target), ['direction' => 'desc; DROP TABLE users'])
            ->assertUnprocessable()->assertJsonValidationErrors('direction');
        $this->assertNotNull($target->fresh());
    }

    public function test_excel_export_matches_role_filter_and_writes_customer_text_as_literal_cells(): void
    {
        $admin = $this->user('admin', '=HYPERLINK("https://example.test")');
        $customer = $this->user('customer', 'Customer');
        $customer->details()->update(['role' => 'admin']);
        $export = new UsersExport(['role' => 'administrators']);
        $spreadsheet = $export->spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $this->assertSame(2, $sheet->getHighestRow());
        $this->assertSame($admin->name, $sheet->getCell('B2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
        $this->assertSame('admin', $sheet->getCell('U2')->getValue());
        $this->assertSame(count($export->headings()), count($export->map($admin->load(['details', 'roles']))));

        $file = tempnam(sys_get_temp_dir(), 'top-tim-users-export-');
        try {
            (new Xlsx($spreadsheet))->save($file);
            $loaded = IOFactory::load($file);
            $this->assertSame($admin->name, $loaded->getActiveSheet()->getCell('B2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $loaded->getActiveSheet()->getCell('B2')->getDataType());
            $loaded->disconnectWorksheets();
        } finally {
            unlink($file);
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function user(?string $role, string $name): User
    {
        $number = User::withTrashed()->count() + 1;
        $user = User::create(['name' => $name, 'email' => 'user' . $number . '@example.test', 'password' => Hash::make('unchanged-password')]);
        UserDetail::create(['user_id' => $user->id, 'fname' => $name, 'role' => $role ?? '', 'status' => 1]);
        if ($role !== null) { $user->assign($role); }
        return $user;
    }
}
