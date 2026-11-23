<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use App\Services\LowestPriceCorrections;
use App\Services\RecordedPrices;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LowestPriceCorrectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array',
            'session.driver' => 'array', 'app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->travelTo(now()->setDate(2026, 11, 23)->setTime(12, 0));
        foreach (['2014_10_12_000000_create_users_table.php', '2020_01_02_223800_create_bouncer_tables.php',
            '2019_12_14_000001_create_personal_access_tokens_table.php'] as $migration) {
            require_once database_path('migrations/'.$migration);
        }
        (new \CreateUsersTable)->up();
        (new \CreateBouncerTables)->up();
        (new \CreatePersonalAccessTokensTable)->up();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('brand_id')->default(0);
            $table->decimal('price', 15, 4); $table->decimal('special', 15, 4)->nullable();
            $table->boolean('status')->default(true);
        });
        DB::table('products')->insert([['id' => 1, 'price' => 100, 'special' => 90], ['id' => 2, 'price' => 50, 'special' => null]]);
        DB::statement('create table product_actions (id integer primary key, status integer, "group" text, user_group_id integer, "lock" integer, links text, type text, discount real, coupon text, date_start text, date_end text, logged integer)');
        (require database_path('migrations/2026_11_16_180000_create_product_price_observations.php'))->up();
        (require database_path('migrations/2026_11_23_180000_create_product_lowest_price_corrections.php'))->up();
        (require database_path('migrations/2026_11_12_190000_create_anchor_price_tables.php'))->up();
        DB::table('anchor_prices')->insert(['product_id' => 1, 'price' => 150, 'reference_date' => '2026-09-10', 'note' => 'Existing reference']);
        $this->observation(1, 80, now()->subDay());
        $this->observation(2, 40, now()->subDay());
    }

    public function test_manual_can_correct_up_or_down_without_rewriting_any_existing_price_or_observation(): void
    {
        $this->actingAs($actor = $this->staff());
        $before = $this->originals();
        $this->assertSame(80.0, $this->effective());
        $this->saveHttp(['price' => ' 95,25 ', 'reason' => '  Provjeren izvor  ', 'actor_id' => 999])
            ->assertRedirect(route('products.edit', 1).'#lowest-price');
        $this->assertSame(95.25, $this->effective());
        $this->saveHttp(['price' => '60'])->assertRedirect();
        $this->assertSame(60.0, $this->effective());
        $this->assertSame($before, $this->originals());
        $rows = DB::table('product_lowest_price_corrections')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('Provjeren izvor', $rows[0]->reason);
        $this->assertSame($actor->id, $rows[0]->actor_id);
        $this->assertSame(now()->addDays(30)->toDateTimeString(), $rows[0]->expires_at);
    }

    public function test_return_to_automatic_appends_an_audit_row_and_never_reactivates_older_corrections(): void
    {
        $this->actingAs($this->staff());
        $this->saveHttp(['price' => 60])->assertRedirect();
        $first = DB::table('product_lowest_price_corrections')->first();
        $this->saveHttp(['mode' => 'automatic', 'price' => 'ignored invalid value'])->assertRedirect();
        $this->assertSame(80.0, $this->effective());
        $this->assertEquals($first, DB::table('product_lowest_price_corrections')->where('id', $first->id)->first());
        $this->assertDatabaseHas('product_lowest_price_corrections', ['id' => 2, 'mode' => 'automatic', 'price' => null, 'expires_at' => null]);
        app(LowestPriceCorrections::class)->forget();
        $this->assertSame(80.0, $this->effective());
    }

    public function test_expiry_is_checked_at_the_exact_boundary_even_with_warm_shared_and_request_caches(): void
    {
        $this->actingAs($this->staff());
        $this->saveHttp(['price' => 60])->assertRedirect();
        // Keep the automatic record current without creating a new observation.
        DB::table('product_price_observations')->where('product_id', 1)->update(['last_seen_at' => now()->addDays(30)]);
        $this->travel(30 * 86400 - 1)->seconds();
        $this->assertSame(60.0, $this->effective());
        $this->travel(1)->seconds();
        $this->assertSame(80.0, $this->effective());
    }

    public function test_latest_expired_row_does_not_reveal_an_older_still_active_manual_row(): void
    {
        $actor = $this->staff();
        app(LowestPriceCorrections::class)->save(1, ['mode' => 'manual', 'price' => 30, 'reason' => 'Older correction'], $actor);
        DB::table('product_lowest_price_corrections')->insert(['product_id' => 1, 'mode' => 'manual', 'price' => 50,
            'reason' => 'Expired latest', 'actor_id' => $actor->id, 'created_at' => now()->subDay(), 'expires_at' => now()->subSecond()]);
        app(LowestPriceCorrections::class)->forget();
        $this->assertSame(80.0, $this->effective());
    }

    public function test_new_lower_public_prices_never_replace_the_exact_manual_value_while_it_is_active(): void
    {
        $this->actingAs($this->staff());
        $this->saveHttp(['price' => 95])->assertRedirect();
        $this->assertSame(95.0, $this->effective());
        // A last_seen refresh of an old bad row must not revive that row.
        DB::table('product_price_observations')->where('product_id', 1)->update(['last_seen_at' => now()]);
        app(RecordedPrices::class)->forget();
        $this->assertSame(95.0, $this->effective());
        DB::table('products')->where('id', 1)->update(['price' => 35]);
        app(RecordedPrices::class)->capture();
        $this->assertSame(95.0, $this->effective());
        $this->assertSame(35.0, app(RecordedPrices::class)->automaticForProduct(1)['price']);
        $this->assertDatabaseHas('product_price_observations', ['product_id' => 1, 'price' => 35, 'observed_at' => now()->toDateTimeString()]);
        $this->assertSame(1, DB::table('product_lowest_price_corrections')->count());
    }

    public function test_a_new_correction_replaces_a_warm_prior_correction_in_request_and_shared_cache(): void
    {
        $this->actingAs($this->staff());
        $this->saveHttp(['price' => 60])->assertRedirect();
        $this->observation(1, 50, now());
        app(RecordedPrices::class)->forget();
        $this->assertSame(60.0, $this->effective());
        $this->saveHttp(['price' => 70])->assertRedirect();
        $this->assertSame(70.0, $this->effective());
        // Simulate another request reusing shared cache instead of request attributes.
        request()->attributes->remove('lowest_price_corrections.latest.v1');
        $this->assertSame(70.0, $this->effective());
    }

    public function test_capture_preserves_manual_value_and_never_records_the_override_as_a_real_price(): void
    {
        $this->actingAs($this->staff());
        $this->saveHttp(['price' => 65])->assertRedirect();
        app(RecordedPrices::class)->capture();
        $this->assertSame(65.0, $this->effective());
        $this->assertDatabaseMissing('product_price_observations', ['price' => 65]);
        $this->assertSame(80.0, app(RecordedPrices::class)->automaticForProduct(1)['price']);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_is_rejected_without_any_audit_write(array $input): void
    {
        $this->actingAs($this->staff());
        $this->patchJson(route('products.lowest-price.update', 1), array_replace($this->payload(), $input))->assertUnprocessable();
        $this->assertDatabaseCount('product_lowest_price_corrections', 0);
        $this->assertSame(80.0, $this->effective());
    }

    public static function invalidInputs(): array
    {
        return array_map(fn (array $input) => [$input], [['price' => 0], ['price' => -1], ['price' => '12.345'], ['price' => '999999999999'],
            ['price' => '1e2'], ['price' => 'NaN'], ['price' => []], ['reason' => '  a  '],
            ['reason' => str_repeat('a', 1001)], ['reason' => null], ['mode' => 'unknown']]);
    }

    public function test_html_validation_returns_to_the_dedicated_form_with_its_own_error_bag(): void
    {
        $this->actingAs($this->staff())->patch(route('products.lowest-price.update', 1), ['mode' => 'manual', 'price' => 'bad', 'reason' => '  '])
            ->assertRedirect(route('products.edit', 1).'#lowest-price')
            ->assertSessionHasErrors(['price', 'reason'], null, 'lowestPrice');
        $this->assertDatabaseCount('product_lowest_price_corrections', 0);
    }

    #[DataProvider('deniedActors')]
    public function test_guests_customers_editors_and_inactive_staff_cannot_correct_prices(string $role, int $status): void
    {
        if ($role !== 'guest') {
            $user = $this->staff($role === 'inactive' ? 'admin' : $role);
            if ($role === 'inactive') { $user->details()->update(['status' => 0]); }
            $this->actingAs($user);
        }
        $this->patchJson(route('products.lowest-price.update', 1), $this->payload())->assertStatus($status);
        $this->assertDatabaseCount('product_lowest_price_corrections', 0);
    }

    public static function deniedActors(): array
    {
        return [['guest', 401], ['customer', 403], ['editor', 403], ['inactive', 403]];
    }

    public function test_superadmin_can_save_and_permissions_are_rechecked_for_a_stale_actor(): void
    {
        $this->actingAs($actor = $this->staff('superadmin'));
        $this->saveHttp(['price' => '0,01'])->assertRedirect();
        $this->assertSame(0.01, $this->effective());
        DB::table('assigned_roles')->where('entity_id', $actor->id)->delete();
        try {
            app(LowestPriceCorrections::class)->save(1, $this->payload(), $actor);
            $this->fail('A revoked role was trusted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('product_lowest_price_corrections', 1);
    }

    public function test_missing_product_is_not_created_and_get_does_not_mutate(): void
    {
        $this->actingAs($this->staff());
        $this->patchJson(route('products.lowest-price.update', 999), $this->payload())->assertNotFound();
        $this->getJson(route('products.lowest-price.update', 1))->assertStatus(405);
        $this->assertDatabaseCount('product_lowest_price_corrections', 0);
    }

    public function test_real_csrf_middleware_blocks_requests_without_the_session_token(): void
    {
        $this->actingAs($this->staff());
        $this->app['env'] = 'csrf-check';
        try {
            $this->patchJson(route('products.lowest-price.update', 1), $this->payload())->assertStatus(419);
            $this->assertDatabaseCount('product_lowest_price_corrections', 0);
            $this->withSession(['_token' => 'lowest-price-token'])->patch(route('products.lowest-price.update', 1),
                $this->payload(), ['X-CSRF-TOKEN' => 'lowest-price-token'])->assertRedirect();
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_admin_data_has_last_ten_audit_entries_but_public_data_has_no_private_fields(): void
    {
        $actor = $this->staff();
        for ($i = 1; $i <= 12; $i++) {
            app(LowestPriceCorrections::class)->save(1, $this->payload(['price' => $i, 'reason' => 'Private reason '.$i]), $actor);
        }
        $admin = app(LowestPriceCorrections::class)->forAdmin(1);
        $this->assertCount(10, $admin['history']);
        $this->assertSame(12, $admin['correction']->id);
        $this->assertSame(80.0, $admin['automatic']['price']);
        $this->assertSame(12.0, $admin['effective']['price']);
        $this->assertTrue($admin['manual_active']);
        $public = app(RecordedPrices::class)->forProduct(1);
        $this->assertSame(['price', 'formatted', 'since', 'note'], array_keys($public));
        $this->assertStringNotContainsString('Private reason', json_encode($public));
        $this->assertStringNotContainsString('actor_id', json_encode($public));
    }

    public function test_lookup_queries_are_batched_and_cache_is_invalidated_after_save(): void
    {
        $actor = $this->staff();
        app(LowestPriceCorrections::class)->save(1, $this->payload(['price' => 60]), $actor);
        app(LowestPriceCorrections::class)->forget();
        DB::enableQueryLog();
        $this->assertSame(60.0, $this->effective());
        $this->assertSame(40.0, app(RecordedPrices::class)->forProduct(2)['price']);
        $count = count(DB::getQueryLog());
        for ($i = 0; $i < 15; $i++) {
            $this->assertSame(60.0, $this->effective());
            $this->assertSame(40.0, app(RecordedPrices::class)->forProduct(2)['price']);
        }
        $this->assertSame($count, count(DB::getQueryLog()));
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(6, $count);
        app(LowestPriceCorrections::class)->save(1, $this->payload(['price' => 70]), $actor);
        $this->assertSame(70.0, $this->effective());
    }

    public function test_missing_migration_is_safe_for_reads_and_refuses_writes(): void
    {
        Schema::drop('product_lowest_price_corrections');
        $this->assertSame(80.0, $this->effective());
        $this->assertNull(app(LowestPriceCorrections::class)->forAdmin(1)['correction']);
        $this->actingAs($this->staff())->patchJson(route('products.lowest-price.update', 1), $this->payload())->assertStatus(503);
        $this->assertDatabaseHas('products', ['id' => 1, 'price' => 100]);
    }

    public function test_migration_refuses_to_drop_a_nonempty_audit_table(): void
    {
        $migration = require database_path('migrations/2026_11_23_180000_create_product_lowest_price_corrections.php');
        app(LowestPriceCorrections::class)->save(1, $this->payload(), $this->staff());
        try {
            $migration->down();
            $this->fail('Audit records must survive a migration rollback attempt.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Audit', $exception->getMessage());
        }
        $this->assertDatabaseCount('product_lowest_price_corrections', 1);
    }

    private function effective(): ?float
    {
        return app(RecordedPrices::class)->forProduct(1)['price'] ?? null;
    }

    private function saveHttp(array $input)
    {
        return $this->patch(route('products.lowest-price.update', 1), $this->payload($input));
    }

    private function payload(array $input = []): array
    {
        return array_replace(['mode' => 'manual', 'price' => 60, 'reason' => 'Provjerena evidencija'], $input);
    }

    private function observation(int $productId, float $price, $time): void
    {
        DB::table('product_price_observations')->insert(['product_id' => $productId, 'price' => $price,
            'observed_at' => $time, 'last_seen_at' => $time, 'source' => 'test_observation']);
    }

    private function staff(string $role = 'admin'): User
    {
        $user = User::create(['name' => 'Price staff', 'email' => uniqid('price-', true).'@example.test',
            'email_verified_at' => now(), 'password' => Hash::make('test-password')]);
        UserDetail::create(['user_id' => $user->id, 'fname' => 'Price', 'role' => $role, 'status' => 1]);
        $user->assign($role);

        return $user;
    }

    private function originals(): array
    {
        $values = [];
        foreach (['products', 'anchor_prices', 'product_price_observations'] as $table) {
            $values[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        return $values;
    }
}
