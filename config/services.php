<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Service
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT'),
    ],

    'mandrill' => [
        'secret' => env('MANDRILL_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'whmcs' => [
        'client_id' => env('WHMCS_CLIENT_ID', '!!! REPLACE THIS BY YOUR WHMCS CLIENT ID !!!'),
        'client_secret' => env('WHMCS_CLIENT_SECRET', '!!! REPLACE THIS BY YOUR WHMCS CLIENT SECRET !!!'),
        'url' => env('WHMCS_URL', 'https://whmcs.com'),
        'redirect' => env('WHMCS_REDIRECT', env('APP_URL') . '/auth/oauth/whmcs/callback'),
    ],
];
