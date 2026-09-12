<?php
/**
 * ErrorController.php
 * Respuestas de error de la aplicación (404, 403). Centralizadas acá para
 * que ningún controlador tenga que preocuparse por el HTML de estas
 * páginas.
 */
class ErrorController extends Controller
{
    public function notFound(): void
    {
        require __DIR__ . '/../Views/errors/404.php';
    }
}
