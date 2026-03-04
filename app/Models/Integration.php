<?php
// FILE: app/Models/Integration.php

namespace App\Models;

use App\Core\Database;

class Integration
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getConfig(string $type): array
    {
        $row = $this->db->fetchOne('SELECT * FROM integrations WHERE type = ?', [$type]);
        if (!$row)
            return [];
        $config = json_decode($row['config'], true) ?? [];
        $config['is_active'] = (bool)$row['is_active'];
        $config['id'] = $row['id'];
        return $config;
    }

    public function saveConfig(string $type, array $config): void
    {
        $isActive = (int)($config['is_active'] ?? true);
        unset($config['is_active'], $config['id']);

        $this->db->execute(
            "INSERT INTO integrations (type, config, is_active) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE config = VALUES(config), is_active = VALUES(is_active)",
        [$type, json_encode($config, JSON_UNESCAPED_UNICODE), $isActive]
        );
    }

    public function isActive(string $type): bool
    {
        $row = $this->db->fetchOne('SELECT is_active FROM integrations WHERE type = ?', [$type]);
        return $row ? (bool)$row['is_active'] : false;
    }
}