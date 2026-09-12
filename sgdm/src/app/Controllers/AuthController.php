<?php
/**
 * AuthController.php
 * Registro e inicio de sesión con email y contraseña — el único
 * mecanismo de autenticación de esta versión (se sacó el login con
 * Google porque no terminaba de funcionar de forma confiable).
 *
 * Seguridad aplicada (OWASP Top 10):
 *   - Contraseñas con password_hash() (bcrypt), nunca en texto plano.
 *   - Mensaje de error de login genérico ("email o contraseña incorrectos")
 *     para no revelar si el email existe o no en la base.
 *   - CSRF token en ambos formularios.
 *   - Mensajes de error dirigidos al usuario, en lenguaje simple, tal
 *     como pide el requerimiento no funcional de mensajes de error claros.
 */
class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/perfil');
        }
        $this->view('auth/login', ['error' => null]);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $token = $_POST['csrf_token'] ?? null;

        if (!Csrf::validate($token)) {
            $this->view('auth/login', ['error' => 'Tu sesión de formulario venció. Volvé a intentarlo.']);
            return;
        }

        $usuario = Usuario::porEmail($email);

        if (!$usuario || !$usuario['password_hash'] || !password_verify($password, $usuario['password_hash'])) {
            $this->view('auth/login', ['error' => 'El correo o la contraseña no son correctos. Revisalos y volvé a intentar.']);
            return;
        }

        if ($usuario['estado'] === 'suspendido') {
            $this->view('auth/login', ['error' => 'Esta cuenta está suspendida. Contactá a un administrador.']);
            return;
        }

        Auth::login($usuario);
        $destino = $_SESSION['redirect_despues_login'] ?? '/perfil';
        unset($_SESSION['redirect_despues_login']);
        $this->redirect($destino);
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('/perfil');
        }
        $this->view('auth/register', ['error' => null, 'valores' => []]);
    }

    public function register(): void
    {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $apellido = trim((string) ($_POST['apellido'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $token = $_POST['csrf_token'] ?? null;

        $valores = ['nombre' => $nombre, 'apellido' => $apellido, 'email' => $email];

        if (!Csrf::validate($token)) {
            $this->view('auth/register', ['error' => 'Tu sesión de formulario venció. Volvé a intentarlo.', 'valores' => $valores]);
            return;
        }

        if ($nombre === '' || $apellido === '') {
            $this->view('auth/register', ['error' => 'Completá tu nombre y apellido.', 'valores' => $valores]);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('auth/register', ['error' => 'Ese correo no parece válido. Revisalo e intentá de nuevo.', 'valores' => $valores]);
            return;
        }
        if (Usuario::porEmail($email)) {
            $this->view('auth/register', ['error' => 'Ya existe una cuenta con ese correo. Probá iniciar sesión.', 'valores' => $valores]);
            return;
        }
        if (mb_strlen($password) < 8) {
            $this->view('auth/register', ['error' => 'La contraseña tiene que tener al menos 8 caracteres.', 'valores' => $valores]);
            return;
        }
        if ($password !== $passwordConfirm) {
            $this->view('auth/register', ['error' => 'Las contraseñas no coinciden. Escribilas de nuevo.', 'valores' => $valores]);
            return;
        }

        $usuarioId = Usuario::registrar($nombre, $apellido, $email, $password);
        Auditoria::registrar($usuarioId, 'registro', 'usuarios', $usuarioId);

        $usuario = Usuario::find($usuarioId);
        Auth::login($usuario);
        $this->redirect('/perfil');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/');
    }
}
