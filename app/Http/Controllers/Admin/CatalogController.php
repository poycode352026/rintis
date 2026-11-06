<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogProductRequest;
use App\Models\CatalogCategory;
use App\Models\CatalogProduct;
use App\Models\OpenOrderItem;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CatalogController extends Controller
{
    public function index(Request $request, Store $store): Response
    {
        Gate::authorize('view', $store);
        $data = $request->validate([
            'tab' => ['nullable', Rule::in(['products', 'categories'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = $data['search'] ?? '';

        return Inertia::render('Admin/Catalog', [
            'store' => $store->only('name'),
            'settingsUrl' => route('admin.stores.edit', $store),
            'tab' => $data['tab'] ?? 'products', 'search' => $search,
            'products' => $store->catalogProducts()->with('catalogCategory')->withExists('sellingItems as is_locked')
                ->when($search !== '', fn (Builder $query) => $query->whereLike('name', '%'.$search.'%'))
                ->orderBy('name')->orderBy('id')->paginate(12)->withQueryString()
                ->through(fn (CatalogProduct $product): array => [
                    ...$product->only('id', 'name', 'price', 'is_visible'),
                    'category' => $product->catalogCategory->name,
                    'imageUrl' => $product->imageUrl(),
                    'isLocked' => (bool) $product->is_locked,
                    'editUrl' => $product->is_locked ? null : route('admin.catalog.edit', [$store, $product]),
                    'deleteUrl' => $product->is_locked ? null : route('admin.catalog.destroy', [$store, $product]),
                ]),
            'categories' => $store->catalogCategories()->withCount('products')->orderBy('name')->get()->map(fn (CatalogCategory $category): array => [
                ...$category->only('id', 'name', 'products_count'),
                'updateUrl' => route('admin.catalog.categories.update', [$store, $category]),
            ]),
            'catalogUrl' => route('admin.catalog.index', $store),
            'createUrl' => route('admin.catalog.create', $store),
            'categoryStoreUrl' => route('admin.catalog.categories.store', $store),
            'overviewUrl' => route('admin.stores.show', $store),
            'storefrontUrl' => route('storefront', $store),
        ]);
    }

    public function create(Store $store): Response
    {
        Gate::authorize('update', $store);

        return $this->form($store);
    }

    public function edit(Store $store, CatalogProduct $catalogProduct): Response|RedirectResponse
    {
        Gate::authorize('update', $store);

        if ($catalogProduct->sellingItems()->exists()) {
            return to_route('admin.catalog.index', $store)->withErrors(['product' => 'This product is locked while its open-order session is active or scheduled.']);
        }

        return $this->form($store, $catalogProduct);
    }

    private function form(Store $store, ?CatalogProduct $product = null): Response
    {
        return Inertia::render('Admin/ProductForm', [
            'store' => $store->only('name'),
            'settingsUrl' => route('admin.stores.edit', $store),
            'product' => $product ? [...$product->only('id', 'name', 'description', 'price', 'catalog_category_id', 'is_visible', 'costing', 'options'), 'imageUrl' => $product->imageUrl()] : null,
            'categories' => $store->catalogCategories()->orderBy('name')->get(['id', 'name']),
            'submitUrl' => $product ? route('admin.catalog.update', [$store, $product]) : route('admin.catalog.store', $store),
            'backUrl' => route('admin.catalog.index', $store),
            'deleteUrl' => $product ? route('admin.catalog.destroy', [$store, $product]) : null,
            'catalogUrl' => route('admin.catalog.index', $store),
            'categoriesUrl' => route('admin.catalog.index', [$store, 'tab' => 'categories']),
        ]);
    }

    public function store(CatalogProductRequest $request, Store $store): RedirectResponse
    {
        $this->persist($request, $store);

        return to_route('admin.catalog.index', $store)->with('status', 'Product added.');
    }

    public function update(CatalogProductRequest $request, Store $store, CatalogProduct $catalogProduct): RedirectResponse
    {
        $this->persist($request, $store, $catalogProduct);

        return to_route('admin.catalog.index', $store)->with('status', 'Product saved.');
    }

    public function destroy(Store $store, CatalogProduct $catalogProduct): RedirectResponse
    {
        Gate::authorize('update', $store);
        DB::transaction(function () use ($store, $catalogProduct): void {
            Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            $product = CatalogProduct::whereKey($catalogProduct->id)->lockForUpdate()->firstOrFail();
            if ($product->sellingItems()->exists()) {
                throw ValidationException::withMessages(['product' => 'This product is locked while its open-order session is active or scheduled.']);
            }
            $product->delete();
        });

        return to_route('admin.catalog.index', $store)->with('status', 'Product deleted.');
    }

    private function persist(CatalogProductRequest $request, Store $store, ?CatalogProduct $product = null): void
    {
        $data = $request->safe()->only(['name', 'description', 'price', 'catalog_category_id', 'is_visible', 'costing', 'options']);
        $oldImage = $product?->image_path;
        $newImage = null;
        if ($request->hasFile('photo')) {
            $newImage = $request->file('photo')->store('rintis/'.$store->id.'/products', 'public');
            if ($newImage === false) {
                throw ValidationException::withMessages(['photo' => 'The photo could not be saved. Please try again.']);
            }
            $data['image_path'] = $newImage;
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        try {
            DB::transaction(function () use ($store, $product, $data): void {
                Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
                if ($product) {
                    $product = CatalogProduct::whereKey($product->id)->lockForUpdate()->firstOrFail();
                    if ($product->sellingItems()->exists()) {
                        throw ValidationException::withMessages(['product' => 'This product is locked while its open-order session is active or scheduled.']);
                    }
                    $product->update($data);
                } else {
                    $store->catalogProducts()->create($data);
                }
                $store->completeSetupIfReady();
            });
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            throw $exception;
        }

        if ($oldImage && ($newImage || $request->boolean('remove_image')) && ! OpenOrderItem::query()->where('image_path', $oldImage)->exists()) {
            Storage::disk('public')->delete($oldImage);
        }
    }
}
