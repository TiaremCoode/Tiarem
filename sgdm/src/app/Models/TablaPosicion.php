<?php
/**
 * TablaPosicion.php — alineado a la tabla `tabla_posiciones`.
 */
class TablaPosicion extends Model
{
    protected static string $table = 'tabla_posiciones';

    public static function delTorneo(int $torneoId): array
    {
        $stmt = static::db()->prepare(
            'SELECT tp.*, u.nombre, u.apellido
             FROM tabla_posiciones tp
             JOIN participantes p ON p.id = tp.participante_id
             JOIN usuarios u      ON u.id = p.usuario_id
             WHERE tp.torneo_id = ?
             ORDER BY tp.puntos DESC, tp.victorias DESC'
        );
        $stmt->execute([$torneoId]);
        return $stmt->fetchAll();
    }
}
