<?php
// FILE: app/Models/Setting.php

namespace App\Models;

use App\Core\Database;

class Setting
{
    private Database $db;
    private static array $cache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $row = $this->db->fetchOne('SELECT value FROM settings WHERE `key` = ?', [$key]);
        $value = $row ? $row['value'] : $default;
        self::$cache[$key] = $value;
        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        self::$cache[$key] = $value;
        $this->db->execute(
            "INSERT INTO settings (`key`, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)",
        [$key, $value]
        );
    }

    public function all(): array
    {
        $rows = $this->db->fetchAll('SELECT `key`, value FROM settings');
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    }

    public function setMany(array $keyValues): void
    {
        foreach ($keyValues as $key => $value) {
            $this->set($key, $value);
        }
    }
}