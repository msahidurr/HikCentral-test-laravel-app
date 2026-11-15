
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

    // Access Denied by Face and Fingerprint: 197141 door
    // Access Timed Out by Face and Fingerprint: 197142 door
    // Access Denied by Face and PIN: 197143 door
    // Access Timed Out by Face and PIN: 197144 door
    // Access Denied by Face and Card: 197145 door
    // Access Timed Out by Face and Card: 197146 door
    // Access Denied by Face, PIN, and Fingerprint:  197147 door
    // Access Timed Out by Face, PIN, and Fingerprint: 197148 door
    // Access Denied by Face, Card, and Fingerprint: 197149 door
    // Access Timed Out by Face, Card, and Fingerprint: 197150 door
    // Access Denied by Face: 197151 door
    // Access Denied by Employee ID and PIN: 197158 door
    // Face Recognition Failed: 197160 door
    // Live Face Detection Failed.: 197161 door
    // Access Denied by Card and PIN: 197382 door

    'event_type' => (int) env(
        'HIKCENTRAL_EVENT_TYPE',
        197149
    ),
];
