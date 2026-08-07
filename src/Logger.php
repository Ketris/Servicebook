<?php

class Logger
{
    private const LOG_RETENTION_DAYS = 60;
    private static bool $retentionPruned = false;

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

        if (is_dir($logDir)) {
            self::pruneOldLogs($logDir);
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

        @file_put_contents(self::dailyLogPath($logDir), $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function dailyLogPath(string $logDir): string
    {
        return $logDir . '/app-' . date('Y-m-d') . '.log';
    }

    private static function pruneOldLogs(string $logDir): void
    {
        if (self::$retentionPruned) {
            return;
        }
        self::$retentionPruned = true;

        $cutoffDate = (new DateTimeImmutable('today'))->modify('-' . self::LOG_RETENTION_DAYS . ' days');
        $files = glob($logDir . '/app-*.log');
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $filePath) {
            $baseName = basename($filePath);
            if (!preg_match('/^app-(\d{4}-\d{2}-\d{2})\.log$/', $baseName, $matches)) {
                continue;
            }

            try {
                $logDate = new DateTimeImmutable($matches[1]);
            } catch (Exception $exception) {
                continue;
            }

            if ($logDate < $cutoffDate) {
                @unlink($filePath);
            }
        }
    }
}
