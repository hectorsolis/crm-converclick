<?php
// FILE: app/Controllers/SettingsController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireAdmin();
        $settings = (new Setting())->all();
        $this->view('settings/index', [
            'settings' => $settings,
            'flash' => $this->flash(),
            'user' => Auth::user(),
            'activeMenu' => 'settings',
            'timezones' => $this->getTimezones(),
        ]);
    }

    public function save(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();

        $settingModel = new Setting();
        $allowed = ['app_name', 'timezone', 'primary_color', 'logo_text'];

        foreach ($allowed as $key) {
            $value = $request->post($key);
            if ($value !== null) {
                $settingModel->set($key, trim($value));
            }
        }

        // Actualizar timezone global si cambia
        if ($request->post('timezone')) {
            date_default_timezone_set($request->post('timezone'));
        }

        $this->withFlash('success', 'Configuración guardada.', '/settings');
    }

    private function getTimezones(): array
    {
        return [
            'America/Santiago' => 'Chile (Santiago) — UTC-3/UTC-4',
            'America/Argentina/Buenos_Aires' => 'Argentina (Buenos Aires) — UTC-3',
            'America/Bogota' => 'Colombia (Bogotá) — UTC-5',
            'America/Lima' => 'Perú (Lima) — UTC-5',
            'America/Mexico_City' => 'México (Ciudad de México) — UTC-6',
            'America/New_York' => 'EE.UU. Este — UTC-5',
            'UTC' => 'UTC',
            'Europe/Madrid' => 'España (Madrid) — UTC+1',
        ];
    }
}