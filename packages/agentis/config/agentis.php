<?php

return [

    'provider' => env('AGENTIS_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Tables exposed to the AI
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'users' => [
            'searchable' => ['id', 'name', 'email', 'created_at'],
            'label'      => 'Registered users',
        ],
        'products' => [
            'searchable' => ['id', 'name', 'sku', 'price', 'stock', 'description', 'user_id', 'created_at'],
            'label'      => 'Products catalog',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Relationships — tells AI how tables connect
    |--------------------------------------------------------------------------
    */
    'relationships' => [
        'products.user_id → users.id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query limits — safety + performance
    |--------------------------------------------------------------------------
    */
    'max_rows'    => 100,   // AI will never return more than this
    'cache_ttl'   => 60,    // seconds — cache identical queries
];
