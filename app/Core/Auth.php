<?php
// FILE: app/Core/Auth.php
// Gestión de autenticación basada en sesión

namespace App\Core;

class Auth
{
    public static function login(array $user): void
    {
        // Regenerar ID de sesión para prevenir session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['auth_user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth_user']['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['auth_user']['id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['auth_user']['role'] ?? '') === 'admin';
    }

    public static function isVendedor(): bool
    {
        return ($_SESSION['auth_user']['role'] ?? '') === 'vendedor';
    }

    /**
     * Redirige al login si no está autenticado
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Redirige con error 403 si no es admin
     */
    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            View::render('errors/403', [], 'main');
            exit;
        }
    }
}