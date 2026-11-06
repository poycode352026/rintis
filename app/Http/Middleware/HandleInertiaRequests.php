<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => ['user' => $request->user()?->only('name', 'email')],
            'navigation' => [
                'home' => route('home'),
                'account' => route('account'),
                'directory' => route('directory'),
                'admin' => route('admin.index'),
                'myOrders' => route('orders.history'),
                'pickup' => route('admin.pickup.entry'),
                'deliveries' => route('admin.deliveries.entry'),
                'openOrders' => route('admin.open-orders.entry'),
                'income' => route('admin.income.entry'),
                'notifications' => route('admin.notifications.index'),
                'readNotifications' => route('admin.notifications.read'),
                'login' => route('login'),
                'register' => route('register'),
                'logout' => route('logout'),
            ],
            'status' => fn () => $request->session()->get('status'),
        ];
    }
}
