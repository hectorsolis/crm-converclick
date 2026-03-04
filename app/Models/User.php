<?php
// FILE: app/Models/User.php

namespace App\Models;

use App\Core\Database;

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array |false
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmail(string $email): array |false
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
    }

    public function all(): array
    {
        return $this->db->fetchAll('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY name');
    }

    public function vendedores(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name FROM users WHERE role IN ('admin','vendedor') AND is_active = 1 ORDER BY name"
        );
    }

    public function create(array $data): int
    {
        $this->db->execute(
            "INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)",
        [
            trim($data['name']),
            strtolower(trim($data['email'])),
            password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            $data['role'] ?? 'vendedor',
            1,
        ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = trim($data['name']);
        }
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = strtolower(trim($data['email']));
        }
        if (isset($data['role'])) {
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = (int)$data['is_active'];
        }
        if (isset($data['password'])) {
            $fields[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (empty($fields))
            return;

        $params[] = $id;
        $this->db->execute("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?", $params);
    }

    public function toggleActive(int $id): void
    {
        $this->db->execute('UPDATE users SET is_active = NOT is_active WHERE id = ?', [$id]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function authenticate(string $email, string $password): array |false
    {
        $user = $this->findByEmail($email);
        if (!$user)
            return false;
        if (!$user['is_active'])
            return false;
        if (!$this->verifyPassword($password, $user['password']))
            return false;
        return $user;
    }
}