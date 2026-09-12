<?php
/**
 * AdminController.php
 * Panel de administración general. Cubre las funciones del rol
 * admin_general que en la entrega anterior habían quedado sin programar:
 * "crear, editar y eliminar usuarios administrativos" (RF de la letra
 * del proyecto) y "consultar registros de auditoría". Todo accesible
 * únicamente para el rol admin_general.
 */
class AdminController extends Controller
{
    public function auditoria(): void
    {
        Auth::requireRole([Roles::ADMIN_GENERAL]);

        $this->view('admin/auditoria', [
            'registros' => Auditoria::recientes(50),
        ]);
    }

    /** Listado de usuarios, con búsqueda opcional por nombre, apellido, email o ID público. */
    public function usuarios(): void
    {
        Auth::requireRole([Roles::ADMIN_GENERAL]);

        $texto = trim((string) ($_GET['q'] ?? ''));

        $this->view('admin/usuarios', [
            'usuarios' => Usuario::buscar($texto, 100),
            'texto'    => $texto,
            'mensaje'  => $_SESSION['admin_mensaje'] ?? null,
            'error'    => $_SESSION['admin_error'] ?? null,
        ]);
        unset($_SESSION['admin_mensaje'], $_SESSION['admin_error']);
    }

    public function crearUsuarioForm(): void
    {
        Auth::requireRole([Roles::ADMIN_GENERAL]);

        $this->view('admin/usuario_form', [
            'modo'    => 'crear',
            'usuario' => null,
            'roles'   => Rol::all('id ASC'),
            'error'   => null,
            'valores' => [],
        ]);
    }

    public function crearUsuario(): void
    {
        Auth::requireRole([Roles::ADMIN_GENERAL]);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->volverAlFormulario('crear', null, 'Tu sesión de formulario venció. Volvé a intentarlo.', $_POST);
        }

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $apellido = trim((string) ($_POST['apellido'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $rolId = (int) ($_POST['rol_id'] ?? 0);

        if ($nombre === '' || $apellido === '') {
            $this->volverAlFormulario('crear', null, 'Completá el nombre y el apellido.', $_POST);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->volverAlFormulario('crear', null, 'Ese correo no parece válido.', $_POST);
        }
        if (Usuario::porEmail($email)) {
            $this->volverAlFormulario('crear', null, 'Ya existe una cuenta con ese correo.', $_POST);
        }
        if (mb_strlen($password) < 8) {
            $this->volverAlFormulario('crear', null, 'La contraseña tiene que tener al menos 8 caracteres.', $_POST);
        }
        if (!Rol::find($rolId)) {
            $this->volverAlFormulario('crear', null, 'Elegí uno de los roles disponibles.', $_POST);
        }

        $nuevoId = Usuario::crearPorAdmin($nombre, $apellido, $email, $password, $rolId);
        Auditoria::registrar((int) Auth::user()['id'], 'crear_usuario_admin', 'usuarios', $nuevoId, "{$nombre} {$apellido} ({$email})");

        $_SESSION['admin_mensaje'] = 'Se creó la cuenta de ' . $nombre . ' ' . $apellido . '.';
        $this->redirect('/admin/usuarios');
    }

    public function editarUsuarioForm(string $id): void
    {
        Auth::requireRole([Roles::ADMIN_GENERAL]);

        $usuario = Usuario::find((int) $id);
        if (!$usuario) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $this->view('admin/usuario_form', [
            'modo'    => 'editar',
            'usuario' => $usuario,
            'roles'   => Rol::all('id ASC'),
            'error'   => null,
            'valores' => [],
        ]);
    }

    /** Actualiza rol y estado (activo/suspendido) de un usuario existente. No permite tocar la propia cuenta. */
    public function actualizarUsuario(string $id): void
    {
        Auth::requireRole([Roles::ADMIN_GENERAL]);
        $usuarioActual = Auth::user();
        $usuario = Usuario::find((int) $id);

        if (!$usuario) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->volverAlFormulario('editar', $usuario, 'Tu sesión de formulario venció. Volvé a intentarlo.', []);
        }
        if ((int) $usuario['id'] === (int) $usuarioActual['id']) {
            $this->volverAlFormulario('editar', $usuario, 'No podés cambiar el rol o el estado de tu propia cuenta desde acá.', []);
        }

        $rolId = (int) ($_POST['rol_id'] ?? 0);
        $estado = (string) ($_POST['estado'] ?? '');

        if (!Rol::find($rolId)) {
            $this->volverAlFormulario('editar', $usuario, 'Elegí uno de los roles disponibles.', []);
        }
        if (!in_array($estado, ['activo', 'suspendido'], true)) {
            $this->volverAlFormulario('editar', $usuario, 'Ese estado de cuenta no es válido.', []);
        }

        Usuario::cambiarRol((int) $usuario['id'], $rolId);
        Usuario::cambiarEstado((int) $usuario['id'], $estado);
        Auditoria::registrar((int) $usuarioActual['id'], 'editar_usuario_admin', 'usuarios', (int) $usuario['id']);

        $_SESSION['admin_mensaje'] = 'Se guardaron los cambios de ' . Usuario::nombreCompleto($usuario) . '.';
        $this->redirect('/admin/usuarios');
    }

    /**
     * Elimina una cuenta. Solo es posible si esa persona no tiene
     * actividad asociada todavía (no organizó torneos, no está anotada
     * en ninguno, no cargó resultados) — la propia base de datos lo
     * impide mediante las claves foráneas con ON DELETE RESTRICT. Si el
     * borrado no es posible, se lo decimos con claridad y se sugiere
     * suspender la cuenta en su lugar.
     */
    public function eliminarUsuario(string $id): void
    {
        Auth::requireRole([Roles::ADMIN_GENERAL]);
        $usuarioActual = Auth::user();
        $usuario = Usuario::find((int) $id);

        if (!$usuario) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $_SESSION['admin_error'] = 'Tu sesión de formulario venció. Volvé a intentarlo.';
            $this->redirect('/admin/usuarios');
        }
        if ((int) $usuario['id'] === (int) $usuarioActual['id']) {
            $_SESSION['admin_error'] = 'No podés eliminar tu propia cuenta.';
            $this->redirect('/admin/usuarios');
        }

        try {
            Usuario::delete((int) $usuario['id']);
            Auditoria::registrar((int) $usuarioActual['id'], 'eliminar_usuario_admin', 'usuarios', null, Usuario::nombreCompleto($usuario));
            $_SESSION['admin_mensaje'] = 'Se eliminó la cuenta de ' . Usuario::nombreCompleto($usuario) . '.';
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = Usuario::nombreCompleto($usuario) . ' ya tiene actividad en el sistema (torneos, participaciones u otros registros), así que no se puede eliminar. Podés suspender la cuenta en su lugar.';
        }

        $this->redirect('/admin/usuarios');
    }

    private function volverAlFormulario(string $modo, ?array $usuario, string $mensaje, array $valores): void
    {
        $this->view('admin/usuario_form', [
            'modo'    => $modo,
            'usuario' => $usuario,
            'roles'   => Rol::all('id ASC'),
            'error'   => $mensaje,
            'valores' => $valores,
        ]);
        exit;
    }
}
