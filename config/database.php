<?php
return [
    'host'    => env('DB_HOST', '127.0.0.1'),
    'port'    => env('DB_PORT', '5432'),
    'name'    => env('DB_NAME', 'loan_database'),
    'user'    => env('DB_USER', 'postgres'),
    'pass'    => env('DB_PASS', 'postgres'),
    // 'require' for managed Postgres (Neon, Render, etc.), 'prefer' for
    // local Postgres that has no SSL set up.
    'sslmode' => env('DB_SSLMODE', 'prefer'),
];
