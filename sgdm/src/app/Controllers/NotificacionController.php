<?php
/**
 * NotificacionController.php
 * Módulo de notificaciones (RF: "apartado de notificaciones... donde
 * pueda aceptar o rechazar" una invitación, además de los avisos de
 * torneo iniciado, avisos del organizador, y —para el organizador— que
 * alguien se sumó o que el torneo se completó).
 *
 * No hace falta validar organizador/admin acá: cada notificación ya
 * nace atada a un usuario_id puntual, y esa es la única identidad que
 * importa — cualquiera ve únicamente las suyas.
 */
class NotificacionController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $usuario = Auth::user();
        $notificaciones = Notificacion::deUsuario((int) $usuario['id']);
        Notificacion::marcarTodasLeidas((int) $usuario['id']);

        $this->view('notificaciones', [
            'notificaciones' => $notificaciones,
            'mensaje'        => $_SESSION['notif_mensaje'] ?? null,
            'error'          => $_SESSION['notif_error'] ?? null,
        ]);
        unset($_SESSION['notif_mensaje'], $_SESSION['notif_error']);
    }

    public function aceptar(string $id): void
    {
        $notificacion = $this->propia($id);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->volverConError('Tu sesión de formulario venció. Volvé a intentarlo.');
        }
        if ($notificacion['tipo'] !== 'invitacion' || $notificacion['estado_invitacion'] !== 'pendiente') {
            $this->volverConError('Esa invitación ya no está disponible.');
        }

        try {
            Notificacion::aceptarInvitacion($notificacion);
        } catch (RuntimeException $e) {
            $this->volverConError($e->getMessage());
        }

        $_SESSION['notif_mensaje'] = 'Te sumaste al torneo.';
        $this->redirect('/notificaciones');
    }

    public function rechazar(string $id): void
    {
        $notificacion = $this->propia($id);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->volverConError('Tu sesión de formulario venció. Volvé a intentarlo.');
        }
        if ($notificacion['tipo'] !== 'invitacion' || $notificacion['estado_invitacion'] !== 'pendiente') {
            $this->volverConError('Esa invitación ya no está disponible.');
        }

        Notificacion::rechazarInvitacion((int) $notificacion['id']);

        $_SESSION['notif_mensaje'] = 'Rechazaste la invitación.';
        $this->redirect('/notificaciones');
    }

    /** Trae la notificación verificando que sea de quien está logueado — no alcanza con adivinar un ID. */
    private function propia(string $id): array
    {
        Auth::requireLogin();
        $notificacion = Notificacion::find((int) $id);
        if (!$notificacion || (int) $notificacion['usuario_id'] !== (int) Auth::user()['id']) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            exit;
        }
        return $notificacion;
    }

    private function volverConError(string $mensaje): void
    {
        $_SESSION['notif_error'] = $mensaje;
        $this->redirect('/notificaciones');
    }
}
