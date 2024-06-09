<?php

namespace App\Http\Controllers\Api\v2;

use App\Helpers\Helper;
use App\Models\Front\Catalog\Product;
use App\Models\Back\Catalog\Product\ProductImage;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Brand;
use App\Models\Front\Catalog\Options\Options;
use App\Models\Front\Catalog\ProductOption;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Publisher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class FilterController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(Request $request)
    {
        if ( ! $request->input('params')) {
            return response()->json(['status' => 300, 'message' => 'Error!']);
        }

        $response = [];
        $params   = $request->input('params');

        // Ako je normal kategorija
        if ($params['group']) {
            $cache_tag = $params['group'] . session('locale');

            $response = Helper::resolveCache('categories')->remember($cache_tag, config('cache.life'), function () use ($params) {
                $categories = Category::active()->topList($params['group'])->sortByName()->with('subcategories')/*->withCount('products')*/ ->get();

                return $this->resolveCategoryArray($categories, 'categories');
            });
        }

        return response()->json($response);
    }


    /**
     * @param             $categories
     * @param string      $type
     * @param null        $target
     * @param string|null $parent_slug
     *
     * @return array
     */
    private function resolveCategoryArray($categories, string $type, $target = null, string $parent_slug = null): array
    {
        $locale   = session('locale');
        $response = [];

        foreach ($categories as $category) {
            $url  = $this->resolveCategoryUrl($category, $type, $target, $parent_slug);
            $subs = null;

            if (isset($category['subcategories']) && ! empty($category['subcategories'])) {
                foreach ($category['subcategories'] as $subcategory) {
                    $sub_url = $this->resolveCategoryUrl($subcategory, $type, $target, $category->translation($locale)->slug);

                    $subs[] = [
                        'id'    => $subcategory['id'],
                        'title' => $subcategory->translation($locale)->title,
                        'count' => 0,//Category::find($subcategory['id'])->products()->count(),
                        'url'   => $sub_url
                    ];
                }
            }

            $response[] = [
                'id'    => $category['id'],
                'title' => $category->translation($locale)->title,
                'icon'  => $category['icon'],
                'count' => 0,//$category['products_count'],
                'url'   => $url,
                'subs'  => $subs
            ];


        }

        return $response;
    }


    /**
     * @param             $category
     * @param string      $type
     * @param             $target
     * @param string|null $parent_slug
     *
     * @return string
     */
    private function resolveCategoryUrl($category, string $type, $target, string $parent_slug = null): string
    {
        $locale = session('locale');

        if ($type == 'brand') {
            $route = route('catalog.route.brand', [
                'brand'  => $target,
                'cat'    => $parent_slug ?: $category->translation($locale)->slug,
                'subcat' => $parent_slug ? $category->translation($locale)->slug : null
            ]);

            return LaravelLocalization::getLocalizedUrl($locale, $route);

        } else {
            $route = route('catalog.route', [
                'group'  => Str::slug($category['group']),
                'cat'    => $parent_slug ?: $category->translation($locale)->slug,
                'subcat' => $parent_slug ? $category->translation($locale)->slug : null
            ]);

            return LaravelLocalization::getLocalizedUrl($locale, $route);
        }
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(Request $request)
    {
        if ( ! $request->has('params')) {
            return response()->json(['status' => 300, 'message' => 'Error!']);
        }

        $params       = $request->input('params');
        $cache_string = '';

        $request_data = [];

        if (isset($params['ids']) && $params['ids'] != '') {
            $request_data['ids'] = $params['ids'];
        }

        if (isset($params['group']) && $params['group']) {
            $request_data['group'] = $params['group'];
            $cache_string          .= '&group=' . $params['group'];
        }

        if (isset($params['cat']) && $params['cat']) {
            $request_data['cat'] = $params['cat'];
            $cache_string        .= '&cat=' . $params['cat'];
        }

        if (isset($params['subcat']) && $params['subcat']) {
            $request_data['subcat'] = $params['subcat'];
        }

        if (isset($params['autor']) && $params['autor']) {
            $request_data['autor'] = $this->authors;
        }

        if (isset($params['nakladnik']) && $params['nakladnik']) {
            $request_data['nakladnik'] = $this->publishers;
        }

        if (isset($params['brand']) && $params['brand']) {
            $request_data['brand'] = $params['brand'];
        }

        if (isset($params['option']) && $params['option']) {
            $request_data['option'] = $params['option'];
        }

        if (isset($params['start']) && $params['start']) {
            $request_data['start'] = $params['start'];
        }

        if (isset($params['end']) && $params['end']) {
            $request_data['end'] = $params['end'];
        }

        if (isset($params['sort']) && $params['sort']) {
            $request_data['sort'] = $params['sort'];
        }

        $request_data['page'] = $request->input('page');

        $request = new Request($request_data);

        if (isset($params['ids']) && $params['ids'] != '') {
            $products = (new Product())->filter($request)
                                       ->paginate(config('settings.pagination.front'));
        } else {
            /*$products = Helper::resolveCache('products')->remember($cache_string, config('cache.life'), function () use ($request) {
                 return (new Product())->filter($request)
                                       ->with('author')
                                       ->paginate(config('settings.pagination.front'), ['*'], 'page', $request->input('page'));
            });*/

            $products = (new Product())->filter($request)
                                       ->paginate(config('settings.pagination.front'));

            /*foreach ($products as $product) {
                Log::info($product->toArray());
            }*/
        }

        return response()->json($products);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function brands(Request $request)
    {
        if ($request->has('params')) {

            return response()->json(
                (new Brand())->filter($request->input('params'))
                             ->get()
                             ->toArray()
            );
        }

        return response()->json(
            Helper::resolveCache('brands')->remember('featured', config('cache.life'), function () {
                return Brand::query()->active()
                            ->featured()
                            ->basicData()
                            ->withCount('products')
                            ->get()
                            ->toArray();
            })
        );
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function options(Request $request)
    {
        if ($request->has('params')) {
            $response = [];
            $options  = (new Options())->filter($request->input('params'))
                                       ->get();

            foreach ($options as $option) {
                if ($option->value_opt) {
                    $style = 'background: linear-gradient(45deg, ' . $option->value . ' 50%, ' . $option->value_opt . ' 50%);';

                } else {
                    $style = 'background-color:' . $option->value;
                }

                $response[] = [
                    'id'             => $option->id,
                    'title'          => $option->translation->title,
                    'value'          => $option->value,
                    'value_opt'      => $option->value_opt,
                    'group'          => $option->group,
                    'type'           => $option->type,
                    'products_count' => $option->products_count,
                    'style'          => $style,
                    'sort_order'     => $option->sort_order
                ];
            }

            return response()->json($response);
        }


    }

}
