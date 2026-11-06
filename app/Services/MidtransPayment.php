<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class MidtransPayment
{
    public function available(Store $store): bool
    {
        return ! config('midtrans.is_production') && app()->environment(['local', 'testing'])
            && filled(config('midtrans.server_key')) && filled(config('midtrans.merchant_id'))
            && $store->slug === config('midtrans.store_slug');
    }

    private function client(): PendingRequest
    {
        abort_unless(! config('midtrans.is_production') && app()->environment(['local', 'testing']), 503);

        return Http::baseUrl('https://api.sandbox.midtrans.com/v2')->withBasicAuth(config('midtrans.server_key'), '')
            ->acceptJson()->asJson()->connectTimeout(3)->timeout(8);
    }

    // The persisted provider order ID is reused after timeouts, never replaced.
    public function sync(Order $order, bool $create = false, bool $force = false): bool
    {
        if (($order->payment_details['provider'] ?? null) !== 'midtrans' || ! $this->available($order->store)) {
            return false;
        }
        try {
            return DB::transaction(function () use ($order, $create, $force): bool {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $details = $locked->payment_details;
                if ($locked->payment_status === 'paid') {
                    return true;
                }
                if (! $create && ! $force && ($details['checked_at'] ?? 0) > now()->timestamp - 15) {
                    return true;
                }
                $response = $this->client()->get('/'.rawurlencode($details['provider_order_id']).'/status');
                if ($create && $locked->status !== 'expired' && ($response->status() === 404 || (string) $response->json('status_code') === '404')) {
                    $type = $details['payment_type'] ?? 'bank_transfer';
                    $deadline = $details['expires_at'] ?? null;
                    $seconds = $deadline ? (int) floor(now()->diffInSeconds(CarbonImmutable::parse($deadline), false)) : 86400;
                    if ($seconds <= 0) {
                        $details['status'] = 'expire';
                        $locked->payment_details = $details;
                        $locked->save();

                        return false;
                    }
                    $response = $this->client()->post('/charge', [
                        'payment_type' => $type,
                        'transaction_details' => ['order_id' => $details['provider_order_id'], 'gross_amount' => $locked->total],
                        ...($type === 'qris' ? ['qris' => ['acquirer' => 'gopay']] : ['bank_transfer' => ['bank' => $details['bank']]]),
                        'custom_expiry' => ['order_time' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s O'), 'expiry_duration' => min($seconds, 604800), 'unit' => 'second'],
                    ]);
                }
                $code = (string) $response->json('status_code');
                if (! $response->successful() || ! in_array($code, ['200', '201'], true)) {
                    if ($create || $code !== '404') {
                        $this->recordFailure($locked, (in_array($code, ['401', '403'], true) || in_array($response->status(), [401, 403], true)) ? 'credentials' : 'provider', $code, $response->status());
                    }

                    return false;
                }
                $data = $response->json();
                // Authenticate through the server-to-server status API, not browser/webhook claims.
                if (($data['order_id'] ?? null) !== $details['provider_order_id']
                    || ($data['merchant_id'] ?? null) !== config('midtrans.merchant_id')
                    || ($data['currency'] ?? null) !== 'IDR'
                    || ! preg_match('/^'.preg_quote((string) $locked->total, '/').'(?:\.00)?$/', (string) ($data['gross_amount'] ?? ''))
                    || ($data['payment_type'] ?? null) !== ($details['payment_type'] ?? 'bank_transfer')) {
                    $this->recordFailure($locked, 'response_mismatch', $code);

                    return false;
                }
                unset($details['last_error']);
                $details['checked_at'] = now()->timestamp;
                $details['status'] = $data['transaction_status'] ?? 'pending';
                $va = $data['va_numbers'][0] ?? null;
                if (is_array($va) && ($va['bank'] ?? '') === ($details['bank'] ?? null) && preg_match('/^[0-9]{5,30}$/', (string) ($va['va_number'] ?? ''))) {
                    $details['account_number'] = $va['va_number'];
                }
                if (($details['payment_type'] ?? '') === 'qris') {
                    foreach ($data['actions'] ?? [] as $action) {
                        $url = $action['url'] ?? '';
                        if (in_array($action['name'] ?? '', ['generate-qr-code', 'generate-qr-code-v2'], true)
                            && preg_match('~^https://api\.sandbox\.midtrans\.com/v[24]/qris/[a-zA-Z0-9-]+/qr-code$~D', $url)) {
                            $details['qr_url'] = $url;
                        }
                    }
                }
                if (! empty($data['expiry_time'])) {
                    $providerExpiry = CarbonImmutable::parse($data['expiry_time'], 'Asia/Jakarta');
                    $deadline = isset($details['expires_at']) ? CarbonImmutable::parse($details['expires_at'], 'Asia/Jakarta') : null;
                    $details['expires_at'] = ($deadline && $deadline->lt($providerExpiry) ? $deadline : $providerExpiry)->toIso8601String();
                }
                if ($details['status'] === 'settlement' && ($data['fraud_status'] ?? 'accept') === 'accept') {
                    $locked->payment_status = 'paid';
                    if ($locked->status === 'expired') {
                        // Provider confirmation wins over a delayed local expiry check.
                        $locked->status = 'received';
                        $history = $locked->fulfillment_history ?? [];
                        $history[] = ['status' => 'received', 'at' => now()->toIso8601String(), 'actor_id' => null];
                        $locked->fulfillment_history = $history;
                        unset($details['expired_at']);
                    }
                    $details['paid_at'] = now()->toIso8601String();
                }
                $locked->payment_details = $details;
                $locked->save();

                return true;
            });
        } catch (Throwable $exception) {
            $kind = $exception instanceof ConnectionException ? 'connection' : ($exception instanceof RequestException ? 'provider' : 'internal');
            $this->recordFailure($order, $kind, '', $exception instanceof RequestException ? $exception->response->status() : null);

            return false;
        }
    }

    private function recordFailure(Order $order, string $kind, string $code = '', ?int $httpStatus = null): void
    {
        DB::transaction(function () use ($order, $kind, $code, $httpStatus): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_status === 'paid') {
                return;
            }
            $details = $locked->payment_details;
            // Store only fixed categories and numeric status codes, never provider payloads or exception messages.
            $details['last_error'] = ['kind' => $kind, 'code' => preg_match('/^[0-9]{3}$/', $code) ? $code : null, 'http_status' => $httpStatus, 'at' => now()->toIso8601String()];
            $locked->payment_details = $details;
            $locked->save();
        });
    }
}
