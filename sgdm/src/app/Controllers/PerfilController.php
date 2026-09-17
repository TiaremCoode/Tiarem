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
}
