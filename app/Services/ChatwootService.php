<?php
// FILE: app/Services/ChatwootService.php
// Cliente HTTP para la API de Chatwoot

namespace App\Services;

use App\Models\Integration;

class ChatwootService
{
    private array $config;

    public function __construct()
    {
        $integration = new Integration();
        $this->config = $integration->getConfig('chatwoot');
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['base_url']) && !empty($this->config['api_access_token']);
    }

    // Aquí se pueden agregar métodos para interactuar con la API de Chatwoot
    // como crear contactos, conversaciones, etc.
}
