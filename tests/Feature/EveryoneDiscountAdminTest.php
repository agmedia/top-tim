<?php

namespace Tests\Feature;

use App\Models\CustomerDiscount;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EveryoneDiscountAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array',
            'app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        foreach (['2014_10_12_000000_create_users_table.php', '2020_01_02_223800_create_bouncer_tables.php',
            '2019_12_14_000001_create_personal_access_tokens_table.php'] as $migration) {
            require_once database_path('migrations/'.$migration);
        }
        (new \CreateUsersTable)->up();
        (new \CreateBouncerTables)->up();
        (new \CreatePersonalAccessTokensTable)->up();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('user_details', fn (Blueprint $table) => $table->unsignedInteger('user_group_id')->nullable());
        (require database_path('migrations/2026_11_12_180000_create_customer_discounts_table.php'))->up();
        (require database_path('migrations/2026_11_23_190000_add_everyone_to_customer_discounts.php'))->up();
        (require database_path('migrations/2026_11_23_200000_add_brand_sets_to_customer_discounts.php'))->up();
        DB::statement('create table user_group (id integer primary key, status integer)');
        DB::statement('create table user_group_translations (id integer primary key, user_group_id integer, lang text, title text)');
        DB::statement('create table brands (id integer primary key, status integer)');
        DB::statement('create table brand_translations (id integer primary key, brand_id integer, lang text, title text)');
        DB::statement('create table settings (id integer primary key, code text, "key" text, value text, json integer)');
        DB::statement('create table pages (id integer primary key)');
        DB::table('user_group')->insert(['id' => 1, 'status' => 1]);
        DB::table('user_group_translations')->insert(['user_group_id' => 1, 'lang' => current_locale(), 'title' => 'VIP']);
        DB::table('brands')->insert([['id' => 1, 'status' => 1], ['id' => 2, 'status' => 1]]);
        DB::table('brand_translations')->insert(['brand_id' => 1, 'lang' => current_locale(), 'title' => 'Macron']);
    }

    public function test_everyone_target_is_explicit_and_clears_ignored_private_ids_on_every_level(): void
    {
        $this->actingAs($this->user('admin'))->post(route('discounts.store'), $this->payload([
            'user_id' => 'ignored-invalid', 'user_group_id' => 999, 'applies_to_everyone' => false,
        ]))->assertRedirect(route('discounts'));
        $rules = CustomerDiscount::orderBy('id')->get();
        $this->assertCount(2, $rules);
        foreach ($rules as $rule) {
            $this->assertTrue($rule->applies_to_everyone);
            $this->assertNull($rule->user_id);
            $this->assertNull($rule->user_group_id);
        }
        $this->assertSame([5, 10], $rules->pluck('min_quantity')->all());
        $this->assertSame([true, false], $rules->pluck('active')->all());
    }

    public function test_switching_all_levels_to_everyone_and_back_preserves_their_ids_and_values(): void
    {
        $customer = $this->user('customer');
        $first = $this->rule(['user_group_id' => 1, 'min_quantity' => 5]);
        $second = $this->rule(['user_group_id' => 1, 'min_quantity' => 10, 'percentage' => 20, 'active' => false]);
        $this->actingAs($this->user('admin'));
        $data = $this->payload(['tiers' => [
            ['id' => $first->id, 'min_quantity' => 5, 'percentage' => 10, 'active' => true],
            ['id' => $second->id, 'min_quantity' => 10, 'percentage' => 20, 'active' => false],
        ]]);
        $this->patch(route('discounts.update', $first), $data)->assertRedirect();
        $this->assertSame([$first->id, $second->id], CustomerDiscount::orderBy('id')->pluck('id')->all());
        $this->assertDatabaseHas('customer_discounts', ['id' => $second->id, 'applies_to_everyone' => true,
            'user_group_id' => null, 'user_id' => null, 'percentage' => 20, 'active' => false]);
        $this->patch(route('discounts.update', $first), array_replace($data, ['target' => 'user',
            'user_id' => $customer->id, 'user_group_id' => 1, 'applies_to_everyone' => true]))->assertRedirect();
        foreach ([$first->id, $second->id] as $id) {
            $this->assertDatabaseHas('customer_discounts', ['id' => $id, 'user_id' => $customer->id,
                'user_group_id' => null, 'applies_to_everyone' => false]);
        }
    }

    public function test_index_and_edit_keep_public_rules_and_legacy_orphans_in_separate_sets(): void
    {
        $public = $this->rule(['applies_to_everyone' => true, 'min_quantity' => 5]);
        $this->rule(['applies_to_everyone' => true, 'min_quantity' => 10]);
        $orphan = $this->rule(['min_quantity' => 3]);
        $this->rule(['user_id' => $this->user('customer')->id, 'min_quantity' => 1]);
        $this->actingAs($this->user('admin'))->get(route('discounts'))->assertOk()
            ->assertViewHas('discounts', function ($sets) use ($public, $orphan) {
                return $sets->total() === 3 && $sets->firstWhere('id', $public->id)->levels->count() === 2
                    && $sets->firstWhere('id', $orphan->id)->levels->count() === 1;
            });
        $this->get(route('discounts.edit', $public))->assertOk()
            ->assertViewHas('levels', fn ($levels) => array_column($levels, 'min_quantity') === [5, 10]);
        $this->get(route('discounts.edit', $orphan))->assertOk()
            ->assertViewHas('levels', fn ($levels) => array_column($levels, 'min_quantity') === [3]);
    }

    public function test_duplicate_checks_distinguish_everyone_from_orphan_and_private_rules(): void
    {
        $orphan = $this->rule();
        $private = $this->rule(['user_group_id' => 1]);
        $this->actingAs($this->user('admin'));
        $this->post(route('discounts.store'), $this->payload())->assertRedirect(route('discounts'));
        $this->postJson(route('discounts.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('brand_id');
        $this->patchJson(route('discounts.update', $private), $this->payload(['tiers' => [
            ['id' => $private->id, 'min_quantity' => 1, 'percentage' => 15, 'active' => true],
        ]]))->assertUnprocessable()->assertJsonValidationErrors('brand_id');
        $this->assertDatabaseHas('customer_discounts', ['id' => $private->id, 'applies_to_everyone' => false, 'user_group_id' => 1]);
        $this->assertDatabaseHas('customer_discounts', ['id' => $orphan->id, 'applies_to_everyone' => false]);
        $this->assertDatabaseCount('customer_discounts', 4);
    }

    public function test_deleting_all_everyone_levels_preserves_private_orphan_and_other_brand_sets(): void
    {
        $public = $this->rule(['applies_to_everyone' => true]);
        $this->rule(['applies_to_everyone' => true, 'min_quantity' => 20]);
        $orphan = $this->rule();
        $private = $this->rule(['user_group_id' => 1]);
        $otherBrand = $this->rule(['applies_to_everyone' => true, 'brand_id' => 2]);
        $this->actingAs($this->user('admin'))->delete(route('discounts.destroy', $public), ['all_levels' => true])->assertRedirect();
        $this->assertSame([$orphan->id, $private->id, $otherBrand->id], CustomerDiscount::orderBy('id')->pluck('id')->all());
    }

    public function test_forged_public_flag_cannot_make_a_user_or_group_rule_public(): void
    {
        $customer = $this->user('customer');
        $this->actingAs($this->user('admin'));
        foreach ([['target' => 'user', 'user_id' => $customer->id], ['target' => 'group', 'user_group_id' => 1]] as $target) {
            $this->post(route('discounts.store'), $this->payload($target + ['applies_to_everyone' => true]))->assertRedirect();
        }
        $this->assertSame(0, CustomerDiscount::where('applies_to_everyone', true)->count());
        $this->assertSame(2, CustomerDiscount::where('user_id', $customer->id)->count());
        $this->assertSame(2, CustomerDiscount::where('user_group_id', 1)->count());
        $this->get(route('discounts.create'))->assertOk()->assertViewHas('discount', fn ($rule) => !$rule->applies_to_everyone);
    }

    #[DataProvider('invalidPayloads')]
    public function test_existing_target_and_tier_validation_stays_atomic(array $changes): void
    {
        $this->actingAs($this->user('admin'))->postJson(route('discounts.store'), $this->payload($changes))->assertUnprocessable();
        $this->assertDatabaseCount('customer_discounts', 0);
    }

    public static function invalidPayloads(): array
    {
        return array_map(fn ($input) => [$input], [
            ['target' => 'invalid'], ['target' => 'user'], ['target' => 'group'], ['brand_id' => 999],
            ['tiers' => [['min_quantity' => 0, 'percentage' => 10, 'active' => true]]],
            ['tiers' => [['min_quantity' => 1, 'percentage' => 101, 'active' => true]]],
            ['tiers' => [['min_quantity' => 1, 'percentage' => 10, 'active' => true], ['min_quantity' => 1, 'percentage' => 20, 'active' => true]]],
        ]);
    }

    public function test_foreign_tier_ids_from_an_orphan_set_cannot_be_used_to_modify_public_levels(): void
    {
        $public = $this->rule(['applies_to_everyone' => true]);
        $orphan = $this->rule();
        $this->actingAs($this->user('admin'))->patchJson(route('discounts.update', $public), $this->payload(['tiers' => [
            ['id' => $orphan->id, 'min_quantity' => 1, 'percentage' => 80, 'active' => true],
        ]]))->assertUnprocessable()->assertJsonValidationErrors('tiers.0.id');
        $this->assertSame(10.0, $orphan->fresh()->percentage);
        $this->assertDatabaseCount('customer_discounts', 2);
    }

    public function test_migration_preserves_private_and_orphan_rows_and_defaults_them_to_false(): void
    {
        $migration = require database_path('migrations/2026_11_23_190000_add_everyone_to_customer_discounts.php');
        $migration->down();
        $row = ['user_id' => null, 'user_group_id' => null, 'brand_id' => 1, 'min_quantity' => 1, 'percentage' => 10, 'active' => true];
        DB::table('customer_discounts')->insert([$row, array_replace($row, ['user_id' => $this->user('customer')->id])]);
        $before = DB::table('customer_discounts')->orderBy('id')->get();
        $migration->up();
        $after = DB::table('customer_discounts')->orderBy('id')->get();
        foreach ($after as $index => $row) {
            $this->assertSame(0, $row->applies_to_everyone);
            unset($row->applies_to_everyone);
            $this->assertEquals($before[$index], $row);
        }
    }

    public function test_rollback_refuses_to_remove_the_public_marker_if_any_public_rule_exists(): void
    {
        $rule = $this->rule(['applies_to_everyone' => true, 'active' => false]);
        try {
            (require database_path('migrations/2026_11_23_190000_add_everyone_to_customer_discounts.php'))->down();
            $this->fail('The public target marker must not be dropped while public rules exist.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Svima', $exception->getMessage());
        }
        $this->assertTrue($rule->fresh()->applies_to_everyone);
    }

    #[DataProvider('deniedActors')]
    public function test_existing_admin_only_access_remains_enforced(string $role, int $status): void
    {
        if ($role !== 'guest') {
            $user = $this->user($role === 'inactive' ? 'admin' : $role);
            if ($role === 'inactive') { $user->details()->update(['status' => false]); }
            $this->actingAs($user);
        }
        $this->postJson(route('discounts.store'), $this->payload())->assertStatus($status);
        $this->assertDatabaseCount('customer_discounts', 0);
    }

    public static function deniedActors(): array
    {
        return [['guest', 401], ['customer', 403], ['editor', 403], ['inactive', 403]];
    }

    public function test_superadmin_can_create_everyone_rules_and_csrf_is_still_required(): void
    {
        $this->actingAs($this->user('superadmin'));
        $this->app['env'] = 'csrf-check';
        try {
            $this->postJson(route('discounts.store'), $this->payload())->assertStatus(419);
            $this->assertDatabaseCount('customer_discounts', 0);
            $this->withSession(['_token' => 'everyone-token'])->post(route('discounts.store'),
                $this->payload(), ['X-CSRF-TOKEN' => 'everyone-token'])->assertRedirect();
        } finally {
            $this->app['env'] = 'testing';
        }
        $this->assertSame(2, CustomerDiscount::where('applies_to_everyone', true)->count());
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['target' => 'everyone', 'brand_id' => 1, 'tiers' => [
            ['min_quantity' => 5, 'percentage' => 5, 'active' => true],
            ['min_quantity' => 10, 'percentage' => 10, 'active' => false],
        ]], $changes);
    }

    private function rule(array $data = []): CustomerDiscount
    {
        return CustomerDiscount::create(array_replace(['brand_id' => 1, 'min_quantity' => 1, 'percentage' => 10, 'active' => true], $data));
    }

    private function user(string $role): User
    {
        $user = User::create(['name' => 'Everyone staff', 'email' => uniqid('everyone-', true).'@example.test', 'password' => 'unused']);
        UserDetail::create(['user_id' => $user->id, 'fname' => 'Everyone', 'role' => $role, 'status' => true]);
        $user->assign($role);

        return $user;
    }
}
