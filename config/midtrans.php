<?php

return [
    // Initial integration is sandbox-only; live payments are not connected yet.
    'is_production' => false,
    'store_slug' => 'wemeet-coffee',
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
];
