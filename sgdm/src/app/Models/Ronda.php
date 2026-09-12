<?php
/**
 * Ronda.php — alineado a la tabla `rondas`.
 */
class Ronda extends Model
{
    protected static string $table = 'rondas';

    public static function delTorneo(int $torneoId): array
    {
        return self::where('torneo_id', $torneoId, 'numero ASC');
    }
}
