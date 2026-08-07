<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

class Installation
{
    public static function redirectToInstallerIfNeeded(): void
    {
        if (!self::isInstallationRequired()) {
            return;
        }

        header('Location: ' . url('install.php'));
        exit;
    }

    public static function isInstallationRequired(): bool
    {
        try {
            Database::getConnection();
            return false;
        } catch (\PDOException $exception) {
            if (Database::isInstallationMissingException($exception)) {
                return true;
            }
            if (self::isConnectionConfigurationException($exception)) {
                return true;
            }

            throw $exception;
        }
    }

    private static function isConnectionConfigurationException(\PDOException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'SQLSTATE[HY000] [1045]')
            || str_contains($message, 'SQLSTATE[HY000] [2002]')
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'Access denied for user');
    }
}
