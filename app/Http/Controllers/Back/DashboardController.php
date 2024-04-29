<?php

namespace App\Http\Controllers\Back;

use App\Helpers\Chart;
use App\Helpers\Helper;
use App\Helpers\Import;
use App\Helpers\ProductHelper;
use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use App\Mail\OrderReceived;
use App\Mail\OrderSent;
use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Mjerilo;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Back\Catalog\Product\ProductImage;
use App\Models\Back\Catalog\Product\ProductImageTranslation;
use App\Models\Back\Catalog\Product\ProductTranslation;
use App\Models\Back\Catalog\Publisher;
use App\Models\Back\Marketing\Review;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderProduct;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Bouncer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DashboardController extends Controller
{

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $data['today']      = Order::whereDate('created_at', Carbon::today())->count();
        $data['proccess']   = Order::whereIn('order_status_id', [1, 2, 3])->count();
        $data['finished']   = Order::whereIn('order_status_id', [4, 5, 6, 7])->count();
        $data['this_month'] = Order::whereMonth('created_at', '=', Carbon::now()->month)->count();
        $data['this_month_total'] = Order::whereMonth('created_at', '=', Carbon::now()->month)->whereIn('order_status_id', [4, 1, 2, 3])->sum('total');

        $data['this_month_total'] =  number_format($data['this_month_total'], 2,'.','');


        $data['users']   = UserDetail::whereIn('role', ['customer'])->count();

        $data['comments']   = Review::whereIn('status', ['0'])->count();
        $data['zeroproducts']   = Product::whereIn('quantity', ['0'])->count();

        $orders   = Order::last()->with('products')->get();

        $ordersfinished   = Order::finished()->with('products')->get();
        $products = $ordersfinished->map(function ($item) {
            return $item->products()->get();
        })->take(9)->flatten();


        $bestsellers = DB::table('order_products')
            ->leftJoin('orders','orders.id','=','order_products.order_id')
            ->select('order_products.name','order_products.product_id',
                DB::raw('SUM(order_products.quantity) as total'))
            ->groupBy('order_products.product_id')
            ->whereIn('orders.order_status_id', [1, 2, 3, 4])
            ->orderBy('total','desc')
            ->limit(10)
            ->get();


        $chart     = new Chart();
        $this_year = json_encode($chart->setDataByYear(
            Order::chartData($chart->setQueryParams())
        ));
        $last_year = json_encode($chart->setDataByYear(
            Order::chartData($chart->setQueryParams(true))
        ));


       // dd($data['users']);

        return view('back.dashboard', compact('data', 'orders', 'bestsellers', 'products', 'this_year', 'last_year'));
    }


    /**
     * Import initialy from Excel files.
     *
     * @param Request $request
     */
    public function import(Request $request)
    {
        $import = new Import();
        $count  = 0;
        $temp = DB::table('temp_products')->get();

        foreach ($temp as $item) {
            $exist = Product::query()->where('sku', $item->sku)->first();

            if ( ! $exist) {
                $new_product_id = Product::query()->insertGetId([
                    'brand_id'     => 0,
                    'action_id'    => 0,
                    'sku'          => $item->sku,
                    'ean'          => '',
                    'price'        => $item->price ?: 0,
                    'quantity'     => 1,
                    'decrease'     => 1,
                    'tax_id'       => 1,
                    'vegan'        => 0,
                    'vegetarian'   => 0,
                    'glutenfree'   => 0,
                    'sort_order'   => 0,
                    'push'         => 0,
                    'status'       => 1,
                    'created_at'   => Carbon::now(),
                    'updated_at'   => Carbon::now()
                ]);

                if ($new_product_id) {
                    foreach (ag_lang() as $lang) {
                        ProductTranslation::query()->insertGetId([
                            'product_id'       => $new_product_id,
                            'lang'             => $lang->code,
                            'name'             => $item->name,
                            'description'      => $item->category . '<br><br>' . ProductHelper::cleanHTML($item->description),
                            'podaci'           => ProductHelper::cleanHTML($item->podaci),
                            'sastojci'         => ProductHelper::cleanHTML($item->sastojci),
                            'meta_title'       => $item->meta_title,
                            'meta_description' => $item->meta_description,
                            'slug'             => Str::slug($item->slug),
                            'created_at'       => Carbon::now(),
                            'updated_at'       => Carbon::now()
                        ]);
                    }

                    $images_arr = explode(', ', $item->images);

                    foreach ($images_arr as $key => $image) {
                        $path = $import->resolveImages($image, $item->name, $new_product_id);

                        if ($path) {
                            $default = 0;

                            if ( ! $key) {
                                Product::query()->where('id', $new_product_id)->update([
                                    'image' => $path
                                ]);

                                $default = 1;
                            }

                            $new_image_id = ProductImage::query()->insertGetId([
                                'product_id' => $new_product_id,
                                'image'      => $path,
                                'default'    => $default,
                                'published'  => 1,
                                'sort_order' => $key,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ]);

                            if ($new_image_id) {
                                foreach (ag_lang() as $lang) {
                                    ProductImageTranslation::query()->insertGetId([
                                        'product_image_id' => $new_image_id,
                                        'lang'             => $lang->code,
                                        'title'            => $item->name,
                                        'alt'              => $item->name,
                                        'created_at'       => Carbon::now(),
                                        'updated_at'       => Carbon::now()
                                    ]);
                                }
                            }
                        }
                    }

                    $count++;
                }

            }
        }

        return redirect()->route('dashboard')->with(['success' => 'Import je uspješno obavljen..! ' . $count . ' proizvoda importano.']);
    }


    /**
     * Set up roles. Should be done once only.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setRoles()
    {
        if ( ! auth()->user()->can('*')) {
            abort(401);
        }

        $superadmin = Bouncer::role()->firstOrCreate([
            'name'  => 'superadmin',
            'title' => 'Super Administrator',
        ]);

        Bouncer::role()->firstOrCreate([
            'name'  => 'admin',
            'title' => 'Administrator',
        ]);

        Bouncer::role()->firstOrCreate([
            'name'  => 'editor',
            'title' => 'Editor',
        ]);

        Bouncer::role()->firstOrCreate([
            'name'  => 'customer',
            'title' => 'Customer',
        ]);

        Bouncer::allow($superadmin)->everything();

        Bouncer::ability()->firstOrCreate([
            'name'  => 'set-super',
            'title' => 'Postavi korisnika kao Superadmina.'
        ]);

        $users = User::whereIn('email', ['filip@agmedia.hr', 'tomislav@agmedia.hr'])->get();

        foreach ($users as $user) {
            $user->assign($superadmin);
        }

        return redirect()->route('dashboard');
    }


    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function letters()
    {
        $authors = Brand::all();

        foreach ($authors as $author) {
            $letter = Helper::resolveFirstLetter($author->title);

            $author->update([
                'letter' => Str::ucfirst($letter)
            ]);
        }

        //
        $publishers = Publisher::all();

        foreach ($publishers as $publisher) {
            $letter = Helper::resolveFirstLetter($publisher->title);

            $publisher->update([
                'letter' => Str::ucfirst($letter)
            ]);
        }

        return redirect()->route('dashboard');
    }


    /**
     *
     */
    public function slugs()
    {
        $slugs = Product::query()->groupBy('slug')->havingRaw('COUNT(id) > 1')->pluck('slug', 'id')->toArray();

        foreach ($slugs as $slug) {
            $products = Product::where('slug', $slug)->get();

            if ($products) {
                foreach ($products as $product) {
                    $time = Str::random(9);
                    $product->update([
                        'slug' => $product->slug . '-' . $time,
                        'url' => $product->url . '-' . $time,
                    ]);
                }
            }
        }

        return redirect()->route('dashboard');
    }


    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function statuses()
    {
        // AUTHORS
        $products = Product::query()
                           ->where('quantity', '>', 0)
                           ->select('author_id', DB::raw('count(*) as total'))
                           ->groupBy('author_id')
                           ->pluck('author_id')
                           ->unique();

        $authors = Brand::query()->pluck('id')->diff($products)->flatten();

        Brand::whereIn('id', $authors)->update([
            'status' => 0,
            'updated_at' => now()
        ]);

        Brand::whereNotIn('id', $authors)->update([
            'status' => 1,
            'updated_at' => now()
        ]);

        // PUBLISHERS
        $products = Product::query()
                           ->where('quantity', '>', 0)
                           ->select('publisher_id', DB::raw('count(*) as total'))
                           ->groupBy('publisher_id')
                           ->pluck('publisher_id')
                           ->unique();

        $publishers = Publisher::query()->pluck('id')->diff($products)->flatten();

        Publisher::whereIn('id', $publishers)->update([
            'status' => 0,
            'updated_at' => now()
        ]);

        Publisher::whereNotIn('id', $publishers)->update([
            'status' => 1,
            'updated_at' => now()
        ]);

        // CATEGORIES
        $categories_off = Category::query()->select('id')->withCount('products')->having('products_count', '<', 1)->get()->toArray();

        if ($categories_off) {
            foreach ($categories_off as $category) {
                Category::where('id', $category['id'])->update([
                    'status' => 0,
                    'updated_at' => now()
                ]);
            }
        }

        $categories_on = Category::query()->select('id')->withCount('products')->having('products_count', '>', 0)->get()->toArray();

        if ($categories_on) {
            foreach ($categories_on as $category) {
                Category::where('id', $category['id'])->update([
                    'status' => 1,
                    'updated_at' => now()
                ]);
            }
        }

        // PRODUCTS
        $products = Product::where('quantity', 0)->pluck('id');

        Product::whereIn('id', $products)->update([
            'status' => 0,
            'updated_at' => now()
        ]);

        Product::whereNotIn('id', $products)->update([
            'status' => 1,
            'updated_at' => now()
        ]);

        return redirect()->route('dashboard');
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function mailing(Request $request)
    {
        $order = Order::where('id', 3)->first();

        dispatch(function () use ($order) {
            Mail::to(config('mail.admin'))->send(new OrderReceived($order));
            Mail::to($order->payment_email)->send(new OrderSent($order));
        });

        return redirect()->route('dashboard');
    }


    /**
     *
     */
    public function duplicate(string $target = null)
    {
        // Duplicate images
        if ($target === 'images') {
            $paths = ProductImage::query()->groupBy('image')->havingRaw('COUNT(id) > 1')->pluck('image', 'id')->toArray();

            foreach ($paths as $path) {
                $first = ProductImage::where('image', $path)->first();

                ProductImage::where('image', $path)->where('id', '!=', $first->id)->delete();
            }
        }

        // Duplicate publishers
        if ($target === 'publishers') {
            $paths = Publisher::query()->groupBy('title')->havingRaw('COUNT(id) > 1')->pluck('title', 'id')->toArray();

            foreach ($paths as $id => $path) {
                $group = Publisher::where('title', $path)->get();

                foreach ($group as $item) {
                    if ($item->id != $id) {
                        foreach ($item->products()->get() as $product) {
                            Product::where('id', $product->id)->update([
                                'publisher_id' => $id
                            ]);
                        }

                        Publisher::where('id', $item->id)->delete();
                    }
                }
            }
        }

        return redirect()->route('dashboard');
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setCategoryGroup(Request $request)
    {
        Category::query()->update([
            'group' => Helper::categoryGroupPath(true)
        ]);

        $products = Product::query()->where('push', 0)->get();

        foreach ($products as $product) {
            $product->update([
                'url'             => ProductHelper::url($product),
                'category_string' => ProductHelper::categoryString($product),
                'push'            => 1
            ]);
        }

        return redirect()->route('dashboard');
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setProductsUnlimitedQty(Request $request)
    {
        $products = ProductCategory::query()->where('category_id', 25)->pluck('product_id');

        Product::query()->whereIn('id', $products)->update([
            'quantity' => 100,
            'decrease' => 0,
            'status' => 1
        ]);

        return redirect()->route('dashboard')->with(['success' => 'Proizvodi su namješteni na neograničenu količinu..! ' . $products->count() . ' proizvoda obnovljeno.']);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setPdvProducts(Request $request)
    {
        $ids = ProductCategory::query()->whereIn('category_id', [174, 175])->pluck('product_id');

        Product::query()->whereIn('id', $ids)->update([
            'tax_id' => 2
        ]);

        return redirect()->route('dashboard')->with(['success' => 'PDV je obnovljen na kategoriji svezalice..! ' . $ids->count() . ' proizvoda obnovljeno.']);
    }

}
