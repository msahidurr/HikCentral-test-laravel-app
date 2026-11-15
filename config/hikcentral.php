
<?php

return [
    'base_url' => env('HIKCENTRAL_BASE_URL'),
    'port' => env('HIKCENTRAL_PORT', 8208),

    'app_key' => env('HIKCENTRAL_APP_KEY'),
    'app_secret' => env('HIKCENTRAL_APP_SECRET'),

    'user_id' => env('HIKCENTRAL_USER_ID'),

    'timezone' => env(
        'HIKCENTRAL_TIMEZONE',
        'Asia/Dhaka'
    ),

    'door_codes' => array_filter(
        explode(',', env('HIKCENTRAL_DOOR_CODES', ''))
    ),

    'page_size' => (int) env(
        'HIKCENTRAL_PAGE_SIZE',
        100
    ),

    'event_type' => (int) env(
        'HIKCENTRAL_EVENT_TYPE',
        197151
    ),
];
