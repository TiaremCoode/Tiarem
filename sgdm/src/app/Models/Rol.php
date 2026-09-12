<?php
/**
 * Rol.php — alineado a la tabla `roles`.
 */
class Rol extends Model
{
    protected static string $table = 'roles';

    public static function porCodigo(string $codigo): ?array
    {
        return self::findBy('codigo', $codigo);
    }
}
