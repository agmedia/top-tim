<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\CatalogBulkDeletion;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CatalogBulkDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'session.driver' => 'array',
            'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        foreach (['2014_10_12_000000_create_users_table.php', '2020_01_02_223800_create_bouncer_tables.php',
            '2019_12_14_000001_create_personal_access_tokens_table.php',
            '2020_01_02_223921_create_products_table.php'] as $migration) {
            require_once database_path('migrations/'.$migration);
        }
        (new \CreateUsersTable)->up();
        (new \CreateBouncerTables)->up();
        (new \CreatePersonalAccessTokensTable)->up();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        foreach (['categories', 'attributes', 'options'] as $table) {
            Schema::create($table, fn (Blueprint $table) => $table->id());
            DB::table($table)->insert(['id' => 1]);
        }
        (new \CreateProductsTable)->up();
        Schema::table('product_option', function (Blueprint $table) {
            $table->unsignedBigInteger('image_id')->nullable();
            $table->foreign('image_id')->references('id')->on('product_images');
        });
        Schema::create('orders', function (Blueprint $table) { $table->id(); $table->decimal('total'); });
        Schema::create('order_products', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('order_id'); $table->unsignedBigInteger('product_id');
            $table->string('sku'); $table->string('image'); $table->decimal('price');
        });
        (require database_path('migrations/2026_11_12_190000_create_anchor_price_tables.php'))->up();
        (require database_path('migrations/2026_11_16_180000_create_product_price_observations.php'))->up();
        Storage::fake('products');
        foreach ([1, 2, 3, 4] as $id) {
            DB::table('product_actions')->insert(['id' => $id, 'type' => 'P', 'discount' => 10, 'group' => 'product']);
            DB::table('product_actions_translations')->insert(['product_action_id' => $id, 'lang' => 'hr', 'title' => 'Akcija '.$id]);
            DB::table('products')->insert(['id' => $id, 'sku' => 'SKU-'.$id, 'price' => 20,
                'action_id' => $id < 4 ? $id : 0, 'special' => 12, 'special_from' => '2026-11-01 00:00:00', 'special_to' => '2026-12-01 00:00:00']);
            DB::table('product_translations')->insert(['product_id' => $id, 'lang' => 'hr', 'name' => 'Artikl '.$id, 'slug' => 'art-'.$id, 'url' => 'art-'.$id]);
            DB::table('product_category')->insert(['product_id' => $id, 'category_id' => 1]);
            DB::table('product_attribute')->insert(['product_id' => $id, 'attribute_id' => 1]);
            $image = DB::table('product_images')->insertGetId(['product_id' => $id, 'image' => $id.'/image.jpg', 'sort_order' => 1]);
            DB::table('product_images_translations')->insert(['product_image_id' => $image, 'lang' => 'hr', 'alt' => 'Slika '.$id]);
            DB::table('product_option')->insert(['product_id' => $id, 'option_id' => 1, 'parent' => '0', 'parent_id' => 0, 'image_id' => $image]);
            Storage::disk('products')->put($id.'/image.jpg', 'historical-image-'.$id);
        }
        DB::table('orders')->insert(['id' => 1, 'total' => 24]);
        DB::table('order_products')->insert(['order_id' => 1, 'product_id' => 1, 'sku' => 'ORDER-SKU', 'image' => '1/image.jpg', 'price' => 12]);
        DB::table('anchor_prices')->insert(['product_id' => 1, 'price' => 20, 'reference_date' => '2026-11-01', 'note' => 'Historical']);
        DB::table('anchor_price_revisions')->insert(['product_id' => 1, 'after' => '{}', 'reason' => 'Historical', 'created_at' => now()]);
        DB::table('product_price_observations')->insert(['product_id' => 1, 'price' => 20, 'observed_at' => now(), 'last_seen_at' => now()]);
        DB::table('price_list_publications')->insert(['publication_date' => '2026-11-01', 'filename' => 'old.csv', 'path' => 'history/old.csv',
            'sha256' => str_repeat('a', 64), 'product_count' => 4, 'published_at' => now()]);
    }

    public function test_selected_products_and_catalog_relations_are_deleted_while_history_and_media_survive(): void
    {
        $history = $this->historicalSnapshot();
        $version = Helper::actionCacheVersion();
        Helper::resolveCache('product')->put('1', 'selected', 60);
        Helper::resolveCache('product')->put('3', 'unselected', 60);
        Helper::resolveCache('cat')->put('cpc1', 4, 60);
        Helper::resolveCache('cat')->put('scpc1', 4, 60);
        Helper::resolveCache('cat')->put('cpc2', 7, 60);
        $this->actingAs($this->staff())->postJson(route('products.bulk.destroy'), ['ids' => [2, 1]])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('deleted', 2);

        $this->assertSame([3, 4], DB::table('products')->orderBy('id')->pluck('id')->all());
        foreach (['product_translations', 'product_category', 'product_attribute', 'product_option', 'product_images'] as $table) {
            $this->assertSame([3, 4], DB::table($table)->orderBy('product_id')->pluck('product_id')->all(), $table);
        }
        $this->assertSame(2, DB::table('product_images_translations')->count());
        $this->assertSame($history, $this->historicalSnapshot());
        $this->assertSame(4, DB::table('product_actions')->count());
        foreach ([1, 2, 3, 4] as $id) {
            $this->assertSame('historical-image-'.$id, Storage::disk('products')->get($id.'/image.jpg'));
        }
        $this->assertNull(Helper::resolveCache('product')->get('1'));
        $this->assertSame('unselected', Helper::resolveCache('product')->get('3'));
        $this->assertNull(Helper::resolveCache('cat')->get('cpc1'));
        $this->assertNull(Helper::resolveCache('cat')->get('scpc1'));
        $this->assertSame(7, Helper::resolveCache('cat')->get('cpc2'));
        $this->assertSame($version + 1, Helper::actionCacheVersion());
    }

    public function test_selected_actions_clear_only_their_legacy_product_discounts_and_bump_cache_once(): void
    {
        $history = $this->historicalSnapshot();
        $untouched = DB::table('products')->whereIn('id', [3, 4])->orderBy('id')->get()->toJson();
        $version = Helper::actionCacheVersion();
        Helper::resolveCache('product')->put('1', 'stale discount', 60);
        Helper::resolveCache('product')->put('3', 'unselected', 60);
        $this->actingAs($this->staff())->postJson(route('actions.bulk.destroy'), ['ids' => [1, 2]])
            ->assertOk()->assertJsonPath('deleted', 2);

        $this->assertSame([3, 4], DB::table('product_actions')->orderBy('id')->pluck('id')->all());
        $this->assertSame([3, 4], DB::table('product_actions_translations')->orderBy('product_action_id')->pluck('product_action_id')->all());
        foreach ([1, 2] as $id) {
            $this->assertDatabaseHas('products', ['id' => $id, 'action_id' => 0, 'special' => null, 'special_from' => null, 'special_to' => null]);
        }
        $this->assertSame($untouched, DB::table('products')->whereIn('id', [3, 4])->orderBy('id')->get()->toJson());
        $this->assertSame(4, DB::table('product_option')->count());
        $this->assertSame($history, $this->historicalSnapshot());
        $this->assertSame($version + 1, Helper::actionCacheVersion());
        $this->assertNull(Helper::resolveCache('product')->get('1'));
        $this->assertSame('unselected', Helper::resolveCache('product')->get('3'));
    }

    #[DataProvider('invalidSelections')]
    public function test_invalid_selections_do_not_partially_delete_anything(array $input): void
    {
        $this->actingAs($this->staff());
        foreach (['products.bulk.destroy', 'actions.bulk.destroy'] as $route) {
            $response = $this->postJson(route($route), $input)->assertUnprocessable();
            $this->assertTrue(collect(array_keys($response->json('errors')))->contains(
                fn (string $key) => $key === 'ids' || str_starts_with($key, 'ids.')
            ));
        }
        $this->assertSame(4, DB::table('products')->count());
        $this->assertSame(4, DB::table('product_actions')->count());
        $this->assertSame(4, DB::table('product_images')->count());
    }

    public static function invalidSelections(): array
    {
        return [
            'missing' => [[]], 'empty' => [['ids' => []]], 'string' => [['ids' => '1,2']],
            'duplicate' => [['ids' => [1, '1']]], 'negative' => [['ids' => [1, -2]]],
            'zero' => [['ids' => [1, 0]]], 'fractional' => [['ids' => [1, 2.5]]],
            'unknown' => [['ids' => [1, 999]]], 'oversized' => [['ids' => range(1, 51)]],
            'associative' => [['ids' => ['first' => 1]]],
        ];
    }

    #[DataProvider('allowedRoles')]
    public function test_each_existing_staff_role_can_use_the_bulk_endpoints(string $role): void
    {
        $this->actingAs($this->staff($role));
        $this->postJson(route('products.bulk.destroy'), ['ids' => ['1']])->assertOk()->assertJsonPath('deleted', 1);
        $this->postJson(route('actions.bulk.destroy'), ['ids' => ['2']])->assertOk()->assertJsonPath('deleted', 1);
    }

    public static function allowedRoles(): array
    {
        return [['admin'], ['superadmin'], ['editor']];
    }

    #[DataProvider('deniedActors')]
    public function test_guests_customers_and_inactive_staff_cannot_delete(string $actor, int $status, string $route): void
    {
        if ($actor !== 'guest') {
            $user = $this->staff($actor === 'inactive' ? 'editor' : 'customer');
            if ($actor === 'inactive') {
                $user->details()->update(['status' => 0]);
            }
            $this->actingAs($user);
        }
        $this->postJson(route($route), ['ids' => [1]])->assertStatus($status);
        $this->assertSame(4, DB::table('products')->count());
        $this->assertSame(4, DB::table('product_actions')->count());
    }

    public static function deniedActors(): array
    {
        $cases = [];
        foreach (['products.bulk.destroy', 'actions.bulk.destroy'] as $route) {
            foreach ([['guest', 401], ['customer', 403], ['inactive', 403]] as [$actor, $status]) {
                $cases[] = [$actor, $status, $route];
            }
        }

        return $cases;
    }

    public function test_get_routes_and_legacy_bulk_get_never_delete(): void
    {
        $this->actingAs($this->staff());
        foreach (['products.bulk.destroy', 'actions.bulk.destroy', 'products.bulk.action'] as $route) {
            $this->getJson(route($route, ['ids' => [1], 'products' => '[1]', 'type' => 'delete']))->assertStatus(405);
        }
        $this->assertSame(4, DB::table('products')->count());
        $this->assertSame(4, DB::table('product_actions')->count());
    }

    public function test_csrf_is_required_and_a_valid_session_token_allows_post(): void
    {
        $this->actingAs($this->staff());
        // Enable real CSRF middleware behaviour instead of Laravel's unit-test bypass.
        $this->app['env'] = 'csrf-check';
        try {
            foreach (['products.bulk.destroy', 'actions.bulk.destroy', 'products.bulk.action'] as $route) {
                $this->postJson(route($route), ['ids' => [1]])->assertStatus(419);
            }
            $this->assertSame(4, DB::table('products')->count());
            $this->withSession(['_token' => 'bulk-csrf-token'])
                ->postJson(route('products.bulk.destroy'), ['ids' => [1]], ['X-CSRF-TOKEN' => 'bulk-csrf-token'])
                ->assertOk()->assertJsonPath('deleted', 1);
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_a_late_database_failure_rolls_back_all_products_and_relations_without_cache_change(): void
    {
        Schema::create('unexpected_reference', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products');
        });
        DB::table('unexpected_reference')->insert(['product_id' => 2]);
        $version = Helper::actionCacheVersion();
        try {
            app(CatalogBulkDeletion::class)->products([1, 2], $this->staff());
            $this->fail('Deletion should be rejected by the database foreign key.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('FOREIGN KEY', $exception->getMessage());
        }
        foreach (['products', 'product_images', 'product_option', 'product_translations', 'product_images_translations'] as $table) {
            $this->assertSame(4, DB::table($table)->count(), $table);
        }
        $this->assertSame($version, Helper::actionCacheVersion());
    }

    public function test_action_failure_rolls_back_discounts_and_translations_without_cache_change(): void
    {
        DB::unprepared("CREATE TRIGGER reject_action_delete BEFORE DELETE ON product_actions WHEN OLD.id = 2 BEGIN SELECT RAISE(ABORT, 'test refusal'); END");
        $before = DB::table('products')->orderBy('id')->get()->toJson();
        $version = Helper::actionCacheVersion();
        try {
            app(CatalogBulkDeletion::class)->actions([1, 2], $this->staff());
            $this->fail('Deletion should be rejected by the database trigger.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('test refusal', $exception->getMessage());
        }
        $this->assertSame($before, DB::table('products')->orderBy('id')->get()->toJson());
        $this->assertSame(4, DB::table('product_actions')->count());
        $this->assertSame(4, DB::table('product_actions_translations')->count());
        $this->assertSame($version, Helper::actionCacheVersion());
    }

    public function test_transaction_rechecks_actor_permissions_instead_of_trusting_a_stale_user(): void
    {
        $actor = $this->staff();
        DB::table('assigned_roles')->where('entity_id', $actor->id)->delete();
        try {
            app(CatalogBulkDeletion::class)->products([1], $actor);
            $this->fail('Revoked permissions must be checked again under lock.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseHas('products', ['id' => 1]);
    }

    public function test_existing_single_delete_uses_the_same_safe_catalog_cleanup(): void
    {
        $history = $this->historicalSnapshot();
        $this->actingAs($this->staff())->postJson(route('products.destroy.api'), ['id' => 1])
            ->assertOk()->assertJsonPath('success', 200);
        $this->assertDatabaseMissing('products', ['id' => 1]);
        $this->assertDatabaseMissing('product_option', ['product_id' => 1]);
        $this->assertSame($history, $this->historicalSnapshot());
        Storage::disk('products')->assertExists('1/image.jpg');
    }

    private function staff(string $role = 'admin'): User
    {
        $user = User::create(['name' => 'Bulk staff', 'email' => uniqid('bulk-', true).'@example.test',
            'email_verified_at' => now(), 'password' => Hash::make('test-password')]);
        UserDetail::create(['user_id' => $user->id, 'fname' => 'Bulk', 'role' => $role, 'status' => 1]);
        $user->assign($role);

        return $user;
    }

    private function historicalSnapshot(): array
    {
        $snapshot = [];
        foreach (['orders', 'order_products', 'anchor_prices', 'anchor_price_revisions', 'product_price_observations', 'price_list_publications'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        return $snapshot;
    }
}
