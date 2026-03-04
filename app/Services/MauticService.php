<?php
// FILE: app/Services/MauticService.php
// Cliente HTTP para la API de Mautic v2

namespace App\Services;

use App\Models\Integration;

class MauticService
{
    private array $config;
    private ?string $accessToken = null;

    public function __construct()
    {
        $integration = new Integration();
        $this->config = $integration->getConfig('mautic');
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['base_url']) && !empty($this->config['client_id']);
    }

    /**
     * Obtiene un contacto por ID de Mautic usando Basic Auth o Bearer token
     */
    public function getContact(int $contactId): ?array
    {
        if (!$this->isConfigured())
            return null;

        $url = rtrim($this->config['base_url'], '/') . "/api/contacts/{$contactId}";
        $result = $this->httpGet($url);

        return $result['contact'] ?? null;
    }

    /**
     * Extrae campos útiles de un contacto de Mautic
     */
    public function extractLeadData(array $mauticContact): array
    {
        $fields = $mauticContact['fields']['all'] ?? $mauticContact['fields']['core'] ?? [];
        return [
            'name' => trim(($fields['firstname'] ?? '') . ' ' . ($fields['lastname'] ?? '')),
            'email' => $fields['email'] ?? null,
            'phone' => $fields['phone'] ?? $fields['mobile'] ?? null,
            'company_name' => $fields['company'] ?? null,
        ];
    }

    private function httpGet(string $url, array $headers = []): ?array
    {
        $defaultHeaders = [
            'Accept: application/json',
        ];

        // Si hay client_id y client_secret usar Basic Auth
        if (!empty($this->config['client_id']) && !empty($this->config['client_secret'])) {
            $creds = base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']);
            $defaultHeaders[] = "Authorization: Basic {$creds}";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400)
            return null;

        return json_decode($response, true);
    }

    public function getEnabledFormIds(): array
    {
        $ids = $this->config['form_ids'] ?? [];
        return is_array($ids) ? array_map('intval', $ids) : [];
    }
}