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
