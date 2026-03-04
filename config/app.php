<?php
// FILE: config/app.php
// Carga variables de entorno y define constantes globales

// Cargar .env si existe
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Función helper para leer env
function env(string $key, mixed $default = null): mixed {
    $val = $_ENV[$key] ?? getenv($key);
    if ($val === false) return $default;
    return match(strtolower($val)) {
        'true'  => true,
        'false' => false,
        'null'  => null,
        default => $val,
    };
}

// Constantes de aplicación
define('APP_NAME',    env('APP_NAME', 'Converclick CRM'));
define('APP_URL',     rtrim(env('APP_URL', 'http://localhost'), '/'));
define('APP_ENV',     env('APP_ENV', 'production'));
define('APP_DEBUG',   env('APP_DEBUG', false));
define('TIMEZONE',    env('TIMEZONE', 'America/Santiago'));
define('APP_KEY',     env('APP_KEY', 'default_key_change_me'));

// Directorio raíz
define('BASE_PATH',   dirname(__DIR__));
define('APP_PATH',    BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('VIEW_PATH',   APP_PATH  . '/Views');
define('LOG_PATH',    BASE_PATH . '/logs');

// Zona horaria
date_default_timezone_set(TIMEZONE);

// Errores
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Constantes de seguridad
define('MAUTIC_WEBHOOK_SECRET', env('MAUTIC_WEBHOOK_SECRET', ''));
define('UAZAPI_WEBHOOK_SECRET', env('UAZAPI_WEBHOOK_SECRET', ''));
