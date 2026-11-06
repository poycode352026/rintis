<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WeeklyScheduleRequest;
use App\Models\Store;
use App\Models\WeeklySchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyScheduleController extends Controller
{
    public function edit(Store $store): Response
    {
        Gate::authorize('update', $store);
        $schedule = WeeklySchedule::where('store_id', $store->id)->first();

        return Inertia::render('Admin/OpenOrders/WeeklySchedule', ['catalogUrl' => route('admin.catalog.index', $store), 'settingsUrl' => route('admin.stores.edit', $store), 'schedule' => $schedule?->only('enabled', 'days', 'product_ids', 'last_error', 'skip_date'), 'products' => $store->catalogProducts()->orderBy('name')->get(['id', 'name']), 'submitUrl' => route('admin.weekly.update', $store), 'skipUrl' => route('admin.weekly.skip', $store), 'backUrl' => route('admin.open-orders.index', $store)]);
    }

    public function update(WeeklyScheduleRequest $request, Store $store): RedirectResponse
    {
        WeeklySchedule::updateOrCreate(['store_id' => $store->id], [...$request->validated(), 'product_ids' => [], 'last_error' => null]);

        return back()->with('status', 'Weekly schedule saved.');
    }

    public function skip(Store $store): RedirectResponse
    {
        Gate::authorize('update', $store);
        WeeklySchedule::where('store_id', $store->id)->update(['skip_date' => now(config('rintis.timezone'))->toDateString()]);

        return back()->with('status', 'Automatic opening skipped today. Stop any session already open from Open orders.');
    }
}
