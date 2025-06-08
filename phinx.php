<?php

return
[
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations', // Corrected path
        'seeds' => __DIR__ . '/db/seeds'         // Corrected path
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        // Production environment can be set up similarly if needed
        'production' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1', // Use general DB vars or specific prod vars
            'name' => getenv('DB_NAME') ?: 'condo_prod_db',
            'user' => getenv('DB_USER') ?: 'prod_user',
            'pass' => getenv('DB_PASS') ?: '',
            'port' => getenv('DB_PORT') ?: 5432,
            'charset' => 'utf8',
        ],
        'development' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST_DEV') ?: (getenv('DB_HOST_TEST') ?: '127.0.0.1'), // Fallback for dev/test
            'name' => getenv('DB_NAME_DEV') ?: (getenv('DB_NAME_TEST') ?: 'condo_management_dev'), // Default dev DB name
            'user' => getenv('DB_USER_DEV') ?: (getenv('DB_USER_TEST') ?: 'dev_user'),
            'pass' => getenv('DB_PASS_DEV') ?: (getenv('DB_PASS_TEST') ?: 'dev_pass'),
            'port' => getenv('DB_PORT_DEV') ?: (getenv('DB_PORT_TEST') ?: 5432),
            'charset' => 'utf8',
        ],
        // Testing environment for Phinx itself, separate from PHPUnit test DB if needed
        // Or, PHPUnit tests could run migrations against their own test DB.
        // For now, PHPUnit uses DatabaseTestCaseHelper which has its own DB setup logic.
        'testing' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST_TEST') ?: '127.0.0.1',
            'name' => getenv('DB_NAME_TEST') ?: 'test_condo_management', // Align with DatabaseTestCaseHelper
            'user' => getenv('DB_USER_TEST') ?: 'testuser',
            'pass' => getenv('DB_PASS_TEST') ?: 'testpass',
            'port' => getenv('DB_PORT_TEST') ?: 5432,
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];
