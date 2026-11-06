<?php

use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\MidtransController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request): RedirectResponse {
    return $request->user() ? to_route('admin.index') : to_route('directory');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('login.store');
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
Route::get('/account', fn (): RedirectResponse => to_route('admin.index'))->middleware('auth')->name('account');

require __DIR__.'/store.php';

// Preserve previously shared customer links while using standalone URLs.
Route::get('/rintis/{path?}', function (Request $request, ?string $path = null): RedirectResponse {
    $query = $request->getQueryString();

    return redirect('/'.($path ?? '').($query ? '?'.$query : ''), 301);
})->where('path', '.*');
// Existing Midtrans notification URLs remain valid during migration.
Route::post('/rintis/payments/midtrans/notification', [MidtransController::class, 'webhook'])
    ->middleware('throttle:60,1,rintis-payment-webhook:');
