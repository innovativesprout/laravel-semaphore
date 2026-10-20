<?php

return [

    /*
    | Your Semaphore API key: https://semaphore.co/account/api
    */
    'api_key' => env('SEMAPHORE_API_KEY'),

    /*
    | Optional default sender name. When null, Semaphore uses your account's
    | default registered sender name.
    */
    'sender_name' => env('SEMAPHORE_SENDER_NAME'),

    'base_url' => env('SEMAPHORE_BASE_URL', 'https://api.semaphore.co/api/v4'),

    // Request timeout in seconds.
    'timeout' => (int) env('SEMAPHORE_TIMEOUT', 10),

    'retry' => [
        /*
        | Extra attempts for send, priority and OTP calls on connection errors
        | and 5xx responses. Off by default: if a send times out after Semaphore
        | already accepted it, a retry sends a DUPLICATE SMS and charges twice.
        */
        'sends' => (int) env('SEMAPHORE_RETRY_SENDS', 0),

        // Extra attempts for read endpoints (safe: they change nothing).
        'reads' => 2,

        // Milliseconds to wait between attempts.
        'sleep_ms' => 500,
    ],

];
