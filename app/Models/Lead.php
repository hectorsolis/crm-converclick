<?php
// FILE: app/Models/Lead.php
// Modelo principal de leads con todos los campos del CRM

namespace App\Models;

use App\Core\Database;
use App\Helpers\PhoneNormalizer;

class Lead
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Búsqueda ────────────────────────────────────────────

    public function findById(int $id): array |false
    {
        return $this->db->fetchOne(
            "SELECT l.*, u.name as vendedor_name
             FROM leads l
             LEFT JOIN users u ON u.id = l.assigned_to
             WHERE l.id = ?",
        [$id]
        );
    }

    public function findByEmail(string $email): array |false
    {
        $email = strtolower(trim($email));
        if (empty($email))
            return false;
        return $this->db->fetchOne('SELECT * FROM leads WHERE email = ?', [$email]);
    }

    public function findByPhone(string $phone): array |false
    {
        $phone = PhoneNormalizer::normalize($phone);
        if (empty($phone))
            return false;
        return $this->db->fetchOne('SELECT * FROM leads WHERE phone = ?', [$phone]);
    }

    public function findByMauticId(int $mauticId): array |false
    {
        return $this->db->fetchOne('SELECT * FROM leads WHERE mautic_contact_id = ?', [$mauticId]);
    }

    // ─── Listados ─────────────────────────────────────────────

    public function all(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)';
            $params = array_merge($params, [$s, $s, $s]);
        }

        if (!empty($filters['source'])) {
            $where[] = 'l.source = ?';
            $params[] = $filters['source'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'l.assigned_to = ?';
            $params[] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(l.created_at) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(l.created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        if (isset($filters['next_step_filter'])) {
            $today = date('Y-m-d');
            $in7 = date('Y-m-d', strtotime('+7 days'));
            switch ($filters['next_step_filter']) {
                case 'overdue':
                    $where[] = 'l.next_step_date < NOW() AND l.next_step IS NOT NULL';
                    break;
                case 'today':
                    $where[] = 'DATE(l.next_step_date) = ?';
                    $params[] = $today;
                    break;
                case 'next7':
                    $where[] = 'DATE(l.next_step_date) BETWEEN ? AND ?';
                    $params[] = $today;
                    $params[] = $in7;
                    break;
            }
        }

        if (isset($filters['qualification'])) {
            switch ($filters['qualification']) {
                case 'complete':
                    $where[] = 'l.has_budget = 1 AND l.has_deadline = 1 AND l.has_active_problem = 1 AND l.decision_maker = 1';
                    break;
                case 'incomplete':
                    $where[] = 'NOT (l.has_budget = 1 AND l.has_deadline = 1 AND l.has_active_problem = 1 AND l.decision_maker = 1)';
                    break;
            }
        }

        if (isset($filters['conflict'])) {
            $where[] = 'l.conflict_flag = ?';
            $params[] = (int)$filters['conflict'];
        }

        $whereStr = implode(' AND ', $where);
        $order = 'l.created_at DESC';

        return $this->db->fetchAll(
            "SELECT l.*, u.name as vendedor_name
             FROM leads l
             LEFT JOIN users u ON u.id = l.assigned_to
             WHERE {$whereStr}
             ORDER BY {$order}",
            $params
        );
    }

    public function countBySource(): array
    {
        return $this->db->fetchAll(
            "SELECT source, COUNT(*) as total FROM leads GROUP BY source"
        );
    }

    public function countTotal(): int
    {
        $r = $this->db->fetchOne('SELECT COUNT(*) as cnt FROM leads');
        return (int)($r['cnt'] ?? 0);
    }

    public function countToday(): int
    {
        $r = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM leads WHERE DATE(created_at) = CURDATE()");
        return (int)($r['cnt'] ?? 0);
    }

    public function countOverdue(): int
    {
        $r = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM leads WHERE next_step_date < NOW() AND next_step IS NOT NULL"
        );
        return (int)($r['cnt'] ?? 0);
    }

    public function recentLeads(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT l.*, u.name as vendedor_name FROM leads l
             LEFT JOIN users u ON u.id = l.assigned_to
             ORDER BY l.created_at DESC LIMIT {$limit}"
        );
    }

    // ─── Creación ─────────────────────────────────────────────

    public function create(array $data): int
    {
        $this->db->execute(
            "INSERT INTO leads (name, email, phone, company_name, company_industry, company_size,
                source, source_detail, source_timestamp, assigned_to,
                has_budget, has_deadline, has_active_problem, decision_maker,
                context_notes, next_step, next_step_date, mautic_contact_id, conflict_flag, conflict_detail)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            trim($data['name'] ?? 'Sin nombre'),
            !empty($data['email']) ? strtolower(trim($data['email'])) : null,
            !empty($data['phone']) ?PhoneNormalizer::normalize($data['phone']) : null,
            $data['company_name'] ?? null,
            $data['company_industry'] ?? null,
            $data['company_size'] ?? null,
            $data['source'] ?? 'manual',
            $data['source_detail'] ?? null,
            $data['source_timestamp'] ?? date('Y-m-d H:i:s'),
            $data['assigned_to'] ?? null,
            (int)($data['has_budget'] ?? 0),
            (int)($data['has_deadline'] ?? 0),
            (int)($data['has_active_problem'] ?? 0),
            (int)($data['decision_maker'] ?? 0),
            $data['context_notes'] ?? null,
            $data['next_step'] ?? null,
            !empty($data['next_step_date']) ? $data['next_step_date'] : null,
            $data['mautic_contact_id'] ?? null,
            (int)($data['conflict_flag'] ?? 0),
            $data['conflict_detail'] ?? null,
        ]
        );
        return (int)$this->db->lastInsertId();
    }

    // ─── Actualización ────────────────────────────────────────

    public function update(int $id, array $data): void
    {
        $allowed = [
            'name', 'email', 'phone', 'company_name', 'company_industry', 'company_size',
            'source', 'source_detail', 'source_timestamp', 'assigned_to',
            'has_budget', 'has_deadline', 'has_active_problem', 'decision_maker',
            'context_notes', 'next_step', 'next_step_date',
            'mautic_contact_id', 'conflict_flag', 'conflict_detail',
        ];

        $fields = [];
        $params = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $data))
                continue;
            $fields[] = "{$field} = ?";
            $val = $data[$field];

            if ($field === 'email' && !empty($val))
                $val = strtolower(trim($val));
            if ($field === 'phone' && !empty($val))
                $val = PhoneNormalizer::normalize($val);
            $params[] = $val;
        }

        if (empty($fields))
            return;

        $params[] = $id;
        $this->db->execute("UPDATE leads SET " . implode(', ', $fields) . " WHERE id = ?", $params);
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM leads WHERE id = ?', [$id]);
    }
}