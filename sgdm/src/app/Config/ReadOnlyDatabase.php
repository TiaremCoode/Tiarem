<?php
/**
 * ReadOnlyDatabase.php
 * Conexión PDO separada, usada exclusivamente por las vistas públicas
 * (inicio, búsqueda de torneos, detalle de torneo, tabla de posiciones)
 * que no requieren sesión iniciada. Se conecta con el usuario
 * "sgdm_readonly", que en la base de datos SOLO tiene privilegio SELECT.
 *
 * Es una defensa en profundidad: incluso si hubiera un error de
 * programación en un controlador público, esta conexión no tiene forma
 * de modificar ni un solo registro, porque el propio motor de MySQL se lo
 * impide a nivel de usuario, no solo el código de la aplicación.
 */
class ReadOnlyDatabase extends Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect(
                getenv('DB_READONLY_USER'),
                getenv('DB_READONLY_PASS')
            );
        }
        return self::$instance;
    }
}
