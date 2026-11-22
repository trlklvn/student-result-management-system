<?php
/**
 * Application configuration.
 * Values can be overridden with environment variables (useful for tests and deployment).
 * Default driver is MySQL. Set DB_DRIVER=sqlite to run without a MySQL server.
 */
return [
    'app_name' => 'Student Result Management System',
    'db' => [
        'driver'   => getenv('DB_DRIVER') ?: 'mysql',
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'srms',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'sqlite_path' => getenv('DB_SQLITE_PATH') ?: __DIR__ . '/../database/srms.sqlite',
    ],
    // Score limits used for validation
    'max_ca'   => 40,
    'max_exam' => 60,
];
