<?php

namespace Tests\Feature;

use App\Models\Front\Catalog\Product;
use App\Services\CatalogImages;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        session(['locale' => current_locale()]);
        DB::statement('create table products (id integer primary key, status integer, price real, quantity integer, image text)');
        DB::statement('create table product_images (id integer primary key, product_id integer, option_id integer, image text, published integer, sort_order integer)');
        DB::statement('create table options (id integer primary key, status integer, type text, value text, value_opt text, sort_order integer)');
        DB::statement('create table options_translations (id integer primary key, option_id integer, lang text, title text, group_title text)');
        DB::statement('create table product_option (id integer primary key, product_id integer, option_id integer, parent_id integer)');
        DB::table('products')->insert([
            ['id' => 1, 'status' => 1, 'price' => 20, 'quantity' => 5],
            ['id' => 2, 'status' => 1, 'price' => 30, 'quantity' => 5],
            ['id' => 3, 'status' => 0, 'price' => 40, 'quantity' => 5],
        ]);
        $this->option(10, 'color', '#0000ff', null, 1);
        $this->option(11, 'color', '#000080', '#ffffff', 1);
        $this->option(12, 'color', '#ff0000', null, 2);
        $this->option(13, 'color', '#00ff00', null, 3);
        $this->option(14, 'color', '#ffff00', null, 1, 0);
        $this->option(20, 'size', null, null, 1);
        DB::table('product_option')->insert(['product_id' => 2, 'option_id' => 20, 'parent_id' => 12]);
        config(['settings.images_domain' => 'https://images.test/']);
        DB::table('products')->update(['image' => 'main.jpg']);
    }

    public function test_filter_groups_shades_and_combinations_without_per_option_queries(): void
    {
        for ($id = 100; $id < 180; $id++) {
            $this->option($id, 'color', '#0000ff', '#fff', 1);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->postJson('/api/v2/filter/getOptions', ['params' => ['ids' => '[1,2,3]']]);
        $response->assertOk();
        $options = collect($response->json());
        $this->assertSame(['color-white', 'color-blue', 'color-red'], $options->where('type', 'color')->pluck('id')->values()->all());
        $this->assertSame([20], $options->where('type', 'size')->pluck('id')->values()->all());
        $this->assertContains(11, $options->firstWhere('id', 'color-white')['option_ids']);
        $this->assertContains(11, $options->firstWhere('id', 'color-blue')['option_ids']);
        $this->assertLessThanOrEqual(3, count(DB::getQueryLog()), 'Option count must not increase the number of queries.');
    }

    public function test_basic_colors_match_all_shades_and_parent_options_and_combine_with_size(): void
    {
        $this->assertSame([1], $this->matching('color-blue'));
        $this->assertSame([1], $this->matching('color-white'));
        $this->assertSame([1, 2], $this->matching('color-blue+color-red'));
        $this->assertSame([2], $this->matching('20+color-red'));
        $this->assertSame([1], $this->matching('11'));
        $this->assertSame([], $this->matching('color-unknown'));
    }

    public function test_file_cache_survives_separate_stores_and_refreshes_after_two_minutes(): void
    {
        $directory = sys_get_temp_dir() . '/top-tim-filter-cache-' . bin2hex(random_bytes(10));
        config([
            'cache.default' => 'array',
            'cache.catalog_filters' => 'file',
            'cache.stores.file.path' => $directory,
        ]);
        Cache::forgetDriver('file');
        $request = ['params' => ['ids' => '[1,2]']];

        try {
            $first = $this->postJson('/api/v2/filter/getOptions', $request)->assertOk();
            $this->assertContains('color-red', array_column($first->json(), 'id'));
            DB::table('products')->where('id', 2)->update(['status' => 0]);

            // Resolve a new store to simulate another PHP request with no shared memory.
            Cache::forgetDriver('file');
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->postJson('/api/v2/filter/getOptions', $request)->assertOk()->assertExactJson($first->json());
            $this->assertCount(0, DB::getQueryLog(), 'Cached options must not query the database.');

            $this->travel(121)->seconds();
            $refreshed = $this->postJson('/api/v2/filter/getOptions', $request)->assertOk();
            $this->assertNotContains('color-red', array_column($refreshed->json(), 'id'));
            $this->assertNotEmpty(DB::getQueryLog(), 'Expired options must be rebuilt from current products.');
        } finally {
            $this->travelBack();
            DB::disableQueryLog();
            Cache::forgetDriver('file');
            (new Filesystem)->deleteDirectory($directory);
        }
    }

    public function test_cached_options_are_isolated_by_search_results_and_selected_options(): void
    {
        $requests = [
            [['ids' => '[1]'], ['color-white', 'color-blue']],
            [['ids' => '[2]'], ['color-red']],
            [['ids' => '[1,2]', 'option' => 'color-blue'], ['color-white', 'color-blue']],
            [['ids' => '[1,2]', 'option' => 'color-red'], ['color-red']],
            [['ids' => '[]'], []],
        ];

        foreach ([false, true] as $cached) {
            foreach ($requests as [$params, $expected]) {
                DB::enableQueryLog();
                DB::flushQueryLog();
                $response = $this->postJson('/api/v2/filter/getOptions', ['params' => $params])->assertOk();
                $this->assertSame($expected, collect($response->json())->where('type', 'color')->pluck('id')->values()->all());
                if ($cached) {
                    $this->assertCount(0, DB::getQueryLog());
                }
            }
        }
    }

    public function test_options_are_scoped_to_search_results_and_existing_ids_still_filter(): void
    {
        $response = $this->postJson('/api/v2/filter/getOptions', ['params' => ['ids' => '[2]', 'option' => '12']]);
        $response->assertOk();
        $this->assertSame(['color-red'], collect($response->json())->where('type', 'color')->pluck('id')->values()->all());
        $this->postJson('/api/v2/filter/getOptions', ['params' => ['ids' => '[]']])->assertOk()->assertExactJson([]);
    }

    public function test_basic_palette_preserves_light_and_dark_color_families(): void
    {
        $samples = ['#f5f5dc', '#fff', '#000', '#808080', '#000080', '#800080', '#ff0000', '#ff0', '#ff8a00', '#008000', '#30c5bf', '#ef64ad', '#88552e'];
        $options = array_map(fn ($value, $id) => (object) ['id' => $id, 'value' => $value, 'value_opt' => null], $samples, array_keys($samples));
        $groups = \App\Helpers\FilterColors::group($options);
        $this->assertCount(13, $groups);
        $this->assertSame([0], collect($groups)->firstWhere('id', 'color-beige')['option_ids']);
        $this->assertSame([5], collect($groups)->firstWhere('id', 'color-purple')['option_ids']);
    }

    public function test_catalog_images_follow_colors_shades_combinations_and_legacy_ids(): void
    {
        $this->image(1, 10, 'blue.jpg', 2);
        $this->image(1, 11, 'navy-white.jpg', 1);
        $this->image(2, 12, 'red.jpg');
        // The red variant exists only as the parent of a size option.
        DB::table('product_option')->where('product_id', 2)->where('option_id', 12)->delete();

        foreach ([
            ['color-blue', [1 => 'navy-white']],
            ['color-white', [1 => 'navy-white']],
            ['10', [1 => 'blue']],
            ['20+color-red', [2 => 'red']],
            ['color-blue+color-red', [1 => 'navy-white', 2 => 'red']],
        ] as [$selection, $expected]) {
            $products = (new Product())->filter(new Request(['option' => $selection]))->get();
            CatalogImages::apply($products, $selection);
            $this->assertSame(array_keys($expected), $products->modelKeys());
            foreach ($products as $product) {
                $this->assertSame('https://images.test/' . $expected[$product->id] . '.webp', $product->image);
            }
        }
        $this->assertSame(['main.jpg'], DB::table('products')->distinct()->pluck('image')->all(), 'Filtering must never save a different main image.');
    }

    public function test_missing_unpublished_inactive_or_unrelated_color_images_keep_the_main_image(): void
    {
        $this->image(1, 10, 'hidden-blue.jpg', 0, 0);
        $this->image(1, 11, '', 0);
        $this->image(1, 14, 'inactive-yellow.jpg', 0);
        $this->image(1, 12, 'red-without-product-option.jpg', 0);
        $this->image(2, 10, 'blue-on-another-product.jpg', 0);
        foreach (['color-blue', 'color-yellow', 'color-red', 'color-unknown', '14', '20', null] as $selection) {
            $products = Product::where('id', 1)->get();
            CatalogImages::apply($products, $selection);
            $this->assertSame('https://images.test/main.webp', $products->first()->image);
        }
    }

    public function test_multiple_colors_use_the_first_selected_color_with_a_published_image(): void
    {
        DB::table('product_option')->insert(['product_id' => 1, 'option_id' => 12, 'parent_id' => 0]);
        $this->image(1, 10, 'blue-first.jpg', 1);
        $this->image(1, 10, 'blue-second.jpg', 1);
        $this->image(1, 12, 'red.jpg', 0);
        foreach ([
            ['color-blue+color-red', 'blue-first'],
            ['color-red+color-blue', 'red'],
            ['color-white+color-blue', 'blue-first'],
            [[10 => true], 'blue-first'],
        ] as [$selection, $image]) {
            $products = Product::where('id', 1)->get();
            CatalogImages::apply($products, $selection);
            $this->assertSame('https://images.test/' . $image . '.webp', $products->first()->image);
        }
    }

    public function test_catalog_images_are_loaded_in_bulk_only_for_the_visible_page(): void
    {
        for ($id = 100; $id < 140; $id++) {
            DB::table('products')->insert(['id' => $id, 'status' => 1, 'price' => 10, 'quantity' => 5, 'image' => 'main.jpg']);
            DB::table('product_option')->insert(['product_id' => $id, 'option_id' => 10, 'parent_id' => 0]);
            $this->image($id, 10, 'blue-' . $id . '.jpg');
        }
        $products = Product::where('id', '>=', 100)->orderBy('id')->limit(20)->get();
        DB::enableQueryLog();
        DB::flushQueryLog();
        CatalogImages::apply($products, 'color-blue');
        $queries = DB::getQueryLog();
        $this->assertCount(2, $queries);
        $this->assertStringContainsString('"product_id" in (', $queries[1]['query']);
        $this->assertNotContains(120, $queries[1]['bindings'], 'Images on other pages must not be loaded.');
        foreach ($products as $product) {
            $this->assertSame('https://images.test/blue-' . $product->id . '.webp', $product->image);
        }
        DB::flushQueryLog();
        CatalogImages::apply($products, null);
        CatalogImages::apply(new \Illuminate\Database\Eloquent\Collection(), 'color-blue');
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_products_endpoint_changes_images_and_restores_main_images_when_cleared(): void
    {
        DB::statement('create table product_translations (id integer primary key, product_id integer, lang text)');
        DB::statement('create table reviews (id integer primary key, product_id integer, stars integer, status integer, sort_order integer)');
        // Isolate the catalog response from unrelated pricing and route accessors.
        Product::retrieved(fn (Product $product) => $product->setAppends([]));
        $this->image(1, 10, 'blue.jpg');
        $this->image(2, 12, 'red.jpg');
        try {
            foreach ([['color-blue', 1, 'blue'], ['color-red', 2, 'red'], ['', 1, 'main']] as [$selection, $id, $image]) {
                $this->postJson('/api/v2/filter/getProducts', ['params' => ['ids' => '[1,2]', 'option' => $selection]])
                    ->assertOk()->assertJsonPath('data.0.id', $id)
                    ->assertJsonPath('data.0.image', 'https://images.test/' . $image . '.webp');
            }
        } finally {
            app('events')->forget('eloquent.retrieved: ' . Product::class);
        }
    }

    private function image(int $productId, int $optionId, string $path, int $sort = 0, int $published = 1): void
    {
        DB::table('product_images')->insert(['product_id' => $productId, 'option_id' => $optionId, 'image' => $path, 'published' => $published, 'sort_order' => $sort]);
    }

    private function matching(string $options): array
    {
        return (new Product())->filter(new Request(['option' => $options]))->pluck('id')->all();
    }

    private function option(int $id, string $type, ?string $value, ?string $secondary, int $productId, int $status = 1): void
    {
        DB::table('options')->insert(['id' => $id, 'status' => $status, 'type' => $type, 'value' => $value, 'value_opt' => $secondary, 'sort_order' => 0]);
        DB::table('options_translations')->insert(['option_id' => $id, 'lang' => current_locale(), 'title' => (string) $id, 'group_title' => $type]);
        DB::table('product_option')->insert(['product_id' => $productId, 'option_id' => $id, 'parent_id' => 0]);
    }
}
