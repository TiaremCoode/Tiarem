<?php
/**
 * PerfilController.php
 * Perfil del usuario autenticado: sus datos y sus torneos (organizados
 * o donde participa).
 */
class PerfilController extends Controller
{
    public function show(): void
    {
        Auth::requireLogin();
        $usuario = Auth::user();
        $torneos = Torneo::delUsuario((int) $usuario['id']);

        $this->view('perfil', [
            'usuario' => $usuario,
            'torneos' => $torneos,
            'titulos' => TorneoHistorial::titulosDe((int) $usuario['id']),
            'mensaje' => $_SESSION['perfil_mensaje'] ?? null,
        ]);
        unset($_SESSION['perfil_mensaje']);
    }

    /** RF: "editar datos básicos de su perfil, si el sistema lo permite". */
    public function update(): void
    {
        Auth::requireLogin();
        $usuario = Auth::user();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $_SESSION['perfil_mensaje'] = 'Tu sesión de formulario venció. Intentá de nuevo.';
            $this->redirect('/perfil');
        }

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $apellido = trim((string) ($_POST['apellido'] ?? ''));

        if ($nombre === '' || $apellido === '') {
            $_SESSION['perfil_mensaje'] = 'El nombre y el apellido no pueden quedar vacíos.';
            $this->redirect('/perfil');
        }

        Usuario::update($usuario['id'], ['nombre' => $nombre, 'apellido' => $apellido]);
        Auditoria::registrar((int) $usuario['id'], 'editar_perfil', 'usuarios', (int) $usuario['id']);

        $_SESSION['perfil_mensaje'] = 'Guardamos los cambios de tu perfil.';
        $this->redirect('/perfil');
    }

    /** RF: "elegir si se te puede agregar a cualquier torneo o no". */
    public function actualizarPrivacidad(): void
    {
        Auth::requireLogin();
        $usuario = Auth::user();

        // Desde la corrección del menú de Ajustes (accesible desde
        // cualquier página, no solo /perfil), el formulario del
        // interruptor manda a dónde volver en "redirigir_a". Se valida
        // que sea una ruta relativa propia del sitio (empieza con "/" y
        // no con "//" ni "/\\") para no abrir la puerta a un open
        // redirect con una URL externa.
        $volverA = (string) ($_POST['redirigir_a'] ?? '');
        $esRutaPropiaSegura = $volverA !== ''
            && str_starts_with($volverA, '/')
            && !str_starts_with($volverA, '//')
            && !str_starts_with($volverA, '/\\');
        $destino = $esRutaPropiaSegura ? $volverA : '/perfil';

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $_SESSION['perfil_mensaje'] = 'Tu sesión de formulario venció. Intentá de nuevo.';
            $this->redirect($destino);
        }

        $permite = isset($_POST['permite_agregado_directo']);
        Usuario::cambiarPreferenciaAgregado((int) $usuario['id'], $permite);
        Auditoria::registrar((int) $usuario['id'], 'actualizar_privacidad', 'usuarios', (int) $usuario['id'], $permite ? 'alta directa' : 'requiere invitación');

        $_SESSION['perfil_mensaje'] = $permite
            ? 'Ahora cualquier organizador te puede sumar directo a un torneo.'
            : 'A partir de ahora, sumarte a un torneo te va a llegar como invitación para aceptar o rechazar.';
        $this->redirect($destino);
    }
}
