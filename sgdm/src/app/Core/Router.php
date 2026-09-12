<?php
/**
 * Router.php
 * Enrutador propio, minimalista, sin dependencias externas. Traduce
 * (método HTTP + ruta) en una llamada a [Controlador, acción], siguiendo
 * la responsabilidad del Controlador dentro del patrón MVC: "recibir las
 * solicitudes... y seleccionar la respuesta correspondiente".
 *
 * Las rutas con parámetros usan la sintaxis {nombre}, por ejemplo:
 *   $router->get('/torneos/{codigo}', [TorneoController::class, 'show']);
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $path);
        $this->routes[] = [$method, "#^{$regex}$#", $handler];
    }

    public function dispatch(string $method, string $requestUri): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                [$controllerClass, $action] = $handler;
                $controller = new $controllerClass();
                $controller->$action(...$matches);
                return;
            }
        }

        http_response_code(404);
        (new ErrorController())->notFound();
    }
}
