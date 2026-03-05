<?php
// FILE: app/Models/PasswordReset.php

namespace App\Models;

use App\Core\Database;

class PasswordReset
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createToken(string $email): string
    {
        // Remove tokens anteriores
        $this->db->query("DELETE FROM password_resets WHERE email = ?", [$email]);

        $token = bin2hex(random_bytes(32));
        $this->db->query(
            "INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())",
            [$email, $token]
        );

        return $token;
    }

    public function findByToken(string $token): ?array
    {
        $result = $this->db->fetchOne(
            "SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$token]
        );
        return $result ?: null;
    }

    public function deleteByEmail(string $email): void
    {
        $this->db->query("DELETE FROM password_resets WHERE email = ?", [$email]);
    }
    
    // Rate limiting simples: conta solicitações na última hora
    public function countRecentRequests(string $email): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM password_resets WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$email]
        );
        return (int)($result['count'] ?? 0);
    }
}
