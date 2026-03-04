<?php
// FILE: app/Core/Csrf.php
// Tokens CSRF para formularios

namespace App\Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function generateToken(): string
    {
        if (!isset($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function validateToken(string $token): bool
    {
        $stored = $_SESSION[self::TOKEN_KEY] ?? '';
        return hash_equals($stored, $token);
    }

    public static function getToken(): string
    {
        return $_SESSION[self::TOKEN_KEY] ?? self::generateToken();
    }

    /**
     * Genera el campo hidden HTML para formularios
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Valida el token del request POST y aborta si es inválido
     */
    public static function verifyPost(): void
    {
        $token = $_POST['_csrf_token'] ?? '';
        if (!self::validateToken($token)) {
            http_response_code(419);
            die('<h1>Error de seguridad</h1><p>Token CSRF inválido. Vuelve atrás y reintenta.</p>');
        }
    }
}