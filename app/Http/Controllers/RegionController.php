<?php

namespace App\Http\Controllers;

use App\Services\RegionDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class RegionController extends Controller
{
    public function __invoke(Request $request, RegionDirectory $directory): JsonResponse
    {
        $data = $request->validate([
            'level' => ['required', Rule::in(['provinces', 'cities', 'districts'])],
            'parent' => ['required_unless:level,provinces', 'nullable', 'string', 'regex:/^[0-9]{2}(?:\.[0-9]{2})?$/'],
        ]);
        if ($data['level'] === 'cities') {
            abort_unless(preg_match('/^[0-9]{2}$/', $data['parent']), 422);
        }
        if ($data['level'] === 'districts') {
            abort_unless(preg_match('/^[0-9]{2}\.[0-9]{2}$/', $data['parent']), 422);
        }
        try {
            return response()->json(['data' => $directory->list($data['level'], $data['parent'] ?? null)]);
        } catch (Throwable) {
            return response()->json(['message' => 'Region options could not be loaded. Please try again.'], 503);
        }
    }
}
