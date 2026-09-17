<?php
/**
 * AvisoController.php
 * Cartelera de avisos del organizador sobre su torneo (RF: "apartado de
 * avisos donde el organizador puede actualizar sobre cosas del torneo...
 * y le lleguen notificaciones a los participantes"). Publicar un aviso
 * guarda el mensaje en la cartelera pública del torneo (Aviso.php) y
 * además crea una notificación privada para cada participante activo
 * (Notificacion::porAviso()) — son dos cosas relacionadas pero
 * distintas, ver el comentario de la tabla `avisos` en 01_schema.sql.
 *
 * Mismo criterio de permisos que el resto de la gestión del torneo:
 * solo su organizador o un admin general.
 */
class AvisoController extends Controller
{
    public function store(string $codigo): void
    {
        $torneo = Torneo::porCodigoPublico(strtoupper($codigo));
        if (!$torneo) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            exit;
        }
        Auth::requireOrganizadorOAdmin($torneo);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $_SESSION['torneo_error'] = 'Tu sesión de formulario venció. Volvé a intentarlo.';
            $this->redirect("/torneos/{$codigo}");
        }

        $mensaje = trim((string) ($_POST['mensaje'] ?? ''));
        if ($mensaje === '') {
            $_SESSION['torneo_error'] = 'Escribí un mensaje antes de publicar el aviso.';
            $this->redirect("/torneos/{$codigo}");
        }

        Aviso::create(['torneo_id' => $torneo['id'], 'mensaje' => $mensaje]);
        Notificacion::porAviso($torneo, Participante::delTorneo((int) $torneo['id']), $mensaje);
        Auditoria::registrar((int) Auth::user()['id'], 'publicar_aviso', 'torneos', (int) $torneo['id'], $mensaje);

        $_SESSION['torneo_mensaje'] = 'Aviso publicado.';
        $this->redirect("/torneos/{$codigo}");
    }
}
