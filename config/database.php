<?php
// Database config reads from environment variables when available.
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'dbname' => getenv('DB_NAME') ?: 'quan_ly_chi_tieu',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: ''
];
