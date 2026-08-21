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

    'deepl' => [
        // Keep SSL verification enabled in production. Set DEEPL_VERIFY_SSL=false only
        // as a temporary local workaround when PHP/cURL has no CA bundle configured.
        'verify_ssl' => env('DEEPL_VERIFY_SSL', true),
    ],

    'microsoft_translator' => [
        'verify_ssl' => env('MICROSOFT_TRANSLATOR_VERIFY_SSL', true),
    ],

    'viva' => [
        'environment' => env('VIVA_ENV', 'demo'),
        'client_id' => env('VIVA_CLIENT_ID'),
        'client_secret' => env('VIVA_CLIENT_SECRET'),
        'merchant_id' => env('VIVA_MERCHANT_ID'),
        'api_key' => env('VIVA_API_KEY'),
        'webhook_verification_key' => env('VIVA_WEBHOOK_VERIFICATION_KEY'),
        'source_code' => env('VIVA_SOURCE_CODE'),
        'currency' => env('VIVA_CURRENCY', 'EUR'),
        'country_code' => env('VIVA_COUNTRY_CODE', 'GR'),
        'request_lang' => env('VIVA_REQUEST_LANG', 'el-GR'),
    ],

    'geoapify' => [
        'key' => env('GEOAPIFY_API_KEY'),
        'country_code' => env('GEOAPIFY_COUNTRY_CODE', 'gr'),
        'routing_mode' => env('GEOAPIFY_ROUTING_MODE', 'drive'),
    ],

];
