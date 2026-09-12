<?php
/**
 * ConfiguracionTorneo.php — alineado a la tabla `configuraciones_torneo`
 * (relación 1:1 con torneos).
 */
class ConfiguracionTorneo extends Model
{
    protected static string $table = 'configuraciones_torneo';

    public static function delTorneo(int $torneoId): ?array
    {
        return self::findBy('torneo_id', $torneoId);
    }
}
