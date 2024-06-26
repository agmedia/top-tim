<?php

namespace App\Models;

use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Brand;
use App\Models\Front\Page;
use App\Models\Front\Blog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
    private function setSitemap(string $sitemap)
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
        $products = Product::query()->active()->hasStock()->with('images');

        foreach ($products->get() as $product) {
            $this->response[$product->id] = [
                'loc' => url($product->translation->url)
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
        $pages = Page::query()->where('group', 'page')->where('status', '=', 1)->get();
        $blogs = Blog::query()->where('status', '=', 1)->get();

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
        $categories = Category::query()->active()->topList()->with('subcategories')->get();

        foreach ($categories as $category) {
            $this->response[] = [
                'url' => route('catalog.route', ['group' => Str::slug($category->group), 'cat' => $category->slug]),
                'lastmod' => $category->updated_at->tz('UTC')->toAtomString()
            ];

            foreach ($category->subcategories()->get() as $subcategory) {
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
        $products = Product::query()->active()->hasStock()->get();

        foreach ($products as $product) {
            $this->response[] = [
                'url' => url($product->translation->url),
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
        $brands = Brand::query()->active()->get();

        $this->response[] = [
            'url' => route('catalog.route.brand'),
            'lastmod' => Carbon::now()->startOfMonth()->tz('UTC')->toAtomString()
        ];

        foreach ($brands as $brand) {
            $this->response[] = [
                'url' => url($brand->translation->url),
                'lastmod' => $brand->updated_at->tz('UTC')->toAtomString()
            ];

        }

        return $this->response;
    }


}
