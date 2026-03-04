<?php
// FILE: app/Controllers/UserController.php
// Solo accesible para ADMIN

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireAdmin();
        $users = (new User())->all();
        $this->view('users/index', [
            'users' => $users,
            'flash' => $this->flash(),
            'user' => Auth::user(),
            'activeMenu' => 'users',
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireAdmin();
        $this->view('users/create', [
            'flash' => $this->flash(),
            'user' => Auth::user(),
            'activeMenu' => 'users',
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();

        $name = trim($request->post('name', ''));
        $email = trim($request->post('email', ''));
        $password = $request->post('password', '');
        $role = $request->post('role', 'vendedor');

        if (empty($name) || empty($email) || empty($password)) {
            $this->withFlash('danger', 'Todos los campos son obligatorios.', '/users/create');
        }
        if (strlen($password) < 8) {
            $this->withFlash('danger', 'La contraseña debe tener al menos 8 caracteres.', '/users/create');
        }

        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            $this->withFlash('danger', 'Ya existe un usuario con ese email.', '/users/create');
        }

        $userModel->create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role]);
        $this->withFlash('success', 'Usuario creado correctamente.', '/users');
    }

    public function edit(Request $request): void
    {
        Auth::requireAdmin();
        $id = (int)$request->param('id');
        $userData = (new User())->findById($id);
        if (!$userData) {
            $this->redirect('/users');
        }

        $this->view('users/edit', [
            'editUser' => $userData,
            'flash' => $this->flash(),
            'user' => Auth::user(),
            'activeMenu' => 'users',
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();

        $id = (int)$request->param('id');
        $userModel = new User();
        $existing = $userModel->findById($id);
        if (!$existing) {
            $this->redirect('/users');
        }

        $data = [
            'name' => trim($request->post('name', '')),
            'email' => trim($request->post('email', '')),
            'role' => $request->post('role', 'vendedor'),
        ];
        if ($request->post('password')) {
            if (strlen($request->post('password')) < 8) {
                $this->withFlash('danger', 'La contraseña debe tener al menos 8 caracteres.', "/users/{$id}/edit");
            }
            $data['password'] = $request->post('password');
        }

        $userModel->update($id, $data);
        $this->withFlash('success', 'Usuario actualizado.', '/users');
    }

    public function toggleActive(Request $request): void
    {
        Auth::requireAdmin();
        Csrf::verifyPost();
        $id = (int)$request->param('id');
        // No permitir desactivar el propio usuario
        if ($id === Auth::id()) {
            $this->withFlash('danger', 'No puedes desactivar tu propia cuenta.', '/users');
        }
        (new User())->toggleActive($id);
        $this->withFlash('success', 'Estado del usuario actualizado.', '/users');
    }
}