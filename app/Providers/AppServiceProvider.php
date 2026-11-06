<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('rintis-delivery-plan', function (Request $request) {
            return Limit::perMinute(10)->by((string) $request->user()?->id)->response(function (Request $request, array $headers) {
                $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));
                $message = "Please wait {$seconds} seconds before planning another delivery route. Your orders are unchanged.";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message, 'errors' => ['orders' => [$message]]], 429, $headers);
                }

                return to_route('admin.deliveries.index', $request->route('store'), 303)->withErrors(['orders' => $message])->withHeaders($headers);
            });
        });
    }
}
