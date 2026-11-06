<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogCategory;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CatalogCategoryController extends Controller
{
    public function store(Request $request, Store $store): RedirectResponse
    {
        Gate::authorize('update', $store);
        $store->catalogCategories()->create($request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('rintis_catalog_categories', 'name')->where('store_id', $store->id)],
        ]));

        return to_route('admin.catalog.index', [$store, 'tab' => 'categories'])->with('status', 'Product category added.');
    }

    public function update(Request $request, Store $store, CatalogCategory $catalogCategory): RedirectResponse
    {
        Gate::authorize('update', $store);
        $catalogCategory->update($request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('rintis_catalog_categories', 'name')->where('store_id', $store->id)->ignore($catalogCategory)],
        ]));

        return to_route('admin.catalog.index', [$store, 'tab' => 'categories'])->with('status', 'Product category saved.');
    }
}
