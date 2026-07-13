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

    'orange_money' => [
        'client_id' => env('ORANGE_MONEY_CLIENT_ID', ''),
        'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET', ''),
        'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY', ''),
        'base_url' => env('ORANGE_MONEY_BASE_URL', 'https://api.orange.com'),
        'country' => env('ORANGE_MONEY_COUNTRY', 'GN'),
        'currency' => env('ORANGE_MONEY_CURRENCY', 'GNF'),
        'notif_url' => env('ORANGE_MONEY_NOTIF_URL'),
        'return_url' => env('ORANGE_MONEY_RETURN_URL'),
        'cancel_url' => env('ORANGE_MONEY_CANCEL_URL'),
        'use_mock' => env('ORANGE_MONEY_USE_MOCK', false),
    ],

];
