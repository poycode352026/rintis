<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreAvailabilityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);
        $store = $request->user()->store;
        $query = Store::query()->when($store, fn ($query) => $query->whereKeyNot($store->id));

        return response()->json([
            'nameAvailable' => ! (clone $query)->where('name_key', Store::nameKey($data['name']))->exists(),
            'slugAvailable' => ! (clone $query)->where('slug', Str::slug($data['slug']))->exists(),
        ]);
    }
}
