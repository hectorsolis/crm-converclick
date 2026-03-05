<?php
// FILE: app/Core/Database.php
// Singleton PDO con helpers query, fetchAll, fetchOne, execute

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config = require BASE_PATH . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
            // Sincronizar timezone do MySQL com o do PHP
            $now = new \DateTime('now', new \DateTimeZone(defined('TIMEZONE') ? TIMEZONE : 'UTC'));
            $offset = $now->format('P'); // Ex: -03:00
            $this->pdo->exec("SET time_zone = '$offset'");
        }
        catch (PDOException $e) {
            if (APP_DEBUG) {
                throw $e;
            }
            http_response_code(500);
            die('Error de conexión a la base de datos. Contacte al administrador.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Ejecuta una consulta y retorna el statement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Obtiene todos los resultados como array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Obtiene un solo registro
     */
    public function fetchOne(string $sql, array $params = []): array |false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Ejecuta INSERT/UPDATE/DELETE, retorna filas afectadas
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Retorna el último ID insertado
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}