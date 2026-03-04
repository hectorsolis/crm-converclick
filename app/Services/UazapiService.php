<?php
// FILE: app/Services/UazapiService.php
// Cliente HTTP para uazapiGO V2

namespace App\Services;

use App\Models\Integration;

class UazapiService
{
    private array $config;

    public function __construct()
    {
        $integration = new Integration();
        $this->config = $integration->getConfig('uazapi');
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['base_url']) && !empty($this->config['instance_token']);
    }

    /**
     * Registra el webhook de la instancia en uazapiGO
     * POST /webhook con header 'token'
     */
    public function registerWebhook(string $crmUrl): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'uazapi no configurado.'];
        }

        $url = rtrim($this->config['base_url'], '/') . '/webhook';
        $payload = [
            'url' => $crmUrl . '/integrations/uazapi/webhook',
            'events' => ['messages', 'connection'],
            'excludeMessages' => ['wasSentByApi'],
        ];

        $result = $this->httpPost($url, $payload, $this->config['instance_token']);
        return $result ?? ['success' => false, 'message' => 'Sin respuesta de uazapi'];
    }

    /**
     * Obtiene info de la instancia (para verificar conexión)
     */
    public function getInstanceInfo(): ?array
    {
        if (!$this->isConfigured())
            return null;
        $url = rtrim($this->config['base_url'], '/') . '/instance/info';
        return $this->httpGet($url, $this->config['instance_token']);
    }

    private function httpGet(string $url, string $token): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'token: ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false)
            return null;
        return json_decode($response, true);
    }

    private function httpPost(string $url, array $body, string $token): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'token: ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false)
            return null;
        return json_decode($response, true);
    }
}