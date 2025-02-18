<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This option defines the settings for handling cross-origin requests.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],  // Allow API routes and CSRF cookies

    'allowed_methods' => ['*'],  // Allow all HTTP methods

    'allowed_origins' => ['*'],  // Allow frontend URL

    'allowed_origins_patterns' => ['*'],  // Allow any origin pattern

    'allowed_headers' => ['*'],  // Allow all headers

    'exposed_headers' => ['*'],  // Allow all headers to be exposed

    'max_age' => 0,

    'supports_credentials' => true,  // Set to true if you need to send cookies

];
