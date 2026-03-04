<?php
// FILE: app/Controllers/IntegrationController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\UazapiService;

class IntegrationController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireAdmin();

        $integration = new Integration();
        $mautic = $integration->getConfig('mautic');
        $uazapi = $integration->getConfig('uazapi');
        $chatwoot = $integration->getConfig('chatwoot');
        $logModel = new IntegrationLog();
        $logs = $logModel->recent(null, 30);

        $this->view('integrations/index', [
            'mautic' => $mautic,
            'uazapi' => $uazapi,
            'chatwoot' => $chatwoot,
            'logs' => $logs,
            'flash' => $this->flash(),
            'user' => Auth::user(),
            'activeMenu' => 'integrations',
        ]);
    }

    public function saveMautic(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();

        $formIds = $request->post('form_ids', '');
        $ids = [];
        foreach (explode(',', $formIds) as $id) {
            $id = trim($id);
            if (is_numeric($id))
                $ids[] = (int)$id;
        }

        $config = [
            'base_url' => rtrim($request->post('base_url', ''), '/'),
            'client_id' => $request->post('client_id', ''),
            'client_secret' => $request->post('client_secret', ''),
            'form_ids' => $ids,
            'webhook_secret' => $request->post('webhook_secret', ''),
            'is_active' => 1,
        ];

        (new Integration())->saveConfig('mautic', $config);
        $this->withFlash('success', 'Configuración de Mautic guardada.', '/integrations');
    }

    public function saveUazapi(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();

        $config = [
            'base_url' => rtrim($request->post('base_url', ''), '/'),
            'instance_token' => $request->post('instance_token', ''),
            'admin_token' => $request->post('admin_token', ''),
            'webhook_secret' => $request->post('webhook_secret', ''),
            'is_active' => 1,
        ];

        (new Integration())->saveConfig('uazapi', $config);
        $this->withFlash('success', 'Configuración de uazapiGO guardada.', '/integrations');
    }

    public function saveChatwoot(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();

        $config = [
            'base_url' => rtrim($request->post('base_url', ''), '/'),
            'api_access_token' => $request->post('api_access_token', ''),
            'account_id' => (int)$request->post('account_id', 0),
            'inbox_identifier' => $request->post('inbox_identifier', ''),
            'webhook_secret' => $request->post('webhook_secret', ''),
            'is_active' => 1,
        ];

        (new Integration())->saveConfig('chatwoot', $config);
        $this->withFlash('success', 'Configuración de Chatwoot guardada.', '/integrations');
    }

    public function registerUazapiWebhook(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();

        $service = new UazapiService();
        $result = $service->registerWebhook(APP_URL);

        if (isset($result['success']) && $result['success'] === false) {
            $this->withFlash('danger', 'Error al registrar webhook: ' . ($result['message'] ?? 'Sin respuesta.'), '/integrations');
        }
        else {
            (new IntegrationLog())->log('uazapi', 'out', 'webhook_register',
                json_encode($result), 'ok', 'Webhook registrado desde panel'
            );
            $this->withFlash('success', 'Webhook registrado en uazapiGO correctamente.', '/integrations');
        }
    }

    public function testUazapi(Request $request): void
    {
        Auth::requireAdmin();
        $service = new UazapiService();
        $info = $service->getInstanceInfo();
        $this->json($info ?? ['error' => 'No se pudo conectar a uazapiGO.']);
    }
}