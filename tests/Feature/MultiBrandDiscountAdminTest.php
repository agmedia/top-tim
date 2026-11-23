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

class MultiBrandDiscountAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array',
            'app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        require_once database_path('migrations/2020_01_02_223800_create_bouncer_tables.php');
        (new \CreateUsersTable)->up();
        (new \CreateBouncerTables)->up();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('user_details', fn (Blueprint $table) => $table->unsignedInteger('user_group_id')->nullable());
        foreach (['2026_11_12_180000_create_customer_discounts_table.php', '2026_11_23_190000_add_everyone_to_customer_discounts.php',
            '2026_11_23_200000_add_brand_sets_to_customer_discounts.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::statement('create table user_group (id integer primary key, status integer)');
        DB::statement('create table user_group_translations (id integer primary key, user_group_id integer, lang text, title text)');
        DB::statement('create table brands (id integer primary key, status integer)');
        DB::statement('create table brand_translations (id integer primary key, brand_id integer, lang text, title text)');
        DB::statement('create table settings (id integer primary key, code text, "key" text, value text, json integer)');
        DB::statement('create table pages (id integer primary key)');
        DB::table('user_group')->insert(['id' => 1, 'status' => 1]);
        DB::table('user_group_translations')->insert(['user_group_id' => 1, 'lang' => current_locale(), 'title' => 'VIP']);
        foreach ([1, 2, 3] as $id) {
            DB::table('brands')->insert(['id' => $id, 'status' => 1]);
            DB::table('brand_translations')->insert(['brand_id' => $id, 'lang' => current_locale(), 'title' => 'Brend '.$id]);
        }
    }

    public function test_multi_brand_store_derives_sorted_ids_primary_brand_and_key_for_every_tier(): void
    {
        $this->actingAs($this->user())->post(route('discounts.store'), $this->payload([
            'brand_ids' => ['2', 1], 'brand_id' => 3, 'brand_key' => 'forged',
        ]))->assertRedirect(route('discounts'));
        $rules = CustomerDiscount::orderBy('id')->get();
        $this->assertCount(2, $rules);
        foreach ($rules as $rule) {
            $this->assertSame([1, 2], $rule->brand_ids);
            $this->assertSame(1, $rule->brand_id);
            $this->assertSame(hash('sha256', '[1,2]'), $rule->brand_key);
            $this->assertTrue($rule->applies_to_everyone);
            $this->assertTrue($rule->appliesToBrand(2));
            $this->assertFalse($rule->appliesToBrand(3));
        }
    }

    public function test_editing_the_brand_set_updates_all_levels_and_preserves_their_ids(): void
    {
        $first = $this->rule(['brand_ids' => [1, 2], 'min_quantity' => 5]);
        $second = $this->rule(['brand_ids' => [1, 2], 'min_quantity' => 10, 'active' => false]);
        $other = $this->rule(['brand_ids' => [1, 2, 3]]);
        $this->actingAs($this->user())->patch(route('discounts.update', $first), $this->payload([
            'brand_ids' => [3, 1], 'tiers' => [
                ['id' => $first->id, 'min_quantity' => 5, 'percentage' => 15, 'active' => true],
                ['id' => $second->id, 'min_quantity' => 10, 'percentage' => 20, 'active' => false],
            ],
        ]))->assertRedirect();
        $this->assertSame([$first->id, $second->id, $other->id], CustomerDiscount::orderBy('id')->pluck('id')->all());
        $this->assertSame([1, 3], $first->fresh()->brand_ids);
        $this->assertSame([1, 3], $second->fresh()->brand_ids);
        $this->assertFalse($second->fresh()->active);
        $this->assertSame([1, 2, 3], $other->fresh()->brand_ids);
    }

    public function test_index_and_edit_do_not_merge_overlapping_brand_sets_with_the_same_primary_brand(): void
    {
        $first = $this->rule(['brand_ids' => [1, 2], 'min_quantity' => 5]);
        $this->rule(['brand_ids' => [1, 2], 'min_quantity' => 10]);
        $other = $this->rule(['brand_ids' => [1, 3], 'min_quantity' => 7]);
        $this->actingAs($this->user())->get(route('discounts'))->assertOk()
            ->assertViewHas('brandLabels', fn ($labels) => $labels === [1 => 'Brend 1', 2 => 'Brend 2', 3 => 'Brend 3'])
            ->assertViewHas('discounts', fn ($sets) => $sets->total() === 2
                && $sets->firstWhere('id', $first->id)->levels->count() === 2
                && $sets->firstWhere('id', $other->id)->levels->count() === 1);
        $this->get(route('discounts.edit', $first))->assertOk()
            ->assertViewHas('selectedBrandIds', [1, 2])
            ->assertViewHas('levels', fn ($levels) => array_column($levels, 'id') === [$first->id, $first->id + 1]);
    }

    public function test_duplicate_detection_uses_canonical_brand_set_and_all_target_fields(): void
    {
        $this->actingAs($this->user());
        $this->post(route('discounts.store'), $this->payload(['brand_ids' => [2, 1]]))->assertRedirect();
        $this->postJson(route('discounts.store'), $this->payload(['brand_ids' => [1, 2]]))->assertUnprocessable();
        $this->post(route('discounts.store'), $this->payload(['brand_ids' => [1, 3]]))->assertRedirect();
        $this->post(route('discounts.store'), $this->payload(['target' => 'group', 'user_group_id' => 1]))->assertRedirect();
        $this->assertDatabaseCount('customer_discounts', 6);
    }

    public function test_switch_to_an_existing_brand_set_is_atomic_and_foreign_tier_ids_are_rejected(): void
    {
        $first = $this->rule(['brand_ids' => [1, 2]]);
        $foreign = $this->rule(['brand_ids' => [1, 3]]);
        $this->actingAs($this->user());
        $this->patchJson(route('discounts.update', $first), $this->payload(['brand_ids' => [1, 3], 'tiers' => [
            ['id' => $first->id, 'min_quantity' => 5, 'percentage' => 80, 'active' => true],
        ]]))->assertUnprocessable();
        $this->patchJson(route('discounts.update', $first), $this->payload(['tiers' => [
            ['id' => $foreign->id, 'min_quantity' => 5, 'percentage' => 80, 'active' => true],
        ]]))->assertUnprocessable();
        $this->assertSame([1, 2], $first->fresh()->brand_ids);
        $this->assertSame(10.0, $first->fresh()->percentage);
        $this->assertSame(10.0, $foreign->fresh()->percentage);
    }

    public function test_delete_all_levels_is_scoped_to_the_exact_brand_set_and_target(): void
    {
        $first = $this->rule(['brand_ids' => [1, 2]]);
        $this->rule(['brand_ids' => [1, 2], 'min_quantity' => 20]);
        $overlap = $this->rule(['brand_ids' => [1, 3]]);
        $private = $this->rule(['brand_ids' => [1, 2], 'applies_to_everyone' => false, 'user_group_id' => 1]);
        $this->actingAs($this->user())->delete(route('discounts.destroy', $first), ['all_levels' => true])->assertRedirect();
        $this->assertSame([$overlap->id, $private->id], CustomerDiscount::orderBy('id')->pluck('id')->all());
    }

    public function test_legacy_single_brand_request_and_model_creation_get_canonical_metadata(): void
    {
        $payload = $this->payload(['brand_id' => 2]);
        unset($payload['brand_ids']);
        $this->actingAs($this->user())->post(route('discounts.store'), $payload)->assertRedirect();
        $rule = CustomerDiscount::firstOrFail();
        $this->assertSame([2], $rule->brand_ids);
        $this->assertSame(CustomerDiscount::brandSetKey([2]), $rule->brand_key);
        $legacy = $this->rule(['brand_id' => 3, 'brand_key' => 'untrusted']);
        $this->assertSame([3], $legacy->brand_ids);
        $this->assertSame(CustomerDiscount::brandSetKey([3]), $legacy->brand_key);
    }

    #[DataProvider('invalidBrandSelections')]
    public function test_invalid_brand_selection_does_not_save_partial_tiers($selection): void
    {
        $this->actingAs($this->user())->postJson(route('discounts.store'), $this->payload([
            'brand_ids' => $selection, 'brand_id' => 1,
        ]))->assertUnprocessable();
        $this->assertDatabaseCount('customer_discounts', 0);
    }

    public static function invalidBrandSelections(): array
    {
        return [[[]], [null], ['1,2'], [[1, '1']], [[1, 999]], [[0, 1]], [[-1]], [[1.5]], [range(1, 101)], [['first' => 1]]];
    }

    public function test_migration_backfills_metadata_without_changing_any_existing_rule_or_target_value(): void
    {
        $migration = require database_path('migrations/2026_11_23_200000_add_brand_sets_to_customer_discounts.php');
        $migration->down();
        foreach ([1, 2] as $id) {
            DB::table('customer_discounts')->insert(['brand_id' => $id, 'user_id' => null, 'user_group_id' => null,
                'applies_to_everyone' => $id === 2, 'min_quantity' => 7, 'percentage' => 12.5, 'active' => false,
                'created_at' => '2026-10-01 12:00:00', 'updated_at' => '2026-10-03 12:00:00']);
        }
        $before = DB::table('customer_discounts')->orderBy('id')->get();
        $migration->up();
        foreach (DB::table('customer_discounts')->orderBy('id')->get() as $i => $row) {
            $this->assertSame('['.$row->brand_id.']', $row->brand_ids);
            $this->assertSame(CustomerDiscount::brandSetKey([$row->brand_id]), $row->brand_key);
            unset($row->brand_ids, $row->brand_key);
            $this->assertEquals($before[$i], $row);
        }
    }

    public function test_rollback_refuses_to_lose_multi_brand_selection(): void
    {
        $rule = $this->rule(['brand_ids' => [1, 2]]);
        try {
            (require database_path('migrations/2026_11_23_200000_add_brand_sets_to_customer_discounts.php'))->down();
            $this->fail('A multi-brand set must not be converted silently to its primary brand.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('više brendova', $exception->getMessage());
        }
        $this->assertSame([1, 2], $rule->fresh()->brand_ids);
    }

    public function test_partial_migration_can_resume_without_overwriting_completed_multi_brand_sets(): void
    {
        $multi = $this->rule(['brand_ids' => [1, 2]]);
        $before = DB::table('customer_discounts')->where('id', $multi->id)->first();
        $id = DB::table('customer_discounts')->insertGetId(['brand_id' => 3, 'min_quantity' => 1, 'percentage' => 10, 'active' => true]);
        $migration = require database_path('migrations/2026_11_23_200000_add_brand_sets_to_customer_discounts.php');
        $migration->up();
        $migration->up();
        $this->assertEquals($before, DB::table('customer_discounts')->where('id', $multi->id)->first());
        $this->assertSame([3], CustomerDiscount::findOrFail($id)->brand_ids);
        $this->assertTrue(Schema::hasIndex('customer_discounts', 'customer_discounts_brand_key_index'));
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['target' => 'everyone', 'brand_ids' => [1, 2], 'tiers' => [
            ['min_quantity' => 5, 'percentage' => 5, 'active' => true],
            ['min_quantity' => 10, 'percentage' => 10, 'active' => false],
        ]], $changes);
    }

    private function rule(array $data): CustomerDiscount
    {
        return CustomerDiscount::create(array_replace(['brand_id' => 1, 'brand_ids' => null, 'min_quantity' => 1,
            'percentage' => 10, 'active' => true, 'applies_to_everyone' => true], $data));
    }

    private function user(): User
    {
        $user = User::create(['name' => 'Brand admin', 'email' => uniqid('brand-', true).'@example.test', 'password' => 'unused']);
        UserDetail::create(['user_id' => $user->id, 'fname' => 'Brand', 'role' => 'admin', 'status' => true]);
        $user->assign('admin');

        return $user;
    }
}
