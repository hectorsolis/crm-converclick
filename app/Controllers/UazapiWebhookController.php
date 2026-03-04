<?php
// FILE: app/Controllers/UazapiWebhookController.php
// Recibe webhooks de uazapiGO V2

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Helpers\LeadDeduplicator;
use App\Helpers\PhoneNormalizer;

class UazapiWebhookController extends Controller
{
    public function receive(Request $request): void
    {
        if ($request->method() !== 'POST') {
            View::json(['error' => 'Method not allowed'], 405);
        }

        $ip = $request->ip();
        $logModel = new IntegrationLog();
        $rawBody = $request->rawBody();

        // ── Verificar secret ─────────────────────────────────────────
        $integration = new Integration();
        $config = $integration->getConfig('uazapi');
        $secret = $config['webhook_secret'] ?? '';

        if (!empty($secret)) {
            $receivedSecret = $_GET['secret']
                ?? $_SERVER['HTTP_X_UAZAPI_SECRET']
                ?? $_SERVER['HTTP_X_WEBHOOK_SECRET']
                ?? '';
            if (!hash_equals($secret, $receivedSecret)) {
                $logModel->log('uazapi', 'in', 'auth_failed', $rawBody, 'error', 'Secret inválido', $ip);
                View::json(['error' => 'Unauthorized'], 401);
            }
        }

        $payload = $request->json();
        $logModel->log('uazapi', 'in', 'webhook_received', json_encode($payload), 'ok', '', $ip);

        // ── Filtrar eventos relevantes ────────────────────────────────
        // Solo procesar eventos de tipo 'messages'
        $event = $payload['event'] ?? $payload['type'] ?? '';
        if (!str_contains(strtolower($event), 'message') && !empty($event)) {
            // Eventos de conexión u otros → solo logear
            $logModel->log('uazapi', 'in', $event, json_encode($payload), 'ok', 'Evento ignorado (no es mensaje)', $ip);
            View::json(['status' => 'ignored', 'event' => $event]);
        }

        // ── Extraer datos del mensaje ────────────────────────────────
        $leadData = $this->extractFromUazapiPayload($payload);

        if (empty($leadData['phone'])) {
            $logModel->log('uazapi', 'in', 'no_phone', json_encode($payload), 'error', 'Sin número de teléfono', $ip);
            View::json(['status' => 'ignored', 'reason' => 'no_phone']);
        }

        $leadData['source_timestamp'] = date('Y-m-d H:i:s');

        // ── Deduplicación ────────────────────────────────────────────
        $deduplicator = new LeadDeduplicator();
        $result = $deduplicator->process($leadData, 'whatsapp', 'uazapi');

        $status = $result['action'] === 'conflict' ? 'conflict' :
            ($result['action'] === 'updated' ? 'duplicate' : 'ok');

        $logModel->log('uazapi', 'in', $result['action'], json_encode($payload), $status,
            "Lead ID: {$result['lead_id']}", $ip);

        View::json([
            'status' => 'success',
            'action' => $result['action'],
            'lead_id' => $result['lead_id'],
        ]);
    }

    private function extractFromUazapiPayload(array $payload): array
    {
        $data = [];

        // Estructura típica de uazapiGO V2 para evento 'messages'
        // El remitente puede estar en distintas ubicaciones según la versión
        $key = $payload['key'] ?? $payload['data']['key'] ?? [];
        $pushName = $payload['pushName'] ?? $payload['data']['pushName'] ?? '';
        $from = $key['remoteJid'] ?? $payload['from'] ?? '';

        // remoteJid suele tener formato: "56912345678@s.whatsapp.net"
        if ($from) {
            $phone = preg_replace('/@.+$/', '', $from); // quitar @s.whatsapp.net
            $data['phone'] = PhoneNormalizer::normalize($phone);
        }

        // Nombre del contacto
        if ($pushName) {
            $data['name'] = $pushName;
        }
        elseif (!empty($payload['data']['message']['extendedTextMessage']['contextInfo']['participant'])) {
            // fallback
            $data['name'] = '';
        }

        // Si el mensaje fue enviado por la API (wasSentByApi) → ignorar para evitar loops
        $fromMe = $key['fromMe'] ?? false;
        if ($fromMe) {
            $data['_skip'] = true;
        }

        return $data;
    }
}