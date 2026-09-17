<?php
/**
 * Controller.php
 * Controlador base del que heredan todos los controladores concretos.
 * Centraliza cómo se renderiza una vista y cómo se responde en JSON,
 * para que ningún controlador concreto mezcle código de presentación
 * (echo/HTML suelto) con lógica de negocio — tal como pide el
 * requerimiento técnico del proyecto.
 */
abstract class Controller
{
    /**
     * Renderiza un archivo de app/Views/{$view}.php pasándole $data como
     * variables locales. La vista es responsable únicamente de mostrar
     * esos datos: no consulta la base ni decide permisos.
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $usuarioActual = Auth::user(); // disponible en toda vista, para la navegación
        $notificacionesNoLeidas = $usuarioActual ? Notificacion::cantidadNoLeidas((int) $usuarioActual['id']) : 0;
        require __DIR__ . "/../Views/{$view}.php";
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }

    /**
     * Lee el cuerpo de la petición como JSON (usado por los endpoints que
     * el frontend llama con fetch(), por ejemplo el asistente de creación
     * de torneo).
     */
    protected function input(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
