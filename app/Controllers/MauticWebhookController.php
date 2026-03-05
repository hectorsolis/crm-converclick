<?php
// FILE: app/Controllers/MauticWebhookController.php
// Recibe webhooks de Mautic v2.16.3

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Helpers\LeadDeduplicator;
use App\Services\MauticService;

class MauticWebhookController extends Controller
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

        // ── Verificar secret ──────────────────────────────────────────
        $integration = new Integration();
        $config = $integration->getConfig('mautic');
        $secret = $config['webhook_secret'] ?? '';

        if (!empty($secret)) {
            // Mautic puede enviar el secret como query param o header personalizado
            $receivedSecret = $_GET['secret'] ?? $_SERVER['HTTP_X_MAUTIC_SECRET'] ?? '';
            if (!hash_equals($secret, $receivedSecret)) {
                $logModel->log('mautic', 'in', 'auth_failed', $rawBody, 'error', 'Secret inválido', $ip);
                View::json(['error' => 'Unauthorized'], 401);
            }
        }

        // ── Parsear payload ───────────────────────────────────────────
        $payload = $request->json();
        if (empty($payload)) {
            // Mautic a veces envía form-encoded
            $payload = $_POST;
        }

        $logModel->log('mautic', 'in', 'webhook_received', json_encode($payload), 'ok', '', $ip);

        // ── Extraer datos del lead ────────────────────────────────────
        // Mautic Modo A: el payload trae el contacto completo
        $leadData = $this->extractFromMauticPayload($payload);

        // Si no hay email ni teléfono, intentar Modo B (consultar API de Mautic)
        if (empty($leadData['email']) && empty($leadData['phone'])) {
            $mauticContactId = $this->extractContactId($payload);
            if ($mauticContactId) {
                $service = new MauticService();
                $contact = $service->getContact($mauticContactId);
                if ($contact) {
                    $leadData = array_merge($leadData, $service->extractLeadData($contact));
                    $leadData['mautic_contact_id'] = $mauticContactId;
                }
            }
        }

        // Verificar form_id habilitado
        $formId = $this->extractFormId($payload);
        $enabledForms = (new MauticService())->getEnabledFormIds();
        
        if (!empty($enabledForms)) {
            // Se houver restrição de formulários, o ID do formulário é OBRIGATÓRIO.
            // Isso bloqueia eventos genéricos (como lead_post_save_new) que não trazem ID de formulário
            // e passariam despercebidos pela verificação anterior.
            if (!$formId) {
                $logModel->log('mautic', 'in', 'form_missing', json_encode($payload), 'warning',
                    "Evento ignorado: Restrição de formulário ativa (" . implode(',', $enabledForms) . ") mas evento não possui ID de formulário.", $ip);
                View::json(['status' => 'ignored', 'reason' => 'form_id_required_by_config']);
            }

            if (!in_array($formId, $enabledForms)) {
                $logModel->log('mautic', 'in', 'form_blocked', json_encode($payload), 'error',
                    "Form ID {$formId} no está habilitado. Permitidos: " . implode(',', $enabledForms), $ip);
                View::json(['status' => 'ignored', 'reason' => 'form_not_enabled']);
            }
        }

        if (empty($leadData['email']) && empty($leadData['phone'])) {
            $logModel->log('mautic', 'in', 'no_identifier', json_encode($payload), 'error',
                'Sin email ni teléfono en el payload', $ip);
            View::json(['status' => 'ignored', 'reason' => 'no_identifiers']);
        }

        $leadData['source_timestamp'] = date('Y-m-d H:i:s');
        $sourceDetail = $formId ? "form_{$formId}" : 'mautic_webhook';

        // ── Deduplicación ─────────────────────────────────────────────
        $deduplicator = new LeadDeduplicator();
        $result = $deduplicator->process($leadData, 'mautic_form', $sourceDetail);

        $status = $result['action'] === 'conflict' ? 'conflict' :
            ($result['action'] === 'updated' ? 'duplicate' : 'ok');

        $logModel->log('mautic', 'in', $result['action'], json_encode($payload), $status,
            "Lead ID: {$result['lead_id']}", $ip);

        View::json([
            'status' => 'success',
            'action' => $result['action'],
            'lead_id' => $result['lead_id'],
        ]);
    }

    // ─── Extractores de payload ───────────────────────────────────────

    private function extractFromMauticPayload(array $payload): array
    {
        $data = [
            'name' => '',
            'email' => null,
            'phone' => null,
            'company_name' => null
        ];

        // 1. Tenta extrair do evento "mautic.form_on_submit"
        $event = $payload['mautic.form_on_submit'][0] ?? null;

        if ($event) {
            // Estrutura: submission -> lead -> fields -> core
            $lead = $event['submission']['lead'] ?? $event['lead'] ?? null;
            
            if ($lead && isset($lead['fields']['core'])) {
                $fields = $lead['fields']['core'];
                
                // Mapeamento de campos
                $data['email'] = $fields['email']['value'] ?? null;
                $data['phone'] = $fields['phone']['value'] ?? $fields['mobile']['value'] ?? null;
                
                $firstname = $fields['firstname']['value'] ?? '';
                $lastname = $fields['lastname']['value'] ?? '';
                $data['name'] = trim("$firstname $lastname");
                
                $data['company_name'] = $fields['company']['value'] ?? null;
                
                if (isset($lead['id'])) {
                    $data['mautic_contact_id'] = (int)$lead['id'];
                }
            }
            
            // Fallback: Tenta extrair de 'results' (dados brutos do formulário)
            $results = $event['submission']['results'] ?? $event['results'] ?? null;
            if ($results) {
                // Mapeamento manual de campos comuns de formulário
                if (empty($data['email'])) $data['email'] = $results['email'] ?? null;
                if (empty($data['phone'])) $data['phone'] = $results['phone'] ?? $results['telefono'] ?? $results['movil'] ?? null;
                if (empty($data['name']))  $data['name']  = $results['firstname'] ?? $results['nombre'] ?? '';
            }
        }

        // 2. Tenta extrair de eventos de Lead (create/update)
        // mautic.lead_post_save_new ou mautic.lead_post_save_update
        $leadEvent = $payload['mautic.lead_post_save_new'][0]['lead'] 
                  ?? $payload['mautic.lead_post_save_update'][0]['lead'] 
                  ?? null;

        if ($leadEvent && isset($leadEvent['fields']['core'])) {
             $fields = $leadEvent['fields']['core'];
             
             $data['email'] = $fields['email']['value'] ?? null;
             $data['phone'] = $fields['phone']['value'] ?? $fields['mobile']['value'] ?? null;
             
             $firstname = $fields['firstname']['value'] ?? '';
             $lastname = $fields['lastname']['value'] ?? '';
             $data['name'] = trim("$firstname $lastname");
             
             $data['company_name'] = $fields['company']['value'] ?? null;
             
             if (isset($leadEvent['id'])) {
                $data['mautic_contact_id'] = (int)$leadEvent['id'];
             }
        }

        return $data;
    }

    private function extractContactId(array $payload): ?int
    {
        $id = $payload['contact']['id']
            ?? $payload['mautic.form_on_submit'][0]['contact']['id']
            ?? null;
        return $id ? (int)$id : null;
    }

    private function extractFormId(array $payload): ?int
    {
        // Tenta extrair ID do formulário de várias estruturas possíveis
        $id = $payload['form']['id'] 
            ?? $payload['mautic.form_on_submit'][0]['form']['id'] 
            ?? $payload['mautic.form_on_submit'][0]['submission']['form']['id'] 
            ?? null;
            
        return $id ? (int)$id : null;
    }
}