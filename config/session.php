<?php
// FILE: config/session.php
// Configuración segura de sesiones PHP

$sessionName = env('SESSION_NAME', 'converclick_crm');
$sessionLifetime = (int)env('SESSION_LIFETIME', 7200);

ini_set('session.name', $sessionName);
ini_set('session.gc_maxlifetime', $sessionLifetime);
ini_set('session.cookie_lifetime', $sessionLifetime);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

// Activar secure solo en HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}