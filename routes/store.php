<?php

use App\Http\Controllers\Admin\CatalogCategoryController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\IncomeController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OpenOrderController;
use App\Http\Controllers\Admin\PickupController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\WalkInController;
use App\Http\Controllers\Admin\WeeklyScheduleController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StoreAvailabilityController;
use App\Models\CatalogProduct;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/regions', RegionController::class)
    ->middleware(['auth', 'throttle:90,1,rintis-regions:'])
    ->name('regions');
Route::get('/explore', function (): Response {
    return Inertia::render('Directory', [
        'stores' => Store::query()->with('category')->where('is_listed', true)->orderBy('name')->select('id', 'name', 'slug', 'tagline', 'category_id', 'province', 'city')->paginate(12)->through(fn (Store $store): array => [...$store->publicProfile(), 'url' => route('storefront', $store)]),
    ]);
})->name('directory');

Route::get('/stores/{store:slug}', function (Request $request, Store $store): Response {
    if (! $store->is_listed) {
        abort_unless($request->user() && Gate::allows('view', $store), 404);
    }

    return Inertia::render('Storefront/Home', [
        'store' => $store->load('category')->publicProfile(),
        'isPreview' => ! $store->is_listed,
        'directoryUrl' => route('directory'),
        'openOrderUrl' => route('open-order', $store),
        'products' => $store->catalogProducts()->where('is_visible', true)->with('catalogCategory')->select('id', 'name', 'description', 'catalog_category_id', 'image_path', 'options')->withAvg('reviews', 'rating')->withCount('reviews')->orderBy('name')->orderBy('id')->paginate(12)->through(fn (CatalogProduct $product): array => [...$product->only('id', 'name', 'description'), 'category' => $product->catalogCategory->name, 'imageUrl' => $product->imageUrl(), 'options' => $product->publicOptions(), 'rating' => $product->reviews_avg_rating === null ? null : round((float) $product->reviews_avg_rating, 1), 'ratingCount' => $product->reviews_count]),
    ]);
})->name('storefront');

Route::get('/stores/{store:slug}/open-order', function (Store $store): Response {
    abort_unless($store->is_listed, 404);
    $period =
        $store->openOrders()->where('status', 'published')->where('closes_at', '>', now())->orderBy('opens_at')->first() ??
        $store
            ->openOrders()
            ->whereIn('status', ['published', 'closed'])
            ->latest('id')
            ->first();
    $visibleMenu = $period && in_array($period->displayStatus(), ['open', 'scheduled'], true);

    return Inertia::render('Storefront/OpenOrder', [
        'store' => $store->load('category')->publicProfile(),
        'period' => $period?->publicSummary(),
        'items' => $visibleMenu ? $period->items()->with(['product' => fn ($query) => $query->withAvg('reviews', 'rating')->withCount('reviews')])->orderBy('id')->get()->map(fn ($item): array => [...$item->publicDetails(), 'rating' => $item->product?->reviews_avg_rating === null ? null : round((float) $item->product->reviews_avg_rating, 1), 'ratingCount' => $item->product?->reviews_count ?? 0]) : [],
        'checkoutUrl' => $period && $period->status === 'published' ? route('checkout.show', [$store, $period]) : null,
        'serverNow' => now()->toIso8601String(),
        'catalogUrl' => route('storefront', $store),
        'timezone' => config('rintis.timezone'),
    ]);
})->name('open-order');

Route::prefix('stores/{store:slug}/sessions/{openOrder}/checkout')
    ->name('checkout.')
    ->scopeBindings()
    ->group(function (): void {
        Route::get('/', [CheckoutController::class, 'show'])->name('show');
        Route::post('/', [CheckoutController::class, 'store'])
            ->middleware('throttle:10,1,rintis-checkout:')
            ->name('store');
        Route::post('/quote', [CheckoutController::class, 'quote'])
            ->middleware('throttle:20,1,rintis-delivery-quote:')
            ->name('quote');
    });
Route::post('/payments/midtrans/notification', [MidtransController::class, 'webhook'])->middleware('throttle:60,1,rintis-payment-webhook:')->name('payment.notification');
Route::post('/orders/{token}/status', [MidtransController::class, 'refresh'])->middleware('throttle:12,1,rintis-payment-status:')->name('order.payment.refresh');
Route::post('/orders/{token}/payment', [MidtransController::class, 'retry'])->middleware('throttle:6,1,rintis-payment-retry:')->name('order.payment.retry');
Route::post('/orders/{token}/reviews', ReviewController::class)->middleware('throttle:20,1,rintis-reviews:')->name('order.review');
Route::get('/my-orders', OrderHistoryController::class)->name('orders.history');
Route::get('/orders/{token}', [CheckoutController::class, 'receipt'])
    ->middleware('throttle:60,1,rintis-receipt:')
    ->name('order.receipt');

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::prefix('stores/{store}')->scopeBindings()->group(function (): void {
            Route::get('/weekly-schedule', [WeeklyScheduleController::class, 'edit'])->name('weekly.edit');
            Route::put('/weekly-schedule', [WeeklyScheduleController::class, 'update'])->name('weekly.update');
            Route::post('/weekly-schedule/skip', [WeeklyScheduleController::class, 'skip'])->name('weekly.skip');
            Route::get('/sessions/{openOrder}/walk-in', [WalkInController::class, 'create'])->name('walk-in.create');
            Route::post('/sessions/{openOrder}/walk-in', [WalkInController::class, 'store'])->middleware('throttle:20,1,rintis-walk-in:')->name('walk-in.store');
        });
        Route::get('/income', [IncomeController::class, 'entry'])->name('income.entry');
        Route::get('/stores/{store}/income', [IncomeController::class, 'index'])->name('income.index');
        Route::get('/notifications', [NotificationController::class, 'index'])->middleware('throttle:30,1,rintis-notifications:')->name('notifications.index');
        Route::post('/notifications/read', [NotificationController::class, 'read'])->middleware('throttle:60,1,rintis-notifications-read:')->name('notifications.read');
        Route::get('/deliveries', [DeliveryController::class, 'entry'])->name('deliveries.entry');
        Route::prefix('stores/{store}/deliveries')->name('deliveries.')->group(function (): void {
            Route::get('/', [DeliveryController::class, 'index'])->name('index');
            Route::post('/plan', [DeliveryController::class, 'plan'])->middleware('throttle:rintis-delivery-plan')->name('plan');
            Route::post('/bulk', [DeliveryController::class, 'bulk'])->name('bulk');
            Route::post('/{run}/start', [DeliveryController::class, 'start'])->name('start');
            Route::post('/{run}/clear', [DeliveryController::class, 'clear'])->name('clear');
            Route::post('/{run}/complete', [DeliveryController::class, 'complete'])->name('complete');
        });
        Route::get('/pickup', [PickupController::class, 'entry'])->name('pickup.entry');
        Route::prefix('stores/{store}/pickup')
            ->name('pickup.')
            ->group(function (): void {
                Route::get('/', [PickupController::class, 'show'])->name('show');
                Route::post('/lookup', [PickupController::class, 'lookup'])
                    ->middleware('throttle:30,1,rintis-pickup-lookup:')
                    ->name('lookup');
                Route::post('/complete', [PickupController::class, 'complete'])
                    ->middleware('throttle:30,1,rintis-pickup-complete:')
                    ->name('complete');
            });
        Route::get('/open-orders', [OpenOrderController::class, 'entry'])->name('open-orders.entry');
        Route::prefix('stores/{store}/open-orders')
            ->name('open-orders.')
            ->scopeBindings()
            ->group(function (): void {
                Route::get('/', [OpenOrderController::class, 'index'])->name('index');
                Route::get('/create', [OpenOrderController::class, 'create'])->name('create');
                Route::post('/', [OpenOrderController::class, 'store'])->name('store');
                Route::post('/{openOrder}/orders/{order}/cancel', [OpenOrderController::class, 'cancelOrder'])->name('order-cancel');
                Route::patch('/{openOrder}/orders/{order}/status', [OpenOrderController::class, 'updateOrderStatus'])->name('order-status');
                Route::post('/{openOrder}/orders/bulk-status', [OpenOrderController::class, 'bulkStatus'])->name('bulk-status');
                Route::get('/{openOrder}/orders', [OpenOrderController::class, 'orders'])->name('orders');
                Route::get('/{openOrder}/edit', [OpenOrderController::class, 'edit'])->name('edit');
                Route::patch('/{openOrder}', [OpenOrderController::class, 'update'])->name('update');
                Route::patch('/{openOrder}/close', [OpenOrderController::class, 'close'])->name('close');
                Route::patch('/{openOrder}/cancel', [OpenOrderController::class, 'cancel'])->name('cancel');
            });
        Route::get('/store-availability', StoreAvailabilityController::class)->middleware('throttle:120,1,rintis-store-availability:')->name('availability');
        Route::get('/', [StoreController::class, 'index'])->name('index');
        Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');
        Route::get('/stores/{store}/settings', [StoreController::class, 'edit'])->name('stores.edit');
        Route::patch('/stores/{store}/setup', [StoreController::class, 'completeSetup'])->name('stores.setup');
        Route::patch('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::prefix('stores/{store}/catalog')
            ->name('catalog.')
            ->scopeBindings()
            ->group(function (): void {
                Route::get('/', [CatalogController::class, 'index'])->name('index');
                Route::get('/products/create', [CatalogController::class, 'create'])->name('create');
                Route::post('/products', [CatalogController::class, 'store'])->name('store');
                Route::get('/products/{catalogProduct}/edit', [CatalogController::class, 'edit'])->name('edit');
                Route::delete('/products/{catalogProduct}', [CatalogController::class, 'destroy'])->name('destroy');
                Route::patch('/products/{catalogProduct}', [CatalogController::class, 'update'])->name('update');
                Route::post('/categories', [CatalogCategoryController::class, 'store'])->name('categories.store');
                Route::patch('/categories/{catalogCategory}', [CatalogCategoryController::class, 'update'])->name('categories.update');
            });
    });
