<?php

return [

    'provider' => env('AGENTIS_PROVIDER', 'gemini'),
    'max_rows' => 100,
    'cache_ttl' => 60,
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
        'posts' => [
            'searchable' => ['id', 'title', 'content', 'user_id', 'created_at'],
            'label'      => 'Posts catalog',
        ],
        'comments' => [
            'searchable' => ['id', 'body', 'user_id', 'created_at'],
            'label'      => 'Comments catalog',
        ],
        'profiles' => [
            'searchable' => ['id', 'user_id', 'bio', 'avatar_url', 'created_at'],
            'label'      => 'Profiles catalog',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Relationships — tells AI how tables connect
    |--------------------------------------------------------------------------
    */
    'relationships' => [
        'products.user_id → users.id',
        'posts.user_id → users.id',
        'comments.user_id → users.id',
        'profiles.user_id → users.id',
    ],
];
