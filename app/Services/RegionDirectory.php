<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RegionDirectory
{
    public function list(string $level, ?string $parent = null): array
    {
        $path = match ($level) {
            'provinces' => 'provinces.json',
            'cities' => 'regencies/'.$parent.'.json',
            'districts' => 'districts/'.$parent.'.json',
            default => throw new RuntimeException('Unknown region level.'),
        };

        return Cache::remember('rintis.regions.v2.'.$path, now()->addDay(), function () use ($path): array {
            $response = Http::acceptJson()->connectTimeout(4)->timeout(10)
                ->get(rtrim(config('rintis.regions_url'), '/').'/'.$path)->throw();
            $rows = $response->json('data');
            if (! is_array($rows) || ! array_is_list($rows) || $rows === []) {
                throw new RuntimeException('Region data is unavailable.');
            }

            return collect($rows)->filter(fn ($row) => is_array($row) && isset($row['id'], $row['name']))
                ->map(fn (array $row): array => [
                    'id' => (string) $row['id'], 'name' => (string) $row['name'],
                    'lat' => isset($row['lat']) && is_numeric($row['lat']) ? (float) $row['lat'] : null,
                    'lng' => isset($row['lng']) && is_numeric($row['lng']) ? (float) $row['lng'] : null,
                ])->values()->all();
        });
    }
}
