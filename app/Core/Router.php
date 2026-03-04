<?php
// FILE: app/Core/Router.php
// Micro-router con soporte para GET, POST y parámetros (:id)

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
            'pattern' => $this->buildPattern($path),
        ];
    }

    private function buildPattern(string $path): string
    {
        // Convierte :param en grupo de captura
        $pattern = preg_replace('/\/:([a-zA-Z_]+)/', '/(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method)
                continue;
            if (!preg_match($route['pattern'], $uri, $matches))
                continue;

            // Extraer parámetros nombrados
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setParams($params);

            // Ejecutar middlewares
            foreach ($route['middleware'] as $middlewareClass) {
                $mw = new $middlewareClass();
                $mw->handle($request);
            }

            // Resolver controller@action
            [$controllerClass, $action] = explode('@', $route['handler']);
            $fullClass = "App\\Controllers\\{$controllerClass}";

            if (!class_exists($fullClass)) {
                $this->abort(500, "Controlador {$controllerClass} no encontrado.");
                return;
            }

            $controller = new $fullClass();
            if (!method_exists($controller, $action)) {
                $this->abort(500, "Acción {$action} no encontrada en {$controllerClass}.");
                return;
            }

            $controller->$action($request);
            return;
        }

        $this->abort(404, 'Página no encontrada.');
    }

    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Error {$code}</title>
        <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8f9fa}
        .box{text-align:center;padding:2rem;background:#fff;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.1)}
        h1{color:#E63946}p{color:#666}</style></head>
        <body><div class='box'><h1>Error {$code}</h1><p>{$message}</p>
        <a href='/dashboard' style='color:#E63946'>← Volver al inicio</a></div></body></html>";
    }
}