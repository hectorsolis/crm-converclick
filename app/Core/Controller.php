<?php
// FILE: app/Core/Controller.php
// Base de todos los controladores

namespace App\Core;

class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'main'): void
    {
        View::render($template, $data, $layout);
    }

    protected function json(mixed $data, int $code = 200): void
    {
        View::json($data, $code);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        $this->redirect($ref);
    }

    protected function withFlash(string $type, string $message, string $url = ''): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        if ($url)
            $this->redirect($url);
    }

    protected function flash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    protected function db(): Database
    {
        return Database::getInstance();
    }
}