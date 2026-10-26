<?php

namespace Tests\Feature;

use App\Helpers\ProductHelper;
use App\Models\Back\Catalog\Product\Product as AdminProduct;
use App\Models\Front\Catalog\Product;
use App\Models\Sitemap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeoSitemapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        session(['locale' => current_locale()]);

        foreach ([
            '2020_01_02_223851_create_authors_table.php' => \CreateAuthorsTable::class,
            '2020_01_02_223911_create_categories_table.php' => \CreateCategoriesTable::class,
            '2020_01_02_223921_create_products_table.php' => \CreateProductsTable::class,
            '2021_06_11_211245_create_pages_table.php' => \CreatePagesTable::class,
        ] as $file => $migration) {
            require_once database_path('migrations/' . $file);
            (new $migration())->up();
        }
    }

    public function test_brand_sitemap_uses_current_routes_and_omits_unpublished_or_untranslated_brands(): void
    {
        $this->brand('macron', 1, '/macron');
        $this->brand('zeus', 1, '/old-zeus');
        $this->brand('hidden', 0, '/hidden');
        $this->brand(null, 1, '/untranslated');
        $this->brand('', 1, '/empty');
        $this->brand('macron', 1, '/duplicate-macron');

        $response = $this->get(route('sitemap', ['sitemap' => 'brands']));

        $response->assertOk()->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $this->assertSame([
            route('catalog.route.brand'),
            route('catalog.route.brand', ['brand' => 'macron']),
            route('catalog.route.brand', ['brand' => 'zeus']),
        ], $this->locations($response->getContent()));
    }

    public function test_category_sitemap_excludes_inactive_or_untranslated_categories_and_children(): void
    {
        $parent = $this->category('sport');
        $this->category('lopte', 1, $parent);
        $this->category('hidden-child', 0, $parent);
        $this->category(null, 1, $parent);
        $inactive = $this->category('hidden-parent', 0);
        $this->category('child-of-hidden', 1, $inactive);
        $this->category(null);
        $this->category('');

        $response = $this->get(route('sitemap', ['sitemap' => 'categories.xml']));

        $response->assertOk();
        $this->assertSame([
            route('catalog.route', ['group' => 'kategorija-proizvoda', 'cat' => 'sport']),
            route('catalog.route', ['group' => 'kategorija-proizvoda', 'cat' => 'sport', 'subcat' => 'lopte']),
        ], $this->locations($response->getContent()));
    }

    public function test_product_and_image_sitemaps_use_current_category_paths_and_exclude_invalid_products(): void
    {
        $parent = $this->category('sport');
        $child = $this->category('lopte', 1, $parent);
        $product = $this->product('nova-lopta', [$parent, $child]);
        $this->product('bez-zalihe', [$parent], ['quantity' => 0]);
        $this->product('skriven', [$parent], ['status' => 0]);
        $this->product('bez-kategorije', []);
        $this->product('skrivena-kategorija', [$this->category('hidden', 0)]);
        $this->product('neprevedena-kategorija', [$this->category(null)]);
        $this->product(null, [$parent]);

        $expected = route('catalog.route', [
            'group' => 'kategorija-proizvoda', 'cat' => 'sport', 'subcat' => 'lopte', 'prod' => 'nova-lopta',
        ]);
        $this->assertSame($expected, url(Product::findOrFail($product)->url));

        $response = $this->get(route('sitemap', ['sitemap' => 'products']));
        $response->assertOk()->assertDontSee('outdated-product-url');
        $this->assertSame([$expected], $this->locations($response->getContent()));

        $imageResponse = $this->get(route('sitemap-image'));
        $imageResponse->assertOk();
        $this->assertSame([$expected], $this->locations($imageResponse->getContent()));
        $this->assertSame($expected, (new Sitemap('images'))->getResponse()[$product]['loc']);
    }

    public function test_page_sitemap_only_includes_published_translated_pages_and_blogs(): void
    {
        $this->page('o-nama', 'page');
        $this->page('novost', 'blog');
        $this->page('hidden', 'page', 0);
        $this->page(null, 'page');
        $this->page('', 'blog');
        $this->page('homepage', 'default');

        $urls = array_column((new Sitemap('pages'))->getResponse(), 'url');
        $this->assertCount(6, $urls);
        $this->assertContains(route('catalog.route.page', ['page' => 'o-nama']), $urls);
        $this->assertContains(route('catalog.route.blog', ['blog' => 'novost']), $urls);
        $this->assertNotContains(route('catalog.route.page', ['page' => 'hidden']), $urls);
    }

    public function test_unknown_sitemap_returns_404_and_image_alias_uses_image_xml(): void
    {
        $this->get(route('sitemap', ['sitemap' => 'missing']))->assertNotFound();
        $this->get(route('sitemap', ['sitemap' => 'images']))
            ->assertOk()->assertSee('xmlns:image=', false);
    }

    public function test_category_canonical_keeps_parent_path_and_pagination_but_omits_filters(): void
    {
        $this->app->instance('request', Request::create('/?page=2&brand=macron&sort=price_up'));
        $html = view('front.catalog.category.canonical', [
            'group' => 'incorrect-group-alias',
            'cat' => $this->translatedObject('sport'),
            'subcat' => $this->translatedObject('lopte'),
        ])->render();

        $expected = route('catalog.route', [
            'group' => 'kategorija-proizvoda', 'cat' => 'sport', 'subcat' => 'lopte', 'page' => 2,
        ]);
        $this->assertStringContainsString('href="' . $expected . '"', $html);
        $this->assertStringNotContainsString('brand=', $html);
        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
    }

    public function test_brand_canonical_uses_brand_route_with_category_path(): void
    {
        $html = view('front.catalog.category.canonical', [
            'brand' => $this->translatedObject('macron'),
            'cat' => $this->translatedObject('sport'),
            'subcat' => $this->translatedObject('lopte'),
        ])->render();

        $this->assertStringContainsString('href="' . route('catalog.route.brand', [
            'brand' => 'macron', 'cat' => 'sport', 'subcat' => 'lopte',
        ]) . '"', $html);
    }

    public function test_contact_page_has_one_canonical_and_direct_published_navigation_links(): void
    {
        $this->page('uvjeti-kupnje', 'page');
        $this->page('hidden', 'page', 0);
        $this->page(null, 'page');

        $response = $this->get(route('kontakt'));
        $response->assertOk()
            ->assertSee('href="' . route('kontakt') . '"', false)
            ->assertSee('href="' . route('catalog.route.page', ['page' => 'uvjeti-kupnje']) . '"', false)
            ->assertSee('href="#signin-modal"', false)
            ->assertDontSee('href="signin-tab"', false)
            ->assertDontSee('href="hr/', false)
            ->assertDontSee('href="' . route('catalog.route.page', ['page' => 'hidden']) . '"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'rel="canonical"'));
        $this->assertStringNotContainsString('name="robots" content="noindex', $response->getContent());
    }

    public function test_product_urls_keep_non_default_locale_prefix(): void
    {
        $category = $this->category('sport');
        $product = $this->product('lopta', [$category]);
        DB::table('category_translations')->insert([
            'category_id' => $category, 'lang' => 'en', 'title' => 'Sports', 'slug' => 'sports',
        ]);
        DB::table('product_translations')->insert([
            'product_id' => $product, 'lang' => 'en', 'name' => 'Ball', 'slug' => 'ball', 'url' => '/old-ball',
        ]);
        session(['locale' => 'en']);

        $this->assertSame('en/kategorija-proizvoda/sports/ball', ProductHelper::url(AdminProduct::findOrFail($product)));
    }

    private function locations(string $xml): array
    {
        $document = simplexml_load_string($xml);
        $this->assertNotFalse($document);
        $document->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return array_map('strval', $document->xpath('/s:urlset/s:url/s:loc'));
    }

    private function brand(?string $slug, int $status, string $oldUrl): int
    {
        $id = DB::table('brands')->insertGetId([
            'letter' => 'M', 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($slug !== null) {
            DB::table('brand_translations')->insert([
                'brand_id' => $id, 'lang' => current_locale(), 'title' => $slug, 'slug' => $slug, 'url' => $oldUrl,
            ]);
        }
        return $id;
    }

    private function category(?string $slug, int $status = 1, int $parent = 0): int
    {
        $id = DB::table('categories')->insertGetId([
            'parent_id' => $parent, 'group' => 'kategorija-proizvoda', 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($slug !== null) {
            DB::table('category_translations')->insert([
                'category_id' => $id, 'lang' => current_locale(), 'title' => $slug, 'slug' => $slug,
            ]);
        }
        return $id;
    }

    private function product(?string $slug, array $categories, array $attributes = []): int
    {
        $id = DB::table('products')->insertGetId(array_merge([
            'price' => 20, 'quantity' => 1, 'status' => 1, 'image' => 'product.jpg',
            'created_at' => now(), 'updated_at' => now(),
        ], $attributes));
        if ($slug !== null) {
            DB::table('product_translations')->insert([
                'product_id' => $id, 'lang' => current_locale(), 'name' => $slug, 'slug' => $slug,
                'url' => '/outdated-product-url',
            ]);
        }
        foreach ($categories as $category) {
            DB::table('product_category')->insert(['product_id' => $id, 'category_id' => $category]);
        }
        return $id;
    }

    private function page(?string $slug, string $group, int $status = 1): void
    {
        $id = DB::table('pages')->insertGetId([
            'category_id' => 0, 'group' => $group, 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($slug !== null) {
            DB::table('page_translations')->insert([
                'page_id' => $id, 'lang' => current_locale(), 'title' => $slug, 'slug' => $slug,
            ]);
        }
    }

    private function translatedObject(string $slug): object
    {
        return (object) ['group' => 'kategorija-proizvoda', 'translation' => (object) ['slug' => $slug]];
    }
}
