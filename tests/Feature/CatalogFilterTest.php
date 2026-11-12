<?php

namespace Tests\Feature;

use App\Models\Front\Catalog\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        session(['locale' => current_locale()]);
        DB::statement('create table products (id integer primary key, status integer, price real, quantity integer)');
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
