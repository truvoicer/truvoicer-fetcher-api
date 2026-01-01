<?php

// config for Truvoicer/TruFetcherCore
return [
    'api' => [
        'api_url' => env('FETCHER_API_URL'),
        'api_key' => env('FETCHER_API_KEY'),
    ],
    'database' => [
        'connection' => 'fetcher_mysql',

        'connections' => [

            'tf_mysql' => [
                'driver' => 'mysql',
                'host' => env('TF_DB_HOST', '127.0.0.1'),
                'port' => env('TF_DB_PORT', '3306'),
                'database' => env('TF_DB_DATABASE', 'fetcher'),
                'username' => env('TF_DB_USERNAME', 'root'),
                'password' => env('TF_DB_PASSWORD', ''),
                'unix_socket' => env('TF_DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ],

            'tf_mongodb' => [
                'driver' => 'mongodb',
                'dsn' => env('TF_MONGODB_URI', ''),
                'database' => env('TF_MONGODB_DATABASE', 'fetcher_data'),
            ],
        ],
    ],
];
