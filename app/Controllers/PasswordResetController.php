<?php
// FILE: app/Controllers/PasswordResetController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\User;
use App\Models\PasswordReset;
use App\Services\Mailer;

class PasswordResetController extends Controller
{
    public function showForgotForm(): void
    {
        $this->view('auth/forgot-password', [
            'pageTitle' => '¿Olvidó sua clave?',
            'flash' => $this->flash()
        ]);
    }

    public function sendResetLink(Request $request): void
    {
        $email = strtolower(trim($request->post('email', '')));
        
        if (empty($email)) {
            $this->withFlash('danger', 'Por favor, ingrese su correo electrónico.', '/forgot-password');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);
        $resetModel = new PasswordReset();

        // Rate limiting: máx 3 tentativas por hora
        if ($resetModel->countRecentRequests($email) >= 3) {
            $this->withFlash('danger', 'Demasiadas solicitudes. Por favor, intente de nuevo en una hora.', '/forgot-password');
        }

        if ($user) {
            $token = $resetModel->createToken($email);
            $mailer = new Mailer();
            
            // Simulação de fila assíncrona (envio direto por enquanto, mas preparado para fila)
            // Em um ambiente real, aqui adicionaríamos à tabela de 'jobs' ou similar
            $mailer->sendPasswordReset($email, $token);
        }

        // Por segurança, mostramos a mesma mensagem mesmo se o email não existir (evita timing attacks e user enumeration)
        $this->withFlash('success', 'Si el correo existe en nuestro sistema, recibirá un enlace para restablecer su contraseña en unos minutos.', '/login');
    }

    public function showResetForm(Request $request): void
    {
        $token = $request->get('token', '');
        
        if (empty($token)) {
            $this->withFlash('danger', 'Enlace inválido o expirado.', '/login');
        }

        $resetModel = new PasswordReset();
        $reset = $resetModel->findByToken($token);

        if (!$reset) {
            $this->withFlash('danger', 'El enlace de recuperación es inválido o ha expirado (validez de 24 horas).', '/login');
        }

        $this->view('auth/reset-password', [
            'pageTitle' => 'Restablecer Contraseña',
            'token' => $token,
            'flash' => $this->flash()
        ]);
    }

    public function resetPassword(Request $request): void
    {
        $token = $request->post('token', '');
        $password = $request->post('password', '');
        $confirm = $request->post('password_confirmation', '');

        if (empty($token)) {
            $this->withFlash('danger', 'Token inválido.', '/login');
        }

        $resetModel = new PasswordReset();
        $reset = $resetModel->findByToken($token);

        if (!$reset) {
            $this->withFlash('danger', 'El enlace ha expirado.', '/login');
        }

        // Validação de força da senha
        if (!$this->isStrongPassword($password)) {
            $this->withFlash('danger', 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y caracteres especiales.', '/reset-password?token=' . $token);
        }

        if ($password !== $confirm) {
            $this->withFlash('danger', 'Las contraseñas no coinciden.', '/reset-password?token=' . $token);
        }

        $userModel = new User();
        $user = $userModel->findByEmail($reset['email']);

        if ($user) {
            $userModel->update($user['id'], ['password' => $password]);
            $resetModel->deleteByEmail($reset['email']);
            $this->withFlash('success', 'Su contraseña ha sido actualizada correctamente. Ya puede iniciar sesión.', '/login');
        } else {
            $this->withFlash('danger', 'Usuario no encontrado.', '/login');
        }
    }

    private function isStrongPassword(string $password): bool
    {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        if (!preg_match('/[^A-Za-z0-9]/', $password)) return false;
        return true;
    }
}
