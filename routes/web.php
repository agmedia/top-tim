<?php

use App\Actions\Fortify\ForgotPasswordController;
use App\Http\Controllers\Api\v2\CartController;
use App\Http\Controllers\Api\v2\FilterController;
use App\Http\Controllers\Back\Catalog\BrandController;
use App\Http\Controllers\Back\Catalog\CategoryController;
use App\Http\Controllers\Back\Catalog\ProductController;
use App\Http\Controllers\Back\Catalog\AttributesController;
use App\Http\Controllers\Back\Catalog\OptionsController;
use App\Http\Controllers\Back\Catalog\PublisherController;
use App\Http\Controllers\Back\DashboardController;
use App\Http\Controllers\Back\Marketing\ReviewController;
use App\Http\Controllers\Back\OrderController;
use App\Http\Controllers\Back\Marketing\ActionController;
use App\Http\Controllers\Back\Marketing\BlogController;
use App\Http\Controllers\Back\Marketing\ReceptiController;
use App\Http\Controllers\Back\Settings\ApiController;
use App\Http\Controllers\Back\Settings\App\CurrencyController;
use App\Http\Controllers\Back\Settings\App\GeoZoneController;
use App\Http\Controllers\Back\Settings\App\LanguagesController;
use App\Http\Controllers\Back\Settings\App\OrderStatusController;
use App\Http\Controllers\Back\Settings\App\PaymentController;
use App\Http\Controllers\Back\Settings\App\ShippingController;
use App\Http\Controllers\Back\Settings\App\TaxController;
use App\Http\Controllers\Back\Settings\FaqController;
use App\Http\Controllers\Back\Settings\SizeGuideController;
use App\Http\Controllers\Back\Settings\HistoryController;
use App\Http\Controllers\Back\Settings\PageController;
use App\Http\Controllers\Back\Settings\QuickMenuController;
use App\Http\Controllers\Back\Settings\SettingsController;
use App\Http\Controllers\Back\Settings\System\ApplicationController;
use App\Http\Controllers\Back\UserController;
use App\Http\Controllers\Back\UserGroupController;
use App\Http\Controllers\Back\Widget\WidgetController;
use App\Http\Controllers\Back\Widget\WidgetGroupController;
use App\Http\Controllers\Front\CatalogRouteController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\CustomerController;
use App\Http\Controllers\Front\HomeController;
use App\Models\Front\Checkout\Payment\Keks;
use App\Models\Front\Checkout\Payment\MyPos;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*Route::domain('https://images.antikvarijatbibl.lin73.host25.com/')->group(function () {
    Route::get('media/img/products/{id}/{image}', function ($id, $image) {
        \Illuminate\Support\Facades\Log::info($id . ' --- ' . $image);
    });
});*/
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/**
 * BACK ROUTES
 */
Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]
    ], function() {

    Route::middleware(['auth:sanctum', 'verified', 'no.customers'])->prefix('admin')->group(function () {
        Route::match(['get', 'post'], '/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('setRoles', [DashboardController::class, 'setRoles'])->name('roles.set');
        Route::get('import', [DashboardController::class, 'import'])->name('import.initial');
        Route::get('update', [DashboardController::class, 'update'])->name('update.initial');
        Route::get('mailing-test', [DashboardController::class, 'mailing'])->name('mailing.test');
        Route::get('letters', [DashboardController::class, 'letters'])->name('letters.import');
        Route::get('slugs', [DashboardController::class, 'slugs'])->name('slugs.revision');
        Route::get('statuses', [DashboardController::class, 'statuses'])->name('statuses.cron');
        Route::get('duplicate/{target?}', [DashboardController::class, 'duplicate'])->name('duplicate.revision');
        Route::get('set/category-group', [DashboardController::class, 'setProductsURL'])->name('set.url');
        Route::get('set/pdv/products', [DashboardController::class, 'setPdvProducts'])->name('set.pdv.products');
        Route::get('set/unlimited-qty', [DashboardController::class, 'setProductsUnlimitedQty'])->name('set.unlimited');

        // CATALOG
        Route::prefix('catalog')->group(function () {
            // KATEGORIJE
            Route::get('categories', [CategoryController::class, 'index'])->name('categories');
            Route::get('category/create', [CategoryController::class, 'create'])->name('category.create');
            Route::post('category', [CategoryController::class, 'store'])->name('category.store');
            Route::get('category/{category}/edit', [CategoryController::class, 'edit'])->name('category.edit');
            Route::patch('category/{category}', [CategoryController::class, 'update'])->name('category.update');
            Route::delete('category/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');

            // ARTIKLI
            Route::get('products', [ProductController::class, 'index'])->name('products');
            Route::get('product/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('product', [ProductController::class, 'store'])->name('products.store');
            Route::get('product/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::patch('product/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('product/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

            // Atttributes
            Route::get('attributes', [AttributesController::class, 'index'])->name('attributes');
            Route::get('attribute/create', [AttributesController::class, 'create'])->name('attributes.create');
            Route::post('attribute', [AttributesController::class, 'store'])->name('attributes.store');
            Route::get('attribute/{attributes}/edit', [AttributesController::class, 'edit'])->name('attributes.edit');
            Route::patch('attribute/{attributes}', [AttributesController::class, 'update'])->name('attributes.update');
            Route::delete('attribute/{attributes}', [AttributesController::class, 'destroy'])->name('attributes.destroy');

            // OPtions
            Route::get('options', [OptionsController::class, 'index'])->name('options');
            Route::get('option/create', [OptionsController::class, 'create'])->name('options.create');
            Route::post('option', [OptionsController::class, 'store'])->name('options.store');
            Route::get('option/{options}/edit', [OptionsController::class, 'edit'])->name('options.edit');
            Route::patch('option/{options}', [OptionsController::class, 'update'])->name('options.update');
            Route::delete('option/{options}', [OptionsController::class, 'destroy'])->name('options.destroy');

            // AUTORI
            Route::get('brands', [BrandController::class, 'index'])->name('brands');
            Route::get('brand/create', [BrandController::class, 'create'])->name('brands.create');
            Route::post('brand', [BrandController::class, 'store'])->name('brands.store');
            Route::get('brand/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
            Route::patch('brand/{brand}', [BrandController::class, 'update'])->name('brands.update');
            Route::delete('brand/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

            // BLOG
            Route::get('blogs', [BlogController::class, 'index'])->name('blogs');
            Route::get('blog/create', [BlogController::class, 'create'])->name('blogs.create');
            Route::post('blog', [BlogController::class, 'store'])->name('blogs.store');
            Route::get('blog/{blog}/edit', [BlogController::class, 'edit'])->name('blogs.edit');
            Route::patch('blog/{blog}', [BlogController::class, 'update'])->name('blogs.update');
            Route::delete('blog/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');

            // INFO PAGES
            Route::get('pages', [PageController::class, 'index'])->name('pages');
            Route::get('page/create', [PageController::class, 'create'])->name('pages.create');
            Route::post('page', [PageController::class, 'store'])->name('pages.store');
            Route::get('page/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
            Route::patch('page/{page}', [PageController::class, 'update'])->name('pages.update');
            Route::delete('page/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

        });

        // NARUDŽBE
        Route::get('orders', [OrderController::class, 'index'])->name('orders');
        Route::get('order/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('order', [OrderController::class, 'store'])->name('orders.store');
        Route::get('order/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('order/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::patch('order/{order}', [OrderController::class, 'update'])->name('orders.update');

        // MARKETING
        Route::prefix('marketing')->group(function () {
            // AKCIJE
            Route::get('actions', [ActionController::class, 'index'])->name('actions');
            Route::get('action/create', [ActionController::class, 'create'])->name('actions.create');
            Route::post('action', [ActionController::class, 'store'])->name('actions.store');
            Route::get('action/{action}/edit', [ActionController::class, 'edit'])->name('actions.edit');
            Route::patch('action/{action}', [ActionController::class, 'update'])->name('actions.update');
            Route::delete('action/{action}', [ActionController::class, 'destroy'])->name('actions.destroy');

            // REWIEVS
            Route::get('reviews', [ReviewController::class, 'index'])->name('reviews');
            Route::get('review/create', [ReviewController::class, 'create'])->name('reviews.create');
            Route::post('review', [ReviewController::class, 'store'])->name('reviews.store');
            Route::get('review/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
            Route::patch('review/{review}', [ReviewController::class, 'update'])->name('reviews.update');
            Route::delete('review/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

            // FAQ
            Route::get('faqs', [FaqController::class, 'index'])->name('faqs');
            Route::get('faq/create', [FaqController::class, 'create'])->name('faqs.create');
            Route::post('faq', [FaqController::class, 'store'])->name('faqs.store');
            Route::get('faq/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
            Route::patch('faq/{faq}', [FaqController::class, 'update'])->name('faqs.update');
            Route::delete('faq/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

            // SizeGuide
            Route::get('sizeguides', [SizeGuideController::class, 'index'])->name('sizeguides');
            Route::get('sizeguide/create', [SizeGuideController::class, 'create'])->name('sizeguides.create');
            Route::post('sizeguide', [SizeGuideController::class, 'store'])->name('sizeguides.store');
            Route::get('sizeguide/{sizeguide}/edit', [SizeGuideController::class, 'edit'])->name('sizeguides.edit');
            Route::patch('sizeguide/{sizeguide}', [SizeGuideController::class, 'update'])->name('sizeguides.update');
            Route::delete('sizeguide/{sizeguide}', [SizeGuideController::class, 'destroy'])->name('sizeguides.destroy');

        });

        // KORISNICI
        Route::get('users', [UserController::class, 'index'])->name('users');
        Route::get('user/create', [UserController::class, 'create'])->name('users.create');
        Route::post('user', [UserController::class, 'store'])->name('users.store');
        Route::get('user/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('user/{user}', [UserController::class, 'update'])->name('users.update');

        // POPUSTI
        Route::get('discounts', [DiscountController::class, 'index'])->name('discounts');

        // GRUPE KORISNIKA
        Route::get('user_groups', [UserGroupController::class, 'index'])->name('user_groups');
        Route::get('user_groups/create', [UserGroupController::class, 'create'])->name('user_groups.create');
        Route::post('user_groups', [UserGroupController::class, 'store'])->name('user_groups.store');
        Route::get('user_groups/{user_groups}/edit', [UserGroupController::class, 'edit'])->name('user_groups.edit');
        Route::patch('user_groups/{user_groups}', [UserGroupController::class, 'update'])->name('user_groups.update');
        Route::delete('user_groups/{user_groups}', [UserGroupController::class, 'destroy'])->name('user_groups.destroy');

        // WIDGETS
        Route::prefix('widgets')->group(function () {
            Route::get('/', [WidgetController::class, 'index'])->name('widgets');
            Route::get('create', [WidgetController::class, 'create'])->name('widget.create');
            Route::post('/', [WidgetController::class, 'store'])->name('widget.store');
            Route::get('{widget}/edit', [WidgetController::class, 'edit'])->name('widget.edit');
            Route::patch('{widget}', [WidgetController::class, 'update'])->name('widget.update');
            // GROUP
            Route::prefix('groups')->group(function () {
                Route::get('create', [WidgetGroupController::class, 'create'])->name('widget.group.create');
                Route::post('/', [WidgetGroupController::class, 'store'])->name('widget.group.store');
                Route::get('{widget}/edit', [WidgetGroupController::class, 'edit'])->name('widget.group.edit');
                Route::patch('{widget}', [WidgetGroupController::class, 'update'])->name('widget.group.update');
            });
        });

        // POSTAVKE
        Route::prefix('settings')->group(function () {
            // API
            Route::get('api', [ApiController::class, 'index'])->name('api.index');
            Route::get('api/cron-reports', [ApiController::class, 'cronReports'])->name('api.cron.reports');
            Route::get('api/products/simple/excel', [ApiController::class, 'exportToSimpleExcel'])->name('export.simple.excel');
            Route::get('api/products/excel', [ApiController::class, 'exportToExcel'])->name('export.excel');
            Route::get('api/eracuni/export', [ApiController::class, 'exportToEracuni'])->name('export.eracuni');

            //Route::get('application', [SettingsController::class, 'index'])->name('settings');

            // SISTEM
            Route::prefix('system')->group(function () {
                // APPLICATION SETTINGS
                Route::get('application', [ApplicationController::class, 'index'])->name('application.settings');
            });

            Route::prefix('application')->group(function () {
                Route::get('languages', [LanguagesController::class, 'index'])->name('languages');
                // GEO ZONES
                Route::get('geo-zones', [GeoZoneController::class, 'index'])->name('geozones');
                Route::get('geo-zone/create', [GeoZoneController::class, 'create'])->name('geozones.create');
                Route::post('geo-zone', [GeoZoneController::class, 'store'])->name('geozones.store');
                Route::get('geo-zone/{geozone}/edit', [GeoZoneController::class, 'edit'])->name('geozones.edit');
                Route::patch('geo-zone/{geozone}', [GeoZoneController::class, 'store'])->name('geozones.update');
                Route::delete('geo-zone/{geozone}', [GeoZoneController::class, 'destroy'])->name('geozones.destroy');
                //
                Route::get('order-statuses', [OrderStatusController::class, 'index'])->name('order.statuses');
                //
                Route::get('shippings', [ShippingController::class, 'index'])->name('shippings');
                Route::get('payments', [PaymentController::class, 'index'])->name('payments');
                Route::get('taxes', [TaxController::class, 'index'])->name('taxes');
                Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies');
            });

            // HISTORY
            Route::get('history', [HistoryController::class, 'index'])->name('history');
            Route::get('history/log/{history}', [HistoryController::class, 'show'])->name('history.show');
        });

        // SETTINGS
        Route::get('/clean/cache', [QuickMenuController::class, 'cache'])->name('cache');
        Route::get('maintenance/on', [QuickMenuController::class, 'maintenanceModeON'])->name('maintenance.on');
        Route::get('maintenance/off', [QuickMenuController::class, 'maintenanceModeOFF'])->name('maintenance.off');
    });
});

/**
 * API Routes
 */
Route::prefix('api/v2')->group(function () {
    // SEARCH
    //Route::get('pretrazi', [CatalogRouteController::class, 'search'])->name('api.front.search');
    // CART
    Route::prefix('cart')->group(function () {
        Route::get('/get', [CartController::class, 'get']);
        Route::post('/check', [CartController::class, 'check']);
        Route::post('/add', [CartController::class, 'add']);
        Route::post('/update/{id}', [CartController::class, 'update']);
        Route::get('/remove/{id}', [CartController::class, 'remove']);
        Route::get('/coupon/{coupon}', [CartController::class, 'coupon']);
        Route::get('/loyalty/{loyalty}', [CartController::class, 'loyalty']);
        //Route::get('/options/{option}', [CartController::class, 'options']);
    });

    Route::get('/products/autocomplete', [\App\Http\Controllers\Api\v2\ProductController::class, 'autocomplete'])->name('products.autocomplete');
    Route::get('/products/options/{option}', [\App\Http\Controllers\Api\v2\ProductController::class, 'options'])->name('products.check.options');
    Route::get('/products/list/options', [\App\Http\Controllers\Api\v2\ProductController::class, 'optionsList'])->name('products.list.options');
    Route::post('/products/image/delete', [\App\Http\Controllers\Api\v2\ProductController::class, 'destroyImage'])->name('products.destroy.image');
    Route::post('/products/change/status', [\App\Http\Controllers\Api\v2\ProductController::class, 'changeStatus'])->name('products.change.status');
    Route::post('products/update-item/single', [\App\Http\Controllers\Api\v2\ProductController::class, 'updateItem'])->name('products.update.item');
    Route::get('/products/bulk/action', [\App\Http\Controllers\Api\v2\ProductController::class, 'bulkAction'])->name('products.bulk.action');

    Route::post('/actions/destroy/api', [ActionController::class, 'destroyApi'])->name('actions.destroy.api');
    Route::post('/reviews/destroy/api', [ReviewController::class, 'destroyApi'])->name('reviews.destroy.api');
    Route::post('/brands/destroy/api', [BrandController::class, 'destroyApi'])->name('brands.destroy.api');
    Route::post('/publishers/destroy/api', [PublisherController::class, 'destroyApi'])->name('publishers.destroy.api');
    Route::post('/products/destroy/api', [ProductController::class, 'destroyApi'])->name('products.destroy.api');
    Route::post('/blogs/destroy/api', [BlogController::class, 'destroyApi'])->name('blogs.destroy.api');
    Route::post('/blogs/upload/image', [BlogController::class, 'uploadBlogImage'])->name('blogs.upload.image');

    Route::post('/receptis/destroy/api', [ReceptiController::class, 'destroyApi'])->name('receptis.destroy.api');
    Route::post('/receptis/upload/image', [ReceptiController::class, 'uploadReceptiImage'])->name('receptis.upload.image');

    // FILTER
    /*Route::prefix('filter')->group(function () {
        Route::post('/getCategories', [FilterController::class, 'categories']);
        Route::post('/getProducts', [FilterController::class, 'products']);
        Route::post('/getAuthors', [FilterController::class, 'authors']);
        Route::post('/getPublishers', [FilterController::class, 'publishers']);
    });*/




    // SETTINGS
    Route::prefix('settings')->group(function () {
        // FRONT SETTINGS LIST
        Route::get('/get', [SettingsController::class, 'get']);
        // WIDGET
        Route::prefix('widget')->group(function () {
            Route::post('group/destroy', [WidgetGroupController::class, 'destroy'])->name('widget.group.destroy.api');
            Route::post('destroy', [WidgetController::class, 'destroy'])->name('widget.destroy');
            Route::get('get-links', [WidgetController::class, 'getLinks'])->name('widget.api.get-links');
        });
        // API
        Route::prefix('api')->group(function () {
            Route::post('import', [ApiController::class, 'import'])->name('api.api.import');
            Route::post('upload/excel', [ApiController::class, 'upload'])->name('api.api.upload');
            Route::post('export/products', [ApiController::class, 'exportProducts'])->name('api.api.export.products');
        });
        // SYSTEM
        Route::prefix('system')->group(function () {
            // APPLICATION
            Route::prefix('application')->group(function () {
                Route::post('basic/store', [ApplicationController::class, 'basicInfoStore'])->name('api.application.basic.store');
                Route::post('maps-api/store', [ApplicationController::class, 'storeGoogleMapsApiKey'])->name('api.application.google-api.store.key');
            });
        });
        // APPLICATION SETTINGS
        Route::prefix('app')->group(function () {
            // LANGUAGES
            Route::prefix('languages')->group(function () {
                Route::post('store', [LanguagesController::class, 'store'])->name('api.languages.store');
                Route::post('store/main', [LanguagesController::class, 'storeMain'])->name('api.languages.store.main');
                Route::post('destroy', [LanguagesController::class, 'destroy'])->name('api.languages.destroy');
            });
            // GEO ZONE
            Route::prefix('geo-zone')->group(function () {
                Route::post('get-state-zones', 'Back\Settings\Store\GeoZoneController@getStateZones')->name('geo-zone.get-state-zones');
                Route::post('store', 'Back\Settings\Store\GeoZoneController@store')->name('geo-zone.store');
                Route::post('destroy', 'Back\Settings\Store\GeoZoneController@destroy')->name('geo-zone.destroy');
            });
            // ORDER STATUS
            Route::prefix('order-status')->group(function () {
                Route::post('store', [OrderStatusController::class, 'store'])->name('api.order.status.store');
                Route::post('destroy', [OrderStatusController::class, 'destroy'])->name('api.order.status.destroy');

                Route::post('change', [OrderController::class, 'api_status_change'])->name('api.order.status.change');
                Route::post('send/gls', [OrderController::class, 'api_send_gls'])->name('api.order.send.gls');
            });
            // PAYMENTS
            Route::prefix('payment')->group(function () {
                Route::post('store', [PaymentController::class, 'store'])->name('api.payment.store');
                Route::post('destroy', [PaymentController::class, 'destroy'])->name('api.payment.destroy');
            });
            // SHIPMENTS
            Route::prefix('shipping')->group(function () {
                Route::post('store', [ShippingController::class, 'store'])->name('api.shipping.store');
                Route::post('destroy', [ShippingController::class, 'destroy'])->name('api.shipping.destroy');
            });
            // TAXES
            Route::prefix('taxes')->group(function () {
                Route::post('store', [TaxController::class, 'store'])->name('api.taxes.store');
                Route::post('destroy', [TaxController::class, 'destroy'])->name('api.taxes.destroy');
            });
            // CURRENCIES
            Route::prefix('currencies')->group(function () {
                Route::post('store', [CurrencyController::class, 'store'])->name('api.currencies.store');
                Route::post('store/main', [CurrencyController::class, 'storeMain'])->name('api.currencies.store.main');
                Route::post('destroy', [CurrencyController::class, 'destroy'])->name('api.currencies.destroy');
            });
            // TOTALS
            /*Route::prefix('totals')->group(function () {
                Route::post('store', 'Back\Settings\Store\TotalController@store')->name('totals.store');
                Route::post('destroy', 'Back\Settings\Store\TotalController@destroy')->name('totals.destroy');
            });*/
        });
    });
});

/*Route::get('/phpinfo', function () {
    return phpinfo();
})->name('index');*/


/**
 * FRONT ROUTES
 */
Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]
    ], function() {

    /**
     * API Routes
     */
    Route::prefix('api/v2')->group(function () {
        // SEARCH
        Route::get('pretrazi/autocomplete', [CatalogRouteController::class, 'search'])->name('api.front.autocomplete');
        Route::get('pretrazi', [CatalogRouteController::class, 'search'])->name('api.front.search');
        // FILTER
        Route::prefix('filter')->group(function () {
            Route::post('/getCategories', [FilterController::class, 'categories']);
            Route::post('/getProducts', [FilterController::class, 'products']);
            Route::post('/getAuthors', [FilterController::class, 'authors']);
            Route::post('/getPublishers', [FilterController::class, 'publishers']);
            Route::post('/getBrands', [FilterController::class, 'brands']);
            Route::post('/getAttributes', [FilterController::class, 'attributes']);
            Route::post('/getOptions', [FilterController::class, 'options']);
        });
    });

    /**
     * CUSTOMER BACK ROUTES
     */
    Route::middleware(['auth:sanctum', 'verified'])->prefix('moj-racun')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('moj-racun');
        Route::patch('/snimi/{user}', [CustomerController::class, 'save'])->name('moj-racun.snimi');
        Route::get('/narudzbe', [CustomerController::class, 'orders'])->name('moje-narudzbe');
        Route::get('/loyalty', [CustomerController::class, 'loyalty'])->name('loyalty');

    });

    Route::get('/', [HomeController::class, 'index'])->name('index');
    Route::get('/kontakt', [HomeController::class, 'contact'])->name('kontakt');
    Route::post('/kontakt/posalji', [HomeController::class, 'sendContactMessage'])->name('poruka');
    Route::get('/faq', [CatalogRouteController::class, 'faq'])->name('faq');
    Route::post('/komentar/proizvoda/posalji', [HomeController::class, 'sendProductComment'])->name('komentar.proizvoda');
//
    Route::get('/kosarica', [CheckoutController::class, 'cart'])->name('kosarica');
    Route::get('/naplata', [CheckoutController::class, 'checkout'])->name('naplata');
    Route::get('/pregled', [CheckoutController::class, 'view'])->name('pregled');
    Route::get('/narudzba', [CheckoutController::class, 'order'])->name('checkout');
    Route::get('/uspjeh', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/greska', [CheckoutController::class, 'error'])->name('checkout.error');
    //
    Route::post('/keks/uspjeh', [CheckoutController::class, 'successKeks'])->name('checkout.success.keks');
    Route::post('kekspay/provjera-narudzbe', [Keks::class, 'check'])->name('keks.provjera');
    Route::post('/mypos/notify', [MyPos::class, 'notify'])->name('mypos.notify');
    Route::post('/mypos/uspjeh', [CheckoutController::class, 'myposSuccess'])->name('checkout.mypos.success');
    Route::post('/mypos/cancel', [MyPos::class, 'cancel'])->name('checkout.mypos.cancel');

//
    Route::get('pretrazi', [CatalogRouteController::class, 'search'])->name('pretrazi');

    Route::post('newsletter', [HomeController::class, 'newsletter'])->name('newsletter');
//
    Route::get('info/{page}', [CatalogRouteController::class, 'page'])->name('catalog.route.page');
    Route::get('blog/{blog?}', [CatalogRouteController::class, 'blog'])->name('catalog.route.blog');
    Route::get('recepti/{recepti?}', [CatalogRouteController::class, 'recepti'])->name('catalog.route.recepti');
//
    Route::get('cache/image', [HomeController::class, 'imageCache']);
    Route::get('cache/thumb', [HomeController::class, 'thumbCache']);
    /**
     * Sitemap routes
     */
    Route::redirect('/sitemap.xml', '/sitemap');
    Route::get('sitemap/{sitemap?}', [HomeController::class, 'sitemapXML'])->name('sitemap');
    Route::get('image-sitemap', [HomeController::class, 'sitemapImageXML'])->name('sitemap-image');
    /**
     * Forgot password & login routes.
     */
    Route::get('forgot-password', [ForgotPasswordController::class, 'showForgetPasswordForm'])->name('forget.password.get');
    Route::post('forgot-password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.post');
    Route::get('reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('reset.password.get');
    Route::post('reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');
    /*
     * Groups, Categories and Products routes resolver.
     * https://www.antikvarijat-biblos.hr/kategorija-proizvoda/knjige/
     */
    //Route::get('proizvod/{prod?}/', [CatalogRouteController::class, 'resolveOldUrl']);
//Route::get('kategorija-proizvoda/{group?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'resolveOldCategoryUrl']);
//
    Route::get(config('settings.author_path') . '/{author?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'author'])->name('catalog.route.author');
    Route::get(config('settings.publisher_path') . '/{publisher?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'publisher'])->name('catalog.route.publisher');

    Route::get(config('settings.brand_path') . '/{brand?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'brand'])->name('catalog.route.brand');
//
    Route::get('akcijska-ponuda/{cat?}/{subcat?}', [CatalogRouteController::class, 'actions'])->name('catalog.route.actions');
//
    //Route::get(LaravelLocalization::transRoute('{group}/{cat?}/{subcat?}/{prod?}'), [CatalogRouteController::class, 'resolve'])->name('catalog.route');

    Route::get('{group}/{cat?}/{subcat?}/{prod?}', [CatalogRouteController::class, 'resolve'])->name('catalog.route');

    Route::fallback(function () {
        return view('front.404');
    });
});
