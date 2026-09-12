<?php
/**
 * Csrf.php
 * Protección contra Cross-Site Request Forgery (OWASP Top 10 - A01) para
 * todos los formularios que modifican datos (login, registro, alta de
 * torneo). Cada formulario incluye un token oculto que se valida contra
 * el guardado en sesión antes de procesar la acción.
 */
class Csrf
{
    public static function token(): string
    {
        Auth::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }

    public static function validate(?string $token): bool
    {
        Auth::start();
        return $token !== null
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
