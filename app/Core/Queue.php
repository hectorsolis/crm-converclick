<?php
// FILE: app/Core/Queue.php

namespace App\Core;

class Queue
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function push(string $jobHandler, array $payload, string $queue = 'default'): void
    {
        $this->db->query(
            "INSERT INTO jobs (queue, payload, available_at, created_at) VALUES (?, ?, ?, ?)",
            [
                $queue,
                json_encode(['handler' => $jobHandler, 'data' => $payload]),
                time(),
                time()
            ]
        );
    }

    public function work(string $queue = 'default'): void
    {
        // 1. Reservar job
        $now = time();
        
        // Lock otimista: update primeiro
        // Nota: em MySQL puro isso pode ter race conditions sem transactions ou FOR UPDATE, 
        // mas para este escopo é aceitável.
        $sql = "SELECT id, payload FROM jobs 
                WHERE queue = ? AND reserved_at IS NULL AND available_at <= ? 
                ORDER BY id ASC LIMIT 1";
        
        $job = $this->db->fetchOne($sql, [$queue, $now]);

        if (!$job) {
            return;
        }

        // Marcar como reservado
        $this->db->query("UPDATE jobs SET reserved_at = ? WHERE id = ?", [$now, $job['id']]);

        // Processar
        try {
            $payload = json_decode($job['payload'], true);
            $handler = $payload['handler'];
            $data = $payload['data'];

            if (class_exists($handler)) {
                $instance = new $handler();
                if (method_exists($instance, 'handle')) {
                    $instance->handle($data);
                }
            }

            // Sucesso: remover
            $this->db->query("DELETE FROM jobs WHERE id = ?", [$job['id']]);
            echo "Job {$job['id']} processado com sucesso.\n";

        } catch (\Exception $e) {
            echo "Erro no Job {$job['id']}: " . $e->getMessage() . "\n";
            // Liberar para tentar novamente (backoff simples) ou marcar como falha
            // Aqui vamos apenas liberar após 5 min
            $this->db->query("UPDATE jobs SET reserved_at = NULL, available_at = ?, attempts = attempts + 1 WHERE id = ?", [$now + 300, $job['id']]);
        }
    }
}
