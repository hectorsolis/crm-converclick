<?php
// FILE: app/Controllers/AuthController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function loginForm(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $flash = $this->flash();
        $this->view('auth/login', ['flash' => $flash], 'auth');
    }

    public function login(Request $request): void
    {
        Csrf::verifyPost();

        $email = $request->post('email', '');
        $password = $request->post('password', '');

        if (empty($email) || empty($password)) {
            $this->withFlash('danger', 'Por favor ingresa email y contraseña.');
            $this->redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->authenticate($email, $password);

        if (!$user) {
            $this->withFlash('danger', 'Credenciales incorrectas o cuenta desactivada.');
            $this->redirect('/login');
        }

        Auth::login($user);
        $this->redirect('/dashboard');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}