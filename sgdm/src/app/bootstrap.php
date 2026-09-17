<?php
/**
 * bootstrap.php
 * Punto de arranque de la aplicación: autoloader propio (sin Composer, a
 * propósito, para que el proyecto no dependa de conexión a internet
 * durante el build de la imagen) y utilidades comunes.
 *
 * Convención: cada clase vive en un único archivo "NombreClase.php"
 * dentro de una de las carpetas registradas abajo. No se usan namespaces
 * para mantener el mapeo clase -> archivo lo más directo posible.
 */

declare(strict_types=1);

// Buffer de salida: si una excepción ocurre a mitad de una respuesta que
// ya empezó a imprimir HTML, mostrarLoQueFallo() puede descartar ese HTML
// parcial (ob_end_clean()) y mostrar la pantalla de error limpia, en vez
// de una página a medio armar seguida del error.
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'production' ? '0' : '1');

spl_autoload_register(function (string $class): void {
    static $paths = [
        __DIR__ . '/Config/',
        __DIR__ . '/Core/',
        __DIR__ . '/Formatos/',
        __DIR__ . '/Models/',
        __DIR__ . '/Controllers/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

/**
 * Manejo centralizado de errores (RNF: "mensajes de error claros y
 * fáciles de comprender para el usuario"). Antes de esta corrección,
 * cualquier excepción sin capturar (por ejemplo, que MySQL todavía no
 * esté listo, o una consulta mal armada) terminaba mostrando el error
 * crudo de PHP directamente en pantalla. Ahora, tanto las excepciones
 * como los errores fatales de PHP pasan por el mismo punto único:
 * mostrarLoQueFalló(), que registra el detalle técnico en el log de
 * Apache (para poder diagnosticarlo) y le muestra a quien esté usando
 * el sitio una pantalla prolija en su lugar.
 *
 * A propósito NO se convierten los warnings/avisos de PHP en
 * excepciones: eso arriesgaría con romper páginas que hoy funcionan bien
 * por un aviso menor y sin importancia. Este manejo cubre los errores
 * que de verdad impiden responder la petición.
 */
function mostrarLoQueFallo(string $mensajeTecnico, string $archivo, int $linea): void
{
    error_log("[SGDM] Error no controlado: {$mensajeTecnico} en {$archivo}:{$linea}");

    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    $detalleTecnico = getenv('APP_ENV') === 'production'
        ? null
        : "{$mensajeTecnico} — {$archivo}:{$linea}";

    $usuarioActual = null;
    try {
        $usuarioActual = class_exists('Auth') ? Auth::user() : null;
    } catch (Throwable $ignorada) {
        $usuarioActual = null;
    }

    require __DIR__ . '/Views/errors/500.php';
    exit;
}

set_exception_handler(function (Throwable $e): void {
    mostrarLoQueFallo($e->getMessage(), $e->getFile(), $e->getLine());
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    $esFatal = $error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
    if ($esFatal) {
        mostrarLoQueFallo($error['message'], $error['file'], $error['line']);
    }
});

Auth::start();
