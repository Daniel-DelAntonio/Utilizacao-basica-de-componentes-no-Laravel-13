<?php

use App\Models\User;

return [
    'presets' => [
        'usuarios' => [
            'model' => User::class,
            'columns' => ['id', 'name', 'login', 'email', 'function_name', 'system_unit_id', 'created_at'],
            'default_columns' => ['id', 'name', 'login', 'function_name'],
            'search_columns' => ['name', 'login', 'email'],
            'filters' => [],
            'date_columns' => ['created_at', 'updated_at'],
            'order_columns' => ['id', 'name', 'created_at'],
            'date_column' => 'created_at',
            'order_by' => 'name',
            'view' => 'exports.generic',
            'filename' => 'usuarios',
            'ability' => null,
        ],
    ],
];
