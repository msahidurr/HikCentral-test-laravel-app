<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'hikcentral' => [
        'base_url' => env('HIKCENTRAL_BASE_URL'),
        'app_key' => env('HIKCENTRAL_APP_KEY'),
        'app_secret' => env('HIKCENTRAL_APP_SECRET'),
        'timeout' => env('HIKCENTRAL_TIMEOUT', 30),
    
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

        'check_in_event_types' => array_filter(
            explode(',', env('HIKCENTRAL_CHECK_IN_EVENT_TYPES', '197149'))
        ),

        'check_out_event_types' => array_filter(
            explode(',', env('HIKCENTRAL_CHECK_OUT_EVENT_TYPES', '197150'))
        ),

        'door_index_codes' => array_filter(
            explode(',', env('HIKCENTRAL_DOOR_INDEX_CODES', '1,2'))
        ),

        'timezone' => env('HIKCENTRAL_TIMEZONE', 'Asia/Dhaka'),
    ],

];
