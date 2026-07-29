<?php
require_once __DIR__ . '/Database.php';

class Technician
{
    public static function findAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, name, phone, active, created_at FROM technicians ORDER BY name');
        return $stmt->fetchAll();
    }

    public static function findAllActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, name FROM technicians WHERE active = 1 ORDER BY name');
        return $stmt->fetchAll();
    }
}
