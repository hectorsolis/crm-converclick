<?php
// FILE: app/Middleware/AuthMiddleware.php
// Verifica que el usuario tenga sesión activa

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }
}