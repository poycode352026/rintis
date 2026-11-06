<?php

return [
    'seed_account' => [
        'name' => 'OCS',
        'email' => 'oneclickstore@gmail.com',
        'password' => env('RINTIS_SEED_ACCOUNT_PASSWORD', env('ONECLICK_SEED_OWNER_PASSWORD')),
    ],
    'routing_demo' => (bool) env('RINTIS_ROUTING_DEMO', false),
    'routing_url' => env('RINTIS_ROUTING_URL'),
    'timezone' => 'Asia/Jakarta',
    'regions_url' => env('RINTIS_REGIONS_URL', 'https://www.emsifa.com/api-wilayah-indonesia/v2'),
    'map_tiles_url' => env('RINTIS_MAP_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
];
