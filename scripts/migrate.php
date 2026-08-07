<?php
require_once __DIR__ . '/../src/Database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only be run from the command line.\n";
    exit(1);
}

try {
    Database::getConnection();
    Database::ensureSchema();
    echo "Schema migration completed successfully.\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, "Schema migration failed: " . $exception->getMessage() . "\n");
    exit(1);
}
