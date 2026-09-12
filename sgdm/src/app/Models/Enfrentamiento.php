<?php
/**
 * Enfrentamiento.php — alineado a la tabla `enfrentamientos`.
 */
class Enfrentamiento extends Model
{
    protected static string $table = 'enfrentamientos';

    /** Enfrentamientos de una ronda, con nombre de los participantes resueltos. */
    public static function deLaRonda(int $rondaId): array
    {
        $stmt = static::db()->prepare(
            'SELECT e.*,
                    u1.nombre AS p1_nombre, u1.apellido AS p1_apellido,
                    u2.nombre AS p2_nombre, u2.apellido AS p2_apellido,
                    r.puntaje_participante1, r.puntaje_participante2, r.ganador_id
             FROM enfrentamientos e
             JOIN participantes p1 ON p1.id = e.participante1_id
             JOIN usuarios u1      ON u1.id = p1.usuario_id
             LEFT JOIN participantes p2 ON p2.id = e.participante2_id
             LEFT JOIN usuarios u2      ON u2.id = p2.usuario_id
             LEFT JOIN resultados r     ON r.enfrentamiento_id = e.id
             WHERE e.ronda_id = ?
             ORDER BY e.orden ASC'
        );
        $stmt->execute([$rondaId]);
        return $stmt->fetchAll();
    }
}
