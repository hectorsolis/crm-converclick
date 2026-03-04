<?php
// FILE: app/Models/LeadActivity.php

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;

class LeadActivity
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function log(int $leadId, string $type, string $description, ?int $userId = null, ?array $metadata = null): void
    {
        $userId = $userId ?? Auth::id();
        $meta = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $this->db->execute(
            "INSERT INTO lead_activities (lead_id, user_id, type, description, metadata) VALUES (?, ?, ?, ?, ?)",
        [$leadId, $userId, $type, $description, $meta]
        );
    }

    public function forLead(int $leadId): array
    {
        return $this->db->fetchAll(
            "SELECT la.*, u.name as user_name
             FROM lead_activities la
             LEFT JOIN users u ON u.id = la.user_id
             WHERE la.lead_id = ?
             ORDER BY la.created_at DESC",
        [$leadId]
        );
    }
}