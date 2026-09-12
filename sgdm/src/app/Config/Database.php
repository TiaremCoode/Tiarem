<?php
/**
 * Database.php
 * Conexión PDO de lectura/escritura, usada por la aplicación autenticada
 * (crear torneos, inscribir participantes, cargar resultados, etc).
 * Se conecta con el usuario "sgdm_app", creado en db/02_dcl.sh con
 * privilegios restringidos (SELECT/INSERT/UPDATE/DELETE, sin DDL).
 *
 * Implementada como singleton para reutilizar una única conexión PDO
 * durante todo el ciclo de vida de la petición HTTP.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect(
                getenv('DB_USER'),
                getenv('DB_PASS')
            );
        }
        return self::$instance;
    }

    protected static function connect(string $user, string $pass): PDO
    {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // usa prepared statements reales del driver (más seguro ante inyección SQL)
        ]);
    }
}
