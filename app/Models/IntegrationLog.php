<?php
// FILE: app/Models/IntegrationLog.php

namespace App\Models;

use App\Core\Database;

class IntegrationLog
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function log(
        string $source,
        string $direction = 'in',
        ?string $eventType = null,
        ?string $payload = null,
        string $status = 'ok',
        ?string $message = null,
        ?string $ip = null
        ): void
    {
        $this->db->execute(
            "INSERT INTO integration_logs (source, direction, event_type, payload, status, message, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$source, $direction, $eventType, $payload, $status, $message, $ip]
        );
    }

    public function recent(string $source = null, int $limit = 50): array
    {
        if ($source) {
            return $this->db->fetchAll(
                "SELECT * FROM integration_logs WHERE source = ? ORDER BY created_at DESC LIMIT {$limit}",
            [$source]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM integration_logs ORDER BY created_at DESC LIMIT {$limit}"
        );
    }

    public function countByStatus(string $source): array
    {
        return $this->db->fetchAll(
            "SELECT status, COUNT(*) as total FROM integration_logs WHERE source = ? GROUP BY status",
        [$source]
        );
    }
}