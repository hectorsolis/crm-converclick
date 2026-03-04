<?php
// FILE: app/Controllers/ChatwootWebhookController.php
// Recibe webhooks de Chatwoot

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\Integration;
use App\Models\IntegrationLog;

class ChatwootWebhookController extends Controller
{
    public function receive(Request $request): void
    {
        // Solo aceptar POST
        if ($request->method() !== 'POST') {
            View::json(['error' => 'Method not allowed'], 405);
        }

        $ip = $request->ip();
        $logModel = new IntegrationLog();
        $rawBody = $request->rawBody();
        $payload = $request->json();

        // ── Verificar secret ──────────────────────────────────────────
        $integration = new Integration();
        $config = $integration->getConfig('chatwoot');
        $secret = $config['webhook_secret'] ?? '';

        if (!empty($secret)) {
            // Chatwoot no envía el secret en header por defecto, así que lo esperamos en la URL
            $receivedSecret = $_GET['secret'] ?? '';
            if (!hash_equals($secret, $receivedSecret)) {
                $logModel->log('chatwoot', 'in', 'auth_failed', $rawBody, 'error', 'Secret inválido', $ip);
                View::json(['error' => 'Unauthorized'], 401);
            }
        }

        $eventType = $payload['event'] ?? 'unknown';
        $logModel->log('chatwoot', 'in', $eventType, json_encode($payload), 'ok', 'Webhook recibido', $ip);

        // Aquí se puede agregar la lógica para procesar leads desde Chatwoot
        // Ejemplo: si es 'conversation_created' o 'message_created', extraer contacto y crear lead

        View::json(['status' => 'success']);
    }
}
