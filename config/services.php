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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'connectwise' => [
        'base_url' => env('CONNECTWISE_BASE_URL', 'https://api-na.myconnectwise.net/v4_6_release/apis/3.0'),
        'company_id' => env('CONNECTWISE_COMPANY_ID'),
        'public_key' => env('CONNECTWISE_PUBLIC_KEY'),
        'private_key' => env('CONNECTWISE_PRIVATE_KEY'),
        'client_id' => env('CONNECTWISE_CLIENT_ID'),
        'sim_agreement_type_ids' => array_filter(explode(',', env('CONNECTWISE_SIM_AGREEMENT_TYPE_IDS', ''))),
    ],

    'gocardless' => [
        'access_token' => env('GOCARDLESS_ACCESS_TOKEN'),
        'environment' => env('GOCARDLESS_ENVIRONMENT', 'sandbox'),
        'webhook_secret' => env('GOCARDLESS_WEBHOOK_SECRET'),
    ],

    'mobilemanager' => [
        'base_url' => env('MOBILEMANAGER_BASE_URL', 'https://developers.mobilemanager.co.uk'),
        'api_key' => env('MOBILEMANAGER_API_KEY'),
        'api_secret' => env('MOBILEMANAGER_API_SECRET'),
    ],

    'microsoft365' => [
        'tenant_id' => env('MICROSOFT365_TENANT_ID'),
        'client_id' => env('MICROSOFT365_CLIENT_ID'),
        'client_secret' => env('MICROSOFT365_CLIENT_SECRET'),
        'sender_email' => env('MICROSOFT365_SENDER_EMAIL'),
    ],

];
