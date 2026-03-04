<?php
// FILE: app/Helpers/LeadDeduplicator.php
// Lógica de deduplicación de leads por email y/o teléfono

namespace App\Helpers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\IntegrationLog;

class LeadDeduplicator
{
    private Lead $leadModel;
    private LeadActivity $activityModel;
    private IntegrationLog $logModel;

    public function __construct()
    {
        $this->leadModel = new Lead();
        $this->activityModel = new LeadActivity();
        $this->logModel = new IntegrationLog();
    }

    /**
     * Resultado: ['action' => 'created'|'updated'|'conflict', 'lead_id' => int, 'lead' => array]
     *
     * Lógica:
     * 1. Si email existe → update ese lead
     * 2. Else si teléfono existe → update ese lead
     * 3. Else crear nuevo
     * 4. Si email pertenece a lead A y teléfono a lead B → conflicto
     */
    public function process(array $payload, string $source, string $sourceDetail = ''): array
    {
        $email = !empty($payload['email']) ? strtolower(trim($payload['email'])) : null;
        $phone = !empty($payload['phone']) ?PhoneNormalizer::normalize($payload['phone']) : null;

        $leadByEmail = $email ? $this->leadModel->findByEmail($email) : null;
        $leadByPhone = $phone ? $this->leadModel->findByPhone($phone) : null;

        // ── Conflicto: email y teléfono apuntan a leads distintos ──
        if ($leadByEmail && $leadByPhone && $leadByEmail['id'] !== $leadByPhone['id']) {
            $this->handleConflict($leadByEmail, $leadByPhone, $payload, $source);
            // Actualizar el lead encontrado por email (prioritario)
            $this->mergeData($leadByEmail['id'], $payload, $source, $sourceDetail);
            return ['action' => 'conflict', 'lead_id' => $leadByEmail['id'], 'lead' => $leadByEmail];
        }

        // ── Existe por email ──
        if ($leadByEmail) {
            // Si llega teléfono nuevo que el lead no tenía, completarlo
            if ($phone && empty($leadByEmail['phone'])) {
                $payload['phone'] = $phone;
            }
            $this->mergeData($leadByEmail['id'], $payload, $source, $sourceDetail);
            return ['action' => 'updated', 'lead_id' => $leadByEmail['id'], 'lead' => $leadByEmail];
        }

        // ── Existe por teléfono ──
        if ($leadByPhone) {
            // Si llega email nuevo que el lead no tenía, completarlo
            if ($email && empty($leadByPhone['email'])) {
                $payload['email'] = $email;
            }
            $this->mergeData($leadByPhone['id'], $payload, $source, $sourceDetail);
            return ['action' => 'updated', 'lead_id' => $leadByPhone['id'], 'lead' => $leadByPhone];
        }

        // ── Lead nuevo ──
        $payload['source'] = $source;
        $payload['source_detail'] = $sourceDetail;
        $leadId = $this->leadModel->create($payload);
        $this->activityModel->log(
            $leadId,
            'created',
            "Lead creado desde {$source}" . ($sourceDetail ? " ({$sourceDetail})" : ''),
            null
        );
        $newLead = $this->leadModel->findById($leadId);
        return ['action' => 'created', 'lead_id' => $leadId, 'lead' => $newLead];
    }

    private function mergeData(int $leadId, array $payload, string $source, string $sourceDetail): void
    {
        $updateData = [];

        // Solo actualizar campos que llegaron con valor
        $mergeable = ['name', 'email', 'phone', 'company_name', 'company_industry', 'company_size'];
        foreach ($mergeable as $field) {
            if (!empty($payload[$field])) {
                $updateData[$field] = $payload[$field];
            }
        }

        // Agregar o acumular source_detail
        $updateData['source_detail'] = $sourceDetail;
        $updateData['source_timestamp'] = $payload['source_timestamp'] ?? date('Y-m-d H:i:s');

        $this->leadModel->update($leadId, $updateData);

        $this->activityModel->log(
            $leadId,
            'source_added',
            "Nuevo ingreso desde {$source}" . ($sourceDetail ? " ({$sourceDetail})" : ''),
            null,
        ['source' => $source, 'detail' => $sourceDetail]
        );
    }

    private function handleConflict(array $leadA, array $leadB, array $payload, string $source): void
    {
        $detail = "Conflicto detectado por {$source}: email apunta a lead #{$leadA['id']} y teléfono a lead #{$leadB['id']}";

        // Marcar ambos leads con conflicto
        $this->leadModel->update($leadA['id'], ['conflict_flag' => 1, 'conflict_detail' => $detail]);
        $this->leadModel->update($leadB['id'], ['conflict_flag' => 1, 'conflict_detail' => $detail]);

        $this->activityModel->log($leadA['id'], 'conflict_flagged', $detail);
        $this->activityModel->log($leadB['id'], 'conflict_flagged', $detail);
    }
}