<?php
/**
 * Auth.php
 * Gestión de usuarios: sesión, login, logout y control de acceso por rol.
 * Implementa el módulo de "usuarios y autenticación" pedido como módulo
 * funcional mínimo, y el requerimiento técnico de un sistema de
 * autenticación y autorización basado en roles.
 *
 * Buenas prácticas de sesión aplicadas (OWASP):
 *   - session_regenerate_id() al iniciar sesión, para evitar fijación de
 *     sesión (session fixation).
 *   - Los datos sensibles (hash de contraseña) nunca se guardan en
 *     $_SESSION; solo el id de usuario. El resto se relee de la base
 *     cuando hace falta con Auth::user().
 */
class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $usuario): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $usuario['id'];

        Usuario::update($usuario['id'], ['ultimo_acceso' => date('Y-m-d H:i:s')]);
        Auditoria::registrar((int) $usuario['id'], 'login', 'usuarios', (int) $usuario['id']);
    }

    public static function logout(): void
    {
        self::start();
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        if ($usuarioId) {
            Auditoria::registrar((int) $usuarioId, 'logout', 'usuarios', (int) $usuarioId);
        }
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        self::start();
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Usuario autenticado (con su rol resuelto como código), o null.
     * Se cachea en un estático dentro del request para no repetir la
     * consulta si se llama varias veces durante la misma petición.
     */
    public static function user(): ?array
    {
        static $cache = null;
        static $resuelto = false;

        if ($resuelto) {
            return $cache;
        }
        $resuelto = true;

        self::start();
        if (!self::check()) {
            return null;
        }

        $usuario = Usuario::find($_SESSION['usuario_id']);
        if (!$usuario) {
            return null;
        }

        $rol = Rol::find($usuario['rol_id']);
        $usuario['rol_codigo'] = $rol['codigo'] ?? null;
        $usuario['rol_nombre'] = $rol['nombre'] ?? null;

        $cache = $usuario;
        return $cache;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            self::start();
            $_SESSION['redirect_despues_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /login');
            exit;
        }
    }

    /**
     * @param string[] $rolesPermitidos códigos de Roles::*
     */
    public static function requireRole(array $rolesPermitidos): void
    {
        self::requireLogin();
        $usuario = self::user();
        if (!$usuario || !in_array($usuario['rol_codigo'], $rolesPermitidos, true)) {
            http_response_code(403);
            require __DIR__ . '/../Views/errors/403.php';
            exit;
        }
    }

    /**
     * ¿La sesión actual puede gestionar ESE torneo puntual? Es organizador
     * de ese torneo (torneos.organizador_id) o admin general. Centraliza
     * una comparación que antes estaba repetida en TorneoController::show()
     * y ParticipanteController::autorizar(); ahora también la usa
     * CompetenciaController.
     */
    public static function esOrganizadorOAdmin(array $torneo): bool
    {
        $usuario = self::user();
        if (!$usuario) {
            return false;
        }
        return (int) $usuario['id'] === (int) $torneo['organizador_id']
            || $usuario['rol_codigo'] === Roles::ADMIN_GENERAL;
    }

    /** Exige sesión iniciada y que sea organizador de ESE torneo o admin general; corta la petición con 403 si no. */
    public static function requireOrganizadorOAdmin(array $torneo): void
    {
        self::requireLogin();
        if (!self::esOrganizadorOAdmin($torneo)) {
            http_response_code(403);
            require __DIR__ . '/../Views/errors/403.php';
            exit;
        }
    }
}
