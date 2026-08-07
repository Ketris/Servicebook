<?php
require_once __DIR__ . '/AppSettings.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';

class BackupManager
{
    private const BACKUP_DIR_RELATIVE = 'storage/backups';
    private const SCHEDULE_LOCK_FILENAME = '.backup-schedule.lock';
    private const UPLOAD_MAX_BYTES = 209715200; // 200 MB
    private static bool $scheduleChecked = false;

    public static function runScheduledIfDue(): void
    {
        if (self::$scheduleChecked) {
            return;
        }
        self::$scheduleChecked = true;

        try {
            $settings = AppSettings::all();
            $enabled = (string)($settings['backup_auto_enabled'] ?? '0') === '1';
            if (!$enabled) {
                return;
            }

            $cadence = self::normalizeCadence((string)($settings['backup_cadence'] ?? 'daily'));
            $retentionDays = self::normalizeRetentionDays((string)($settings['backup_retention_days'] ?? '60'));
            $lastRunAt = trim((string)($settings['backup_last_run_at'] ?? ''));
            if (!self::isBackupDue($lastRunAt, $cadence)) {
                return;
            }

            $lockHandle = self::acquireScheduleLock();
            if ($lockHandle === null) {
                return;
            }

            try {
                self::createBackup('auto', $retentionDays);
                self::setLastRunAt(date('Y-m-d H:i:s'));
            } finally {
                self::releaseScheduleLock($lockHandle);
            }
        } catch (Throwable $exception) {
            Logger::error('Automatic backup failed', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    public static function createBackup(string $trigger = 'manual', ?int $retentionDays = null): array
    {
        if (!function_exists('gzencode')) {
            throw new RuntimeException('Server does not support gzip compression (gzencode missing).');
        }

        $backupDir = self::ensureBackupDirectory();
        $safeTrigger = preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower($trigger)) ?: 'manual';
        $fileName = 'backup-' . date('Ymd-His') . '-' . $safeTrigger . '.json.gz';
        $filePath = $backupDir . '/' . $fileName;

        $snapshot = self::buildSnapshot($safeTrigger);
        $json = json_encode($snapshot, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Failed to encode backup payload as JSON.');
        }

        $compressed = gzencode($json, 9);
        if ($compressed === false) {
            throw new RuntimeException('Failed to compress backup payload.');
        }

        if (@file_put_contents($filePath, $compressed, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write backup file.');
        }

        self::pruneByRetentionDays($backupDir, $retentionDays ?? self::normalizeRetentionDays(AppSettings::get('backup_retention_days')));

        Logger::info('Backup created', [
            'filename' => $fileName,
            'trigger' => $safeTrigger,
            'size_bytes' => filesize($filePath) ?: 0,
        ]);

        return [
            'filename' => $fileName,
            'path' => $filePath,
            'size_bytes' => filesize($filePath) ?: 0,
            'created_at' => date('Y-m-d H:i:s'),
            'trigger' => $safeTrigger,
        ];
    }

    public static function listBackups(): array
    {
        $backupDir = self::ensureBackupDirectory();
        $files = glob($backupDir . '/backup-*.json.gz');
        if (!is_array($files)) {
            return [];
        }

        usort($files, static function (string $a, string $b): int {
            return (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0);
        });

        $results = [];
        foreach ($files as $filePath) {
            $name = basename($filePath);
            $results[] = [
                'name' => $name,
                'size_bytes' => filesize($filePath) ?: 0,
                'modified_at' => date('Y-m-d H:i:s', filemtime($filePath) ?: time()),
            ];
        }

        return $results;
    }

    public static function streamBackupDownload(string $fileName): void
    {
        $filePath = self::resolveBackupPath($fileName);
        if (!is_file($filePath)) {
            throw new InvalidArgumentException('Selected backup file was not found.');
        }

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . (string)(filesize($filePath) ?: 0));
        readfile($filePath);
    }

    public static function restoreFromStoredBackup(string $fileName): array
    {
        $filePath = self::resolveBackupPath($fileName);
        if (!is_file($filePath)) {
            throw new InvalidArgumentException('Selected backup file was not found.');
        }

        $snapshot = self::loadSnapshotFromFile($filePath);
        $result = self::restoreSnapshot($snapshot);

        Logger::warning('Backup restored from stored file', [
            'filename' => basename($filePath),
            'tables_restored' => $result['tables_restored'],
            'rows_restored' => $result['rows_restored'],
        ]);

        return $result;
    }

    public static function restoreFromUploadedBackup(string $tmpPath, string $originalName): array
    {
        if (!is_file($tmpPath)) {
            throw new InvalidArgumentException('Uploaded backup file was not found.');
        }

        $backupDir = self::ensureBackupDirectory();
        $storedName = 'backup-' . date('Ymd-His') . '-upload.json.gz';
        $storedPath = $backupDir . '/' . $storedName;

        if (!@copy($tmpPath, $storedPath)) {
            throw new RuntimeException('Unable to store uploaded backup file.');
        }

        try {
            $snapshot = self::loadSnapshotFromFile($storedPath);
            $result = self::restoreSnapshot($snapshot);

            Logger::warning('Backup restored from uploaded file', [
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'tables_restored' => $result['tables_restored'],
                'rows_restored' => $result['rows_restored'],
            ]);

            return $result;
        } catch (Throwable $exception) {
            @unlink($storedPath);
            throw $exception;
        }
    }

    public static function maxUploadBytes(): int
    {
        return self::UPLOAD_MAX_BYTES;
    }

    private static function buildSnapshot(string $trigger): array
    {
        $pdo = Database::getConnection();
        $tables = self::listTableNames($pdo);

        $snapshotTables = [];
        foreach ($tables as $tableName) {
            $safeTable = self::quoteIdentifier($tableName);
            $rows = $pdo->query('SELECT * FROM ' . $safeTable)->fetchAll(PDO::FETCH_ASSOC);
            $snapshotTables[$tableName] = $rows;
        }

        return [
            'version' => 1,
            'created_at' => date('c'),
            'trigger' => $trigger,
            'tables' => $snapshotTables,
        ];
    }

    private static function restoreSnapshot(array $snapshot): array
    {
        $tables = $snapshot['tables'] ?? null;
        if (!is_array($tables) || empty($tables)) {
            throw new InvalidArgumentException('Backup payload is missing table data.');
        }

        $pdo = Database::getConnection();
        $existing = array_fill_keys(self::listTableNames($pdo), true);
        $tablesRestored = 0;
        $rowsRestored = 0;

        $transactionStarted = false;
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $pdo->beginTransaction();
            $transactionStarted = true;

            foreach ($tables as $tableName => $rows) {
                if (!is_string($tableName) || !isset($existing[$tableName])) {
                    continue;
                }

                $safeTable = self::quoteIdentifier($tableName);
                // DELETE preserves the transaction; TRUNCATE causes implicit commits in MySQL.
                $pdo->exec('DELETE FROM ' . $safeTable);
                $tablesRestored++;

                if (!is_array($rows) || empty($rows)) {
                    continue;
                }

                $firstRow = reset($rows);
                if (!is_array($firstRow) || empty($firstRow)) {
                    continue;
                }

                $columns = array_keys($firstRow);
                $quotedColumns = array_map([self::class, 'quoteIdentifier'], $columns);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $insertSql = 'INSERT INTO ' . $safeTable . ' (' . implode(', ', $quotedColumns) . ') VALUES (' . $placeholders . ')';
                $insertStmt = $pdo->prepare($insertSql);

                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $values = [];
                    foreach ($columns as $column) {
                        $values[] = $row[$column] ?? null;
                    }
                    $insertStmt->execute($values);
                    $rowsRestored++;
                }
            }

            $pdo->commit();
            $transactionStarted = false;
        } catch (Throwable $exception) {
            if ($transactionStarted && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        } finally {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Throwable $exception) {
                Logger::warning('Unable to re-enable foreign key checks after backup restore', [
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'tables_restored' => $tablesRestored,
            'rows_restored' => $rowsRestored,
        ];
    }

    private static function loadSnapshotFromFile(string $filePath): array
    {
        $payload = @file_get_contents($filePath);
        if ($payload === false || $payload === '') {
            throw new RuntimeException('Backup file could not be read.');
        }

        $json = @gzdecode($payload);
        if ($json === false || $json === '') {
            throw new InvalidArgumentException('Backup file is not a valid gzip payload.');
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Backup payload is not valid JSON.');
        }

        return $decoded;
    }

    private static function ensureBackupDirectory(): string
    {
        $root = dirname(__DIR__);
        $dir = $root . '/' . self::BACKUP_DIR_RELATIVE;

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create backup storage directory.');
        }

        $htaccessPath = $dir . '/.htaccess';
        if (!is_file($htaccessPath)) {
            @file_put_contents($htaccessPath, "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
        }

        return $dir;
    }

    private static function resolveBackupPath(string $fileName): string
    {
        $safeName = basename(trim($fileName));
        if (!preg_match('/^backup-[A-Za-z0-9._-]+\.json\.gz$/', $safeName)) {
            throw new InvalidArgumentException('Invalid backup file selected.');
        }

        return self::ensureBackupDirectory() . '/' . $safeName;
    }

    private static function listTableNames(PDO $pdo): array
    {
        $rows = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
        $tables = [];
        foreach ($rows as $row) {
            $table = (string)($row[0] ?? '');
            if ($table !== '') {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    private static function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException('Invalid identifier encountered in backup payload.');
        }

        return '`' . $identifier . '`';
    }

    private static function pruneByRetentionDays(string $backupDir, int $retentionDays): void
    {
        $retentionDays = max(1, $retentionDays);
        $cutoff = (new DateTimeImmutable('today'))->modify('-' . $retentionDays . ' days');
        $files = glob($backupDir . '/backup-*.json.gz');
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $filePath) {
            $mtime = filemtime($filePath);
            if ($mtime === false) {
                continue;
            }

            $fileDate = (new DateTimeImmutable())->setTimestamp($mtime);
            if ($fileDate < $cutoff) {
                @unlink($filePath);
            }
        }
    }

    private static function normalizeCadence(string $cadence): string
    {
        $value = strtolower(trim($cadence));
        if (!in_array($value, ['daily', 'weekly', 'monthly'], true)) {
            return 'daily';
        }
        return $value;
    }

    private static function normalizeRetentionDays(string $value): int
    {
        $days = (int)$value;
        if ($days < 1) {
            $days = 60;
        }
        return min($days, 3650);
    }

    private static function isBackupDue(string $lastRunAt, string $cadence): bool
    {
        if ($lastRunAt === '') {
            return true;
        }

        try {
            $last = new DateTimeImmutable($lastRunAt);
        } catch (Exception $exception) {
            return true;
        }

        $next = match ($cadence) {
            'monthly' => $last->modify('+1 month'),
            'weekly' => $last->modify('+7 days'),
            default => $last->modify('+1 day'),
        };

        return (new DateTimeImmutable('now')) >= $next;
    }

    private static function setLastRunAt(string $timestamp): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (name, value) VALUES (:name, :insert_value)
             ON DUPLICATE KEY UPDATE value = :update_value'
        );
        $stmt->execute([
            ':name' => 'backup_last_run_at',
            ':insert_value' => $timestamp,
            ':update_value' => $timestamp,
        ]);
    }

    private static function acquireScheduleLock()
    {
        $lockPath = self::ensureBackupDirectory() . '/' . self::SCHEDULE_LOCK_FILENAME;
        $handle = @fopen($lockPath, 'c+');
        if (!is_resource($handle)) {
            return null;
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            @fclose($handle);
            return null;
        }

        return $handle;
    }

    private static function releaseScheduleLock($handle): void
    {
        if (!is_resource($handle)) {
            return;
        }

        @flock($handle, LOCK_UN);
        @fclose($handle);
    }
}
