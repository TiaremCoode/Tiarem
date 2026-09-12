<?php
/**
 * Resultado.php — alineado a la tabla `resultados` (1:1 con
 * enfrentamientos).
 */
class Resultado extends Model
{
    protected static string $table = 'resultados';

    public static function delEnfrentamiento(int $enfrentamientoId): ?array
    {
        return self::findBy('enfrentamiento_id', $enfrentamientoId);
    }
}
