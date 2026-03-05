<?php
// FILE: app/Controllers/DashboardWhatsAppController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Services\UazapiService;
use App\Models\Integration;
use App\Models\User;
use App\Services\Mailer;

class DashboardWhatsAppController extends Controller
{
    private UazapiService $uazapi;
    private Integration $integrationModel;

    public function __construct()
    {
        Auth::requireAuth();
        $this->uazapi = new UazapiService();
        $this->integrationModel = new Integration();
    }

    /**
     * Endpoint para obtener estado en tiempo real (polling)
     * GET /dashboard/whatsapp/status
     */
    public function status(Request $request): void
    {
        try {
            // Simulação para testes de alertas (Query param: ?simulate=disconnected|reconnected)
            $simulate = $request->get('simulate');
            
            if ($simulate) {
                $info = [
                    'instance' => ['status' => 'connected', 'owner' => '56968659196'], // Default connected info
                    'status' => ['jid' => '56968659196@s.whatsapp.net']
                ];
                
                // Simular Desconexão
                if ($simulate === 'disconnected') {
                    $info['instance']['status'] = 'disconnected';
                } 
                // Simular Reconexão
                elseif ($simulate === 'reconnected') {
                    $info['instance']['status'] = 'connected';
                }
            } else {
                // Fluxo normal
                $info = $this->uazapi->getInstanceInfo();
            }

            $qrData = null;
            
            // Estado por defecto
            $status = 'disconnected';
            $phone = '';
            
            // La API uazapiGO V2 (endpoint /instance/status) devuelve:
            // { instance: { status: "connected"|"disconnected"|"connecting", qrcode: "data...", ... }, status: { jid: "..." } }
            
            if ($info && isset($info['instance']['status'])) {
                 $state = $info['instance']['status'];
                 
                 // Debug log (opcional, comentar em produção se muito verboso)
                 // (new \App\Models\IntegrationLog())->log('uazapi', 'out', 'status_check', json_encode($info), 'info', "Status API: $state");
                 
                 if ($state === 'connected') {
                     $status = 'connected';
                     // status.jid: 569...@s.whatsapp.net (prioridad)
                     // instance.owner: 569... (fallback)
                     $jid = $info['status']['jid'] ?? $info['instance']['owner'] ?? '';
                     $phone = explode(':', $jid)[0]; // Remover porta se houver
                     $phone = explode('@', $phone)[0];
                 } else {
                     // Si está disconnected o connecting
                     if ($state === 'connecting') {
                         $status = 'connecting';
                     }
                     
                     // Buscar QR code (viene directo en instance.qrcode como data URI base64)
                     if (!empty($info['instance']['qrcode'])) {
                         $qrData = $info['instance']['qrcode'];
                         $status = 'qr_ready';
                     } else if ($state === 'disconnected') {
                         // Si está desconectado y NO hay QR, solicitamos uno nuevo
                         // Solo si no estamos en simulación
                         if (!$simulate) {
                             $connectRes = $this->uazapi->connectInstance();
                             if ($connectRes && isset($connectRes['instance']['qrcode'])) {
                                 $qrData = $connectRes['instance']['qrcode'];
                                 $status = 'qr_ready';
                             }
                         }
                     }
                 }
            } else {
                // Error de conexión con API o instancia no encontrada
                // Se info for null, pode ser erro de rede ou config
                if ($info === null) {
                    $status = 'api_error'; // Status específico para erro de API
                } else {
                    $status = 'error';
                }
            }

            // Lógica de alertas (simplificada para este paso)
            $this->checkAlerts($status, $phone);

            $config = $this->getAlertConfig();

            // Limpar buffer de saída para evitar injeção de erros/whitespace no JSON
            if (ob_get_length()) ob_clean();

            View::json([
                'status' => $status,
                'phone' => $phone,
                'qr' => $qrData,
                'timestamp' => date('d-m-Y H:i:s'),
                'config' => [
                    'alert_email' => $config['email'],
                    'alert_phone' => $config['phone']
                ]
            ]);
        } catch (\Throwable $e) {
            error_log("DashboardWhatsAppController Error: " . $e->getMessage());
            if (ob_get_length()) ob_clean();
            View::json(['status' => 'server_error', 'message' => $e->getMessage()], 500);
        }
    }

    private function getAlertConfig(): array
    {
        $config = $this->integrationModel->getConfig('uazapi');
        return [
            'email' => $config['alert_email'] ?? 'hectorsolis@gmail.com',
            'phone' => $config['alert_phone'] ?? '+56968659196'
        ];
    }

    private function checkAlerts(string $currentStatus, string $phone): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Obtener configuración completa para leer estado anterior persistente
        $config = $this->integrationModel->getConfig('uazapi');
        
        // Estado anterior vem do DB (mais confiável que sessão) ou 'unknown'
        $lastStatus = $config['last_status'] ?? 'unknown';
        $lastAlertTime = $config['last_alert_time'] ?? 0;

        // Log para debug em produção
        if ($lastStatus !== $currentStatus) {
            error_log("WA Status Change (DB): $lastStatus -> $currentStatus (Phone: $phone)");
        }

        // Inicialización: Si es la primera vez (unknown), guardamos y salimos
        if ($lastStatus === 'unknown' && !isset($_GET['simulate'])) {
            $config['last_status'] = $currentStatus;
            $this->integrationModel->updateConfig('uazapi', $config);
            return;
        }

        // Si el estado no ha cambiado, no hacemos nada
        if ($lastStatus === $currentStatus && !isset($_GET['simulate'])) {
            return;
        }

        // Actualizar estado nuevo en DB
        // Nota: Solo actualizamos si NO es simulación, para no ensuciar o
        // se for simulação, tratamos diferente. 
        // Na verdade, a simulação deve ser isolada.
        if (!isset($_GET['simulate'])) {
            $config['last_status'] = $currentStatus;
            $this->integrationModel->updateConfig('uazapi', $config);
        }

        $alertEmail = $config['alert_email'] ?? '';
        $alertPhone = $config['alert_phone'] ?? '';

        $now = time();
        
        // Rate Limiting (15 min)
        if (!isset($_GET['simulate']) && ($now - $lastAlertTime) < 900) {
            error_log("WA Alert skipped due to rate limit. Last: $lastAlertTime, Now: $now");
            return;
        }

        $mailer = new Mailer();
        $uazapi = new UazapiService();
        $timestamp = date('d-m-Y H:i');

        // Determinar transições
        $isDisconnected = ($currentStatus !== 'connected');
        $wasConnected = ($lastStatus === 'connected');

        // FORCE SIMULATION LOGIC
        if (isset($_GET['simulate'])) {
            $sim = $_GET['simulate'];
            // Reset timer local (não salva no DB na simulação para não afetar prod)
            
            if ($sim === 'disconnected') {
                $isDisconnected = true;
                $wasConnected = true; 
            } elseif ($sim === 'reconnected') {
                $isDisconnected = false;
                $wasConnected = false; 
                $currentStatus = 'connected'; 
            }
            
            error_log("Simulate Alert LOGIC: Sim=$sim, WasConn=" . ($wasConnected?1:0) . ", IsDisc=" . ($isDisconnected?1:0));
        }

        // ALERTA DESCONEXÃO
        if ($wasConnected && $isDisconnected) {
            $subject = "Alerta: WhatsApp desconectado - CRM Converclick";
            $body = "
                <h2>Alerta de Desconexión WhatsApp</h2>
                <p>La instancia de WhatsApp se ha desconectado.</p>
                <ul>
                    <li><strong>ID Instancia:</strong> (Ver config)</li>
                    <li><strong>Teléfono Afectado:</strong> {$phone}</li>
                    <li><strong>Fecha:</strong> {$timestamp}</li>
                    <li><strong>URL CRM:</strong> " . APP_URL . "</li>
                </ul>
                <p>Por favor ingrese al Dashboard para reconectar.</p>
            ";

            if ($alertEmail) {
                $mailer->send($alertEmail, $subject, $body);
            }
            
            // Atualizar timer no DB
            if (!isset($_GET['simulate'])) {
                $config['last_alert_time'] = $now;
                $this->integrationModel->updateConfig('uazapi', $config);
            }
        }

        // ALERTA RECONEXÃO
        if (!$wasConnected && $currentStatus === 'connected') {
            $message = "✅ *WhatsApp Reconectado*\n\n" .
                       "La conexión con el CRM se ha restablecido exitosamente.\n" .
                       "📅 Fecha: {$timestamp}\n" .
                       "📱 Número: {$phone}\n" .
                       "🔗 URL: " . APP_URL;

            // WhatsApp
            if ($alertPhone) {
                $res = $uazapi->sendMessage($alertPhone, $message);
                if (isset($_GET['simulate'])) {
                    error_log("Simulate WA Send: To=$alertPhone, Result=" . json_encode($res));
                } else {
                    error_log("Real WA Send: To=$alertPhone, Result=" . json_encode($res));
                }
            }
            
            // Email
            if ($alertEmail) {
                $mailer->send($alertEmail, "WhatsApp Reconectado - CRM Converclick", nl2br($message));
            }
            
            // Webhook
            $webhookResult = $uazapi->registerWebhook(APP_URL);
            if (isset($_GET['simulate'])) {
                 error_log("Reconnection: Webhook registered result=" . json_encode($webhookResult));
            }

            // Atualizar timer no DB
            if (!isset($_GET['simulate'])) {
                $config['last_alert_time'] = $now;
                $this->integrationModel->updateConfig('uazapi', $config);
            }
        }
    }
}
