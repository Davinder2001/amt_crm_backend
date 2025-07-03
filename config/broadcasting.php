<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default broadcaster that should be used by your
    | application. Laravel supports several broadcast drivers, including
    | Pusher, Redis, and a simple log driver. You may even set up your own
    | custom driver. Just set the name of the driver below.
    |
    */

    'default' => env('BROADCAST_DRIVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | All of the broadcast connections configured for your application are
    | listed here. You can configure each connection to your preferred
    | service (Pusher, Redis, etc.) and adjust any options necessary.
    |
    */

    'connections' => [

        //    'pusher' => [
        //        'driver' => 'pusher',
        //        'key' => env('PUSHER_APP_KEY'),
        //        'secret' => env('PUSHER_APP_SECRET'),
        //        'app_id' => env('PUSHER_APP_ID'),
        //        'options' => [
        //            'cluster' => env('PUSHER_APP_CLUSTER'),
        //            'useTLS' => true,
        //        ],
        //        'host' => env('PUSHER_HOST', 'api.pusherapp.com'),
        //        'port' => env('PUSHER_PORT', 443),
        //        'scheme' => env('PUSHER_SCHEME', 'https'),
        //        'encrypted' => true,
        //    ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'array' => [
            'driver' => 'array',
        ],
    ],

];
