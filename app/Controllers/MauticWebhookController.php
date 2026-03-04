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
        if (!empty($enabledForms) && $formId && !in_array($formId, $enabledForms)) {
            $logModel->log('mautic', 'in', 'form_blocked', json_encode($payload), 'error',
                "Form ID {$formId} no está habilitado", $ip);
            View::json(['status' => 'ignored', 'reason' => 'form_not_enabled']);
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
        $data = [];

        // Estructura típica de Mautic webhook: mautic.form_on_submit
        $contact = $payload['contact'] ?? $payload['mautic.form_on_submit'][0]['contact'] ?? null;
        $results = $payload['results'] ?? $payload['mautic.form_on_submit'][0]['results'] ?? null;

        if ($contact) {
            $fields = $contact['fields']['all'] ?? $contact['fields']['core'] ?? [];
            $data['name'] = trim(($fields['firstname'] ?? '') . ' ' . ($fields['lastname'] ?? ''));
            $data['email'] = $fields['email'] ?? null;
            $data['phone'] = $fields['phone'] ?? $fields['mobile'] ?? null;
            $data['company_name'] = $fields['company'] ?? null;
            if (isset($contact['id']))
                $data['mautic_contact_id'] = (int)$contact['id'];
        }

        // Campos de form results
        if ($results) {
            $data['email'] = $data['email'] ?? $results['email'] ?? null;
            $data['phone'] = $data['phone'] ?? $results['phone'] ?? $results['telefono'] ?? null;
            if (empty($data['name'])) {
                $data['name'] = $results['name'] ?? $results['nombre'] ?? '';
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
        $id = $payload['form']['id']
            ?? $payload['mautic.form_on_submit'][0]['form']['id']
            ?? null;
        return $id ? (int)$id : null;
    }
}