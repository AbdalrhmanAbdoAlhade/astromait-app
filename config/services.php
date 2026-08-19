<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
// config/services.php — أضف
'adfpay' => [
    'api_key'     => env('ADFPAY_API_KEY'),
    'merchant_id' => env('ADFPAY_MERCHANT_ID'),
    'base_url'    => env('ADFPAY_BASE_URL', 'https://api.adfpay.com'),
    'sandbox'     => env('ADFPAY_SANDBOX', false),
],
'dhl' => [
    'api_key'   => env('DHL_API_KEY'),
    'site_id'   => env('DHL_SITE_ID'),
    'password'  => env('DHL_PASSWORD'),
    'base_url'  => env('DHL_BASE_URL', 'https://xmlpi-ea.dhl.com/XMLShippingServlet'),
],
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
