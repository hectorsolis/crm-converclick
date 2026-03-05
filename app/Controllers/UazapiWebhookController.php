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
            // Verifica o secret na URL (GET) ou nos headers
            $receivedSecret = $_GET['secret'] 
                ?? $_SERVER['HTTP_X_UAZAPI_SECRET'] 
                ?? $_SERVER['HTTP_X_WEBHOOK_SECRET'] 
                ?? null;

            // Se não encontrou em nenhum lugar, tenta pegar do payload se existir (alguns webhooks mandam no body)
            if (!$receivedSecret && isset($payload['secret'])) {
                $receivedSecret = $payload['secret'];
            }
            
            // Debug: Se não veio secret, vamos logar os headers para ver se está vindo com outro nome
            if (!$receivedSecret) {
                $headers = getallheaders();
                $logModel->log('uazapi', 'in', 'debug_headers', json_encode($headers), 'warning', 'Secret não encontrado. Verificando headers.', $ip);
            }

            // MUDANÇA TEMPORÁRIA: Logar erro mas permitir continuar se o secret for null, para diagnosticar o payload real
            // Depois reverteremos para bloquear
            if (!$receivedSecret || !hash_equals($secret, $receivedSecret)) {
                 $logModel->log('uazapi', 'in', 'auth_failed_soft', $rawBody, 'warning', "Secret inválido ou ausente. Recebido: " . ($receivedSecret ? '***' : 'null') . ". Permitindo para debug.", $ip);
                 // View::json(['error' => 'Unauthorized', 'message' => 'Invalid or missing secret'], 401);
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
        $data = [
            'name' => '',
            'phone' => null,
            '_skip' => false
        ];

        // 1. Tenta extrair da estrutura "message" (objeto direto no payload, como visto nos logs)
        // Payload exemplo: {"message": {"sender_pn": "551199999999@s.whatsapp.net", "senderName": "RS", ...}, "chat": {...}}
        if (isset($payload['message']) && is_array($payload['message'])) {
             $msg = $payload['message'];
             
             // Extrair telefone de 'sender_pn' ou 'chatid'
             $remoteJid = $msg['sender_pn'] ?? $msg['chatid'] ?? '';
             
             if ($remoteJid) {
                 $phone = preg_replace('/@.+$/', '', $remoteJid);
                 $data['phone'] = preg_replace('/[^0-9]/', '', $phone);
             }
             
             // Extrair nome
             $data['name'] = $msg['senderName'] ?? $payload['chat']['name'] ?? '';
             
             // Verificar se foi enviado por mim
             if (!empty($msg['fromMe'])) {
                 $data['_skip'] = true;
             }
             
             return $data;
        }

        // 2. Fallback: Tenta extrair da estrutura "messages" (array antigo)
        $message = $payload['data'] ?? $payload;
        if (isset($message[0]) && is_array($message[0])) {
            $message = $message[0];
        }

        // Estrutura comum antiga: key -> remoteJid
        $key = $message['key'] ?? $message['data']['key'] ?? [];
        $remoteJid = $key['remoteJid'] ?? $message['remoteJid'] ?? '';
        
        if ($remoteJid) {
            $phone = preg_replace('/@.+$/', '', $remoteJid);
            $data['phone'] = preg_replace('/[^0-9]/', '', $phone);
        }

        $pushName = $message['pushName'] ?? $message['data']['pushName'] ?? '';
        if ($pushName) {
            $data['name'] = $pushName;
        }

        $fromMe = $key['fromMe'] ?? $message['fromMe'] ?? false;
        if ($fromMe) {
            $data['_skip'] = true;
        }

        return $data;
    }
}