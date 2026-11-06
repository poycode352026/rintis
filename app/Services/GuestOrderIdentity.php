<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GuestOrderIdentity
{
    public const COOKIE = 'rintis_order_history';

    public function key(Request $request): string
    {
        $key = $request->attributes->get(self::COOKIE)
            ?? $request->cookie(self::COOKIE)
            ?? $request->session()->get('rintis.guest_key');
        if (! is_string($key) || ! preg_match('/^[A-Za-z0-9]{64}$/', $key)) {
            $key = Str::random(64);
        }
        $request->attributes->set(self::COOKIE, $key);
        $request->session()->put('rintis.guest_key', $key);

        return $key;
    }

    public function remember(Request $request, Response $response): Response
    {
        // Laravel's EncryptCookies middleware encrypts this HttpOnly cookie.
        $response->headers->setCookie(cookie(self::COOKIE, $this->key($request), 60 * 24 * 30, '/', null, $request->isSecure(), true, false, 'lax'));

        return $response;
    }
}
