<?php
// FILE: app/Core/View.php
// Motor de plantillas PHP con soporte de layouts anidados

namespace App\Core;

class View
{
    private static array $sections = [];
    private static ?string $currentSection = null;

    /**
     * Renderiza una vista con datos y layout opcional
     */
    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        // Extraer variables para la vista
        extract($data);

        // Capturar el contenido de la vista
        ob_start();
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $template) . '.php';
        if (!file_exists($viewFile)) {
            ob_end_clean();
            die("Vista no encontrada: {$viewFile}");
        }
        require $viewFile;
        $content = ob_get_clean();

        // Renderizar el layout
        $layoutFile = VIEW_PATH . '/layouts/' . $layout . '.php';
        if ($layout && file_exists($layoutFile)) {
            extract($data);
            require $layoutFile;
        }
        else {
            echo $content;
        }
    }

    /**
     * Renderiza solo el fragmento (sin layout) — para partials y AJAX
     */
    public static function partial(string $template, array $data = []): void
    {
        extract($data);
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $template) . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
    }

    /**
     * Inicia una sección nombrada
     */
    public static function startSection(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    /**
     * Termina la sección actual
     */
    public static function endSection(): void
    {
        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = null;
    }

    /**
     * Imprime el contenido de una sección
     */
    public static function yield (string $name, string $default = ''): void
    {
        echo self::$sections[$name] ?? $default;
    }

    /**
     * Escapa HTML para output seguro
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Retorna JSON para respuestas de API/webhook
     */
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}