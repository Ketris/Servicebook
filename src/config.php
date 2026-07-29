<?php
// Database configuration for local installation. Update these values for your environment.
$appTimezone = 'America/Chicago';
date_default_timezone_set($appTimezone);

return [
    'db_host' => '127.0.0.1',
    'db_name' => 'servicebook',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',
    'app_timezone' => $appTimezone,
];
