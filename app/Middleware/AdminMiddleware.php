<?php
// FILE: app/Middleware/AdminMiddleware.php
// Verifica que el usuario sea ADMIN

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;

class AdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        if (!Auth::isAdmin()) {
            http_response_code(403);
            View::render('errors/403', [], 'main');
            exit;
        }
    }
}