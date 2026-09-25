<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ToSend API Key
    |--------------------------------------------------------------------------
    |
    | Your ToSend API key. You can find this in your ToSend dashboard
    | under API Keys. The key should start with 'tsend_'.
    |
    */

    'api_key' => env('TOSEND_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | ToSend Admin API Key
    |--------------------------------------------------------------------------
    |
    | Optional. Only needed for the Admin API client (ToSendAdmin), which
    | manages domains, client teams, logs, webhooks and sending keys. Admin
    | keys start with 'tsend_admin_' and are created on your main account in
    | the dashboard under API Keys -> Admin API. Leave unset if you only send.
    |
    */

    'admin_key' => env('TOSEND_ADMIN_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the ToSend API. You should not need to change this
    | unless you are using a custom endpoint for testing.
    |
    */

    'api_url' => env('TOSEND_API_URL', 'https://api.tosend.com'),

    /*
    |--------------------------------------------------------------------------
    | Default From Address
    |--------------------------------------------------------------------------
    |
    | This address will be used as the default sender when no from address
    | is specified. The domain must be verified in your ToSend account.
    |
    */

    'from' => [
        'address' => env('TOSEND_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'name' => env('TOSEND_FROM_NAME', env('MAIL_FROM_NAME')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum number of seconds to wait for a response from the API.
    |
    */

    'timeout' => env('TOSEND_TIMEOUT', 30),

];
