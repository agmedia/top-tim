<?php

namespace App\Models;

use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Brand;
use App\Models\Front\Page;
use App\Models\Front\Blog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Sitemap
 * @package App\Models
 */
class Sitemap
{

    /**
     * @var string|null
     */
    private $sitemap;

    /**
     * @var array
     */
    private $response = [];


    /**
     * Sitemap constructor.
     *
     * @param string|null $sitemap
     */
    public function __construct(string $sitemap = null)
    {
        $this->sitemap = $this->setSitemap($sitemap);
    }


    /**
     * @return string|null
     */
    public function getSitemap()
    {
        return $this->sitemap;
    }


    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }


    /**
     * @param string $sitemap
     *
     * @return array
     */
    private function setSitemap(?string $sitemap)
    {
        if ( ! $sitemap) {
            return $sitemap;
        }

        if ($sitemap == 'pages' || $sitemap == 'pages.xml') {
            return $this->getPages();
        }

        if ($sitemap == 'categories' || $sitemap == 'categories.xml') {
            return $this->getCategories();
        }

        if ($sitemap == 'products' || $sitemap == 'products.xml') {
            return $this->getProducts();
        }

        if ($sitemap == 'brands' || $sitemap == 'brands.xml') {
            return $this->getBrands();
        }


        if ($sitemap == 'images' || $sitemap == 'img') {
            return $this->getImages();
        }
    }


    /**
     * @return array
     */
    private function getImages(): array
    {
        $products = $this->products()->with('images');

        foreach ($products->get() as $product) {
            $url = $this->productUrl($product);

            if (!$url) {
                continue;
            }

            $this->response[$product->id] = [
                'loc' => $url
            ];

            $this->response[$product->id]['images'][] = [
                'loc' => $product->image
            ];

            foreach ($product->images as $image) {
                $this->response[$product->id]['images'][] = [
                    'loc' => config('settings.images_domain') . $image->image
                ];
            }
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getPages()
    {
        $pages = $this->translated(Page::query()->where('group', 'page')->where('status', 1))->get();
        $blogs = $this->translated(Blog::query()->active())->get();

        $this->response[] = [
            'url' => route('index'),
            'lastmod' => Carbon::now()->startOfMonth()->tz('UTC')->toAtomString()
        ];

        $this->response[] = [
            'url' => route('kontakt'),
            'lastmod' => Carbon::now()->startOfYear()->tz('UTC')->toAtomString()
        ];

        $this->response[] = [
            'url' => route('faq'),
            'lastmod' => Carbon::now()->startOfYear()->tz('UTC')->toAtomString()
        ];

        $this->response[] = [
            'url' => route('contract-termination'),
            'lastmod' => Carbon::now()->startOfYear()->tz('UTC')->toAtomString()
        ];

        foreach ($pages as $page) {
            $this->response[] = [
                'url' => route('catalog.route.page', ['page' => $page->translation->slug]),
                'lastmod' => $page->updated_at->tz('UTC')->toAtomString()
            ];
        }

        foreach ($blogs as $blog) {
            $this->response[] = [
                'url' => route('catalog.route.blog', ['blog' => $blog->translation->slug]),
                'lastmod' => $blog->updated_at->tz('UTC')->toAtomString()
            ];
        }

        //dd($coll);

        return $this->response;
    }


    /**
     * @return array
     */

    private function getCategories()
    {
        $categories = $this->translated(Category::query()->active()->topList())
            ->where('group', '!=', '')
            ->with(['subcategories' => function ($query) {
                $query->whereHas('translation', function ($query) {
                    $query->whereNotNull('slug')->where('slug', '!=', '');
                });
            }])->get();

        foreach ($categories as $category) {
            $this->response[] = [
                'url' => route('catalog.route', ['group' => Str::slug($category->group), 'cat' => $category->slug]),
                'lastmod' => $category->updated_at->tz('UTC')->toAtomString()
            ];

            foreach ($category->subcategories as $subcategory) {
                $this->response[] = [
                    'url' => route('catalog.route', ['group' => Str::slug($category->group), 'cat' => $category->slug, 'subcat' => $subcategory->slug]),
                    'lastmod' => $subcategory->updated_at->tz('UTC')->toAtomString()
                ];
            }
        }

        return $this->response;
    }


    /**
     * @return array
     */
    private function getProducts()
    {
        $products = $this->products()->get();

        foreach ($products as $product) {
            $url = $this->productUrl($product);

            if (!$url) {
                continue;
            }

            $this->response[] = [
                'url' => $url,
                'lastmod' => $product->updated_at->tz('UTC')->toAtomString()
            ];
        }

        return $this->response;
    }




    /**
     * @return array
     */
    private function getBrands()
    {
        $brands = $this->translated(Brand::query()->active())
            ->orderByDesc('updated_at')->orderBy('id')->get()
            ->unique(function ($brand) {
                return $brand->translation->slug;
            });

        $this->response[] = [
            'url' => route('catalog.route.brand'),
            'lastmod' => Carbon::now()->startOfMonth()->tz('UTC')->toAtomString()
        ];

        foreach ($brands as $brand) {
            $this->response[] = [
                'url' => route('catalog.route.brand', ['brand' => $brand->translation->slug]),
                'lastmod' => $brand->updated_at->tz('UTC')->toAtomString()
            ];

        }

        return $this->response;
    }

    private function translated(Builder $query): Builder
    {
        return $query->whereHas('translation', function ($query) {
            $query->whereNotNull('slug')->where('slug', '!=', '');
        })->with('translation');
    }

    private function products(): Builder
    {
        return $this->translated(Product::query()->active()->hasStock())
            ->with('categories.translation');
    }

    private function productUrl(Product $product): ?string
    {
        // Match the category selection used by ProductHelper::url, rather than
        // advertising an old URL saved before a category or product was renamed.
        $category = $product->categories->firstWhere('parent_id', 0);

        if (!$category || !$category->status || !$category->translation ||
            !$category->translation->slug || !Str::slug($category->group)) {
            return null;
        }

        $subcategory = $product->categories->first(function ($category) {
            return $category->parent_id != 0;
        });

        if ($subcategory && $subcategory->parent_id != $category->id) {
            $subcategory = null;
        }

        if ($subcategory && (!$subcategory->status || !$subcategory->translation || !$subcategory->translation->slug)) {
            return null;
        }

        return route('catalog.route', [
            'group' => Str::slug($category->group),
            'cat' => $category->translation->slug,
            'subcat' => $subcategory ? $subcategory->translation->slug : $product->translation->slug,
            'prod' => $subcategory ? $product->translation->slug : null,
        ]);
    }

}
