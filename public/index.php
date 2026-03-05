<?php
// FILE: public/index.php
// Front controller — punto de entrada de toda la aplicación

declare(strict_types=1);

// Autoloader simple PSR-4
spl_autoload_register(function (string $class): void {
    $base = dirname(__DIR__) . '/app/';
    $relative = str_replace('App\\', '', $class);
    $file = $base . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Configuración global
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/session.php';

use App\Core\Router;
use App\Core\Request;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

$router = new Router();
$request = new Request();

// ──────────────────────────────────────────────────────────────
// Dashboard WhatsApp Widget
// ──────────────────────────────────────────────────────────────
$router->get('/dashboard/whatsapp/status', 'DashboardWhatsAppController@status');

// ──────────────────────────────────────────────────────────────
// Recuperación de Contraseña
// ──────────────────────────────────────────────────────────────
$router->get('/forgot-password', 'PasswordResetController@showForgotForm');
$router->post('/forgot-password/send', 'PasswordResetController@sendResetLink');
$router->get('/reset-password', 'PasswordResetController@showResetForm');
$router->post('/reset-password/update', 'PasswordResetController@resetPassword');

// ──────────────────────────────────────────────────────────────
// Rutas de Autenticación
// ──────────────────────────────────────────────────────────────
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Redirect raíz a dashboard
$router->get('/', 'DashboardController@index', [AuthMiddleware::class]);

// ──────────────────────────────────────────────────────────────
// Dashboard
// ──────────────────────────────────────────────────────────────
$router->get('/dashboard', 'DashboardController@index', [AuthMiddleware::class]);

// ──────────────────────────────────────────────────────────────
// Leads
// ──────────────────────────────────────────────────────────────
$router->get('/leads', 'LeadController@index', [AuthMiddleware::class]);
$router->get('/leads/export', 'LeadController@export', [AuthMiddleware::class]);
$router->get('/leads/create', 'LeadController@create', [AuthMiddleware::class]);
$router->post('/leads', 'LeadController@store', [AuthMiddleware::class]);
$router->get('/leads/:id', 'LeadController@show', [AuthMiddleware::class]);
$router->get('/leads/:id/edit', 'LeadController@edit', [AuthMiddleware::class]);
$router->post('/leads/:id', 'LeadController@update', [AuthMiddleware::class]);
$router->post('/leads/:id/delete', 'LeadController@delete', [AdminMiddleware::class]);

// ──────────────────────────────────────────────────────────────
// Pipeline
// ──────────────────────────────────────────────────────────────
$router->get('/pipeline', 'PipelineController@index', [AuthMiddleware::class]);

// ──────────────────────────────────────────────────────────────
// Usuarios (solo Admin)
// ──────────────────────────────────────────────────────────────
$router->get('/users', 'UserController@index', [AdminMiddleware::class]);
$router->get('/users/create', 'UserController@create', [AdminMiddleware::class]);
$router->post('/users', 'UserController@store', [AdminMiddleware::class]);
$router->get('/users/:id/edit', 'UserController@edit', [AdminMiddleware::class]);
$router->post('/users/:id', 'UserController@update', [AdminMiddleware::class]);
$router->post('/users/:id/toggle', 'UserController@toggleActive', [AdminMiddleware::class]);

// ──────────────────────────────────────────────────────────────
// Ajustes (solo Admin)
// ──────────────────────────────────────────────────────────────
$router->get('/settings', 'SettingsController@index', [AdminMiddleware::class]);
$router->post('/settings/save', 'SettingsController@save', [AdminMiddleware::class]);

// ──────────────────────────────────────────────────────────────
// Integraciones (solo Admin)
// ──────────────────────────────────────────────────────────────
$router->get('/integrations', 'IntegrationController@index', [AdminMiddleware::class]);
$router->post('/integrations/mautic/save', 'IntegrationController@saveMautic', [AdminMiddleware::class]);
$router->post('/integrations/uazapi/save', 'IntegrationController@saveUazapi', [AdminMiddleware::class]);
$router->post('/integrations/chatwoot/save', 'IntegrationController@saveChatwoot', [AdminMiddleware::class]);
$router->post('/integrations/uazapi/register-webhook', 'IntegrationController@registerUazapiWebhook', [AdminMiddleware::class]);
$router->get('/integrations/uazapi/test', 'IntegrationController@testUazapi', [AdminMiddleware::class]);

// ──────────────────────────────────────────────────────────────
// Webhooks (públicos — protegidos por secret propio)
// ──────────────────────────────────────────────────────────────
$router->post('/integrations/mautic/webhook', 'MauticWebhookController@receive');
$router->post('/integrations/uazapi/webhook', 'UazapiWebhookController@receive');
$router->post('/integrations/chatwoot/webhook', 'ChatwootWebhookController@receive');

// ──────────────────────────────────────────────────────────────
// Despachar
// ──────────────────────────────────────────────────────────────
$router->dispatch($request);