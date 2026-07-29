<?php

class Logger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $logDir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $record = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $line = json_encode($record, JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            $line = date('c') . ' ' . $level . ' ' . $message;
        }

        @file_put_contents($logDir . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
