<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Models\CatalogProduct;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $store = $request->user()->store;

        return $store
            ? to_route('admin.stores.show', $store)
            : to_route('admin.stores.create');
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->store) {
            return to_route('admin.index');
        }

        return Inertia::render('Admin/StoreForm', [
            'store' => null,
            'categories' => StoreCategory::query()->orderBy('name')->get(['id', 'name']),
            'submitUrl' => route('admin.stores.store'),
            'backUrl' => route('directory'),
            'regionsUrl' => route('regions'),
            'mapTilesUrl' => config('rintis.map_tiles_url'),
            'availabilityUrl' => route('admin.availability'),
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $store = $this->saveTransaction($request, function () use ($request): Store {
            $owner = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            $existing = $owner->store()->first();
            if ($existing) {
                return $existing;
            }
            $created = $owner->store()->create($request->storeAttributes());
            $this->saveBankAccounts($request, $created);
            $created->completeSetupIfReady();

            return $created;
        });

        return to_route('admin.stores.show', $store);
    }

    public function show(Store $store): Response
    {
        Gate::authorize('view', $store);

        return Inertia::render('Admin/Overview', [
            'products' => $store->catalogProducts()->select('id', 'store_id', 'name', 'description', 'image_path')->orderBy('name')->orderBy('id')->paginate(12)->through(fn (CatalogProduct $product): array => [...$product->only('id', 'name', 'description'), 'imageUrl' => $product->imageUrl(), 'editUrl' => route('admin.catalog.edit', [$store, $product])]),
            'createProductUrl' => route('admin.catalog.create', $store),
            'store' => $store->only('delivery_options', 'cash_enabled', 'bank_transfer_enabled', 'name', 'slug', 'tagline', 'is_listed', 'category_id', 'province', 'city', 'district', 'province_code', 'city_code', 'district_code', 'address_line', 'latitude', 'longitude', 'pickup_enabled', 'pickup_instructions', 'delivery_enabled', 'delivery_fee_mode', 'delivery_fixed_fee', 'delivery_rate_per_km', 'delivery_min_distance_m', 'delivery_max_distance_m'),
            'settingsUrl' => route('admin.stores.edit', $store),
            'setupCompleted' => $store->setup_completed_at !== null,
            'setup' => [
                'settingsReady' => $store->settingsReady(),
                'categories' => $store->catalogCategories()->count(),
                'products' => $store->catalogProducts()->count(),
                'visibleProducts' => $store->catalogProducts()->where('is_visible', true)->count(),
                'bankAccounts' => $store->bankAccounts()->where('is_active', true)->count(),
                'locationReady' => (bool) ($store->province && $store->city && $store->district && $store->address_line),
                'pinReady' => $store->latitude !== null && $store->longitude !== null,
            ],
            'catalogUrl' => route('admin.catalog.index', $store),
            'storefrontUrl' => route('storefront', $store),
        ]);
    }

    public function edit(Store $store): Response
    {
        Gate::authorize('update', $store);

        return Inertia::render('Admin/StoreForm', [
            'store' => $store->only('delivery_options', 'cash_enabled', 'bank_transfer_enabled', 'name', 'slug', 'tagline', 'is_listed', 'category_id', 'province', 'city', 'district', 'province_code', 'city_code', 'district_code', 'address_line', 'latitude', 'longitude', 'pickup_enabled', 'pickup_instructions', 'delivery_enabled', 'delivery_fee_mode', 'delivery_fixed_fee', 'delivery_rate_per_km', 'delivery_min_distance_m', 'delivery_max_distance_m'),
            'categories' => StoreCategory::query()->orderBy('name')->get(['id', 'name']),
            'submitUrl' => route('admin.stores.update', $store),
            'backUrl' => route('admin.stores.show', $store),
            'settingsUrl' => route('admin.stores.edit', $store),
            'catalogUrl' => route('admin.catalog.index', $store),
            'bankAccounts' => $store->bankAccounts()->orderBy('id')->get(['id', 'bank_name', 'account_holder', 'account_number', 'is_active']),
            'regionsUrl' => route('regions'),
            'mapTilesUrl' => config('rintis.map_tiles_url'),
            'availabilityUrl' => route('admin.availability'),
        ]);
    }

    public function update(StoreRequest $request, Store $store): RedirectResponse
    {
        Gate::authorize('update', $store);
        $this->saveTransaction($request, function () use ($request, $store): void {
            Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();
            $store->update($request->storeAttributes());
            $this->saveBankAccounts($request, $store);
            $store->completeSetupIfReady();
        });

        return to_route('admin.stores.show', $store)->with('status', 'Store settings saved.');
    }

    public function completeSetup(Store $store): RedirectResponse
    {
        Gate::authorize('update', $store);
        abort_unless($store->settingsReady() && $store->catalogCategories()->exists()
            && $store->catalogProducts()->where('is_visible', true)->exists(), 422, 'Complete settings and catalog first.');
        $store->completeSetupIfReady();

        return to_route('admin.stores.show', $store)->with('status', 'Store setup complete. Welcome to your workspace!');
    }

    private function saveTransaction(StoreRequest $request, Closure $action): mixed
    {
        try {
            return DB::transaction($action);
        } catch (UniqueConstraintViolationException $exception) {
            $store = $request->route('store');
            $query = Store::query()->when($store instanceof Store, fn ($query) => $query->whereKeyNot($store->id));
            if ((clone $query)->where('name_key', Store::nameKey($request->validated('name')))->exists()) {
                throw ValidationException::withMessages(['name' => 'This store name is already in use. Please choose another name.']);
            }
            if ((clone $query)->where('slug', $request->validated('slug'))->exists()) {
                throw ValidationException::withMessages(['slug' => 'This store URL is already in use. Please choose another URL.']);
            }
            throw $exception;
        }
    }

    private function saveBankAccounts(StoreRequest $request, Store $store): void
    {
        $store->bankAccounts()->whereIn('id', $request->validated('deleted_bank_account_ids', []))->delete();
        if (! array_key_exists('bank_accounts', $request->validated())) {
            return;
        }
        foreach ($request->validated('bank_accounts', []) as $account) {
            $id = $account['id'] ?? null;
            unset($account['id'], $account['instructions']);
            if ($id) {
                $store->bankAccounts()->findOrFail($id)->update($account);
            } else {
                $store->bankAccounts()->create($account);
            }
        }
    }
}
