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
            'enabled' => true,
            'events' => ['messages', 'connection'],
            'excludeMessages' => ['wasSentByApi'],
        ];

        $result = $this->httpPost($url, $payload, $this->config['instance_token']);
        return $result ?? ['success' => false, 'message' => 'Sin respuesta de uazapi'];
    }

    /**
     * Inicia a conexão da instância (gera QR code)
     * POST /instance/connect
     */
    public function connectInstance(): ?array
    {
        if (!$this->isConfigured())
            return null;
            
        $url = rtrim($this->config['base_url'], '/') . '/instance/connect';
        // Payload vazio para gerar QR (se passar phone, gera pairing code)
        // Usar stdClass vazio para garantir {} no JSON se necessário, ou array vazio se a lib aceitar
        return $this->httpPost($url, new \stdClass(), $this->config['instance_token']);
    }

    /**
     * Obtiene info de la instancia (para verificar conexión)
     * Usa /instance/status que devuelve todo (status, qr, jid)
     */
    public function getInstanceInfo(): ?array
    {
        if (!$this->isConfigured())
            return null;
            
        $url = rtrim($this->config['base_url'], '/') . '/instance/status';
        
        return $this->httpGet($url, $this->config['instance_token']);
    }

    /**
     * Obtiene el código QR para conexión
     * (Mantido para compatibilidade, mas getInstanceInfo já traz o QR se desconectado)
     */
    public function getQrCode(): ?array
    {
        // Reutiliza getInstanceInfo pois ele já traz o QR code no campo 'qrcode'
        $info = $this->getInstanceInfo();
        if ($info && isset($info['instance']['qrcode']) && !empty($info['instance']['qrcode'])) {
            return ['base64' => $info['instance']['qrcode']];
        }
        return null;
    }

    /**
     * Envía mensaje de texto
     * POST /send/text
     */
    public function sendMessage(string $phone, string $message): ?array
    {
        if (!$this->isConfigured())
            return null;

        // Limpar número (remover + e espaços)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Novo endpoint conforme documentação
        $url = rtrim($this->config['base_url'], '/') . '/send/text';
        
        $payload = [
            'number' => $phone,
            'text' => $message,
            'delay' => 1200,
            'linkPreview' => true
        ];

        return $this->httpPost($url, $payload, $this->config['instance_token']);
    }

    private function httpGet(string $url, string $token): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'token: ' . $token,
                'apikey: ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            error_log("Uazapi GET Error [$httpCode] URL: $url Response: " . ($response ?: $curlError));
            return null;
        }
        
        return json_decode($response, true);
    }

    protected function httpPost(string $url, $data, string $token): ?array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // json_encode aceita qualquer tipo (array ou objeto)
        $jsonData = json_encode($data);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'token: ' . $token,
            'apikey: ' . $token
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($result === false || $httpCode >= 400) {
            error_log("Uazapi POST Error [$httpCode] URL: $url Response: " . ($result ?: curl_error($ch)));
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return json_decode($result, true);
    }
}