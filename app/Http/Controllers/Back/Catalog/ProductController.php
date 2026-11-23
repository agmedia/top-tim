<?php

namespace App\Http\Controllers\Back\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Services\CatalogBulkDeletion;
use App\Services\LowestPriceCorrections;
use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductAction;
use App\Models\Back\Settings\SizeGuide;
use App\Models\Back\Catalog\Publisher;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Product $product)
    {
        $query = $product->filter($request);

        $products = $query->paginate(50)->appends(request()->query());

        if ($request->has('status')) {
            if ($request->input('status') == 'with_action' || $request->input('status') == 'without_action') {
                $products = collect();
                $temps = Product::all();

                if ($request->input('status') == 'with_action') {
                    foreach ($temps as $product) {
                        if ($product->special()) {
                            $products->push($product);
                        }
                    }
                }

                if ($request->input('status') == 'without_action') {
                    foreach ($temps as $product) {
                        if ( ! $product->special()) {
                            $products->push($product);
                        }
                    }
                }

                $products = $this->paginateColl($products);
            }
        }

        $categories = (new Category())->getList(false);
        /*$authors    = Author::all()->pluck('title', 'id');
        $publishers = Publisher::all()->pluck('title', 'id');*/
        $counts = [];//Product::setCounts($query);

        return view('back.catalog.product.index', compact('products', 'categories'/*, 'authors', 'publishers'*/, 'counts'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $product = new Product();

        $data = $product->getRelationsData();
        $active_actions = ProductAction::active()->get();

        return view('back.catalog.product.edit', compact('data', 'active_actions'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //dd($request->toArray());
        $product = new Product();

        $stored = $product->validateRequest($request)->create();

        if ($stored) {
            $product->storeImages($stored);

            return redirect()->route('products.edit', ['product' => $stored])->with(['success' => 'Artikl je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Ops..! Greška prilikom snimanja.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Product $product
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $data = $product->getRelationsData();

        $color_options = Options::getColorList();

        $lowestPriceData = app(LowestPriceCorrections::class)->forAdmin($product->id);
        $user = request()->user();
        $canCorrectLowestPrice = $user && $user->isActive()
            && $user->roles()->whereIn('name', ['admin', 'superadmin'])->exists();

        //dd($product->attributes);

        return view('back.catalog.product.edit', compact('product', 'data', 'color_options', 'lowestPriceData', 'canCorrectLowestPrice'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Product                  $product
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //dd($request->toArray());
        $updated = $product->validateRequest($request)->edit();

        if ($updated) {
            $product->storeImages($updated);

            //$product->addHistoryData('change');

            return redirect()->route('products.edit', ['product' => $updated])->with(['success' => 'Artikl je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Ops..! Greška prilikom snimanja.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Product $product)
    {
        $destroyed = app(CatalogBulkDeletion::class)->products([$product->id], $request->user());

        if ($destroyed) {
            return redirect()->route('products')->with(['success' => 'Artikl je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Ops..! Greška prilikom snimanja.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyApi(Request $request)
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);
        app(CatalogBulkDeletion::class)->products([$validated['id']], $request->user());

        return response()->json(['success' => 200]);
    }

    public function bulkDestroy(BulkDeleteRequest $request, CatalogBulkDeletion $deletion)
    {
        $deleted = $deletion->products($request->validated('ids'), $request->user());

        return response()->json(['success' => true, 'deleted' => $deleted, 'message' => 'Odabrani artikli su izbrisani.']);
    }


    /**
     * @param       $items
     * @param int   $perPage
     * @param null  $page
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function paginateColl($items, $perPage = 50, $page = null, $options = []): LengthAwarePaginator
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}
