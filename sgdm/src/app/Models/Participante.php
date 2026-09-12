<?php
/**
 * Participante.php — alineado a la tabla `participantes`.
 * Representa la inscripción de un Usuario a un Torneo puntual (no la
 * cuenta de acceso en sí, ver Usuario.php).
 */
class Participante extends Model
{
    protected static string $table = 'participantes';

    /** Participantes activos de un torneo, con su equipo si tienen uno. */
    public static function delTorneo(int $torneoId): array
    {
        $stmt = static::db()->prepare(
            'SELECT p.*, u.nombre, u.apellido, u.id_publico, e.nombre AS equipo_nombre
             FROM participantes p
             JOIN usuarios u      ON u.id = p.usuario_id
             LEFT JOIN equipos e  ON e.id = p.equipo_id
             WHERE p.torneo_id = ? AND p.estado = "activo"
             ORDER BY e.nombre, u.apellido, u.nombre'
        );
        $stmt->execute([$torneoId]);
        return $stmt->fetchAll();
    }

    public static function yaInscripto(int $torneoId, int $usuarioId): bool
    {
        $stmt = static::db()->prepare(
            'SELECT id FROM participantes WHERE torneo_id = ? AND usuario_id = ? AND estado = "activo" LIMIT 1'
        );
        $stmt->execute([$torneoId, $usuarioId]);
        return (bool) $stmt->fetch();
    }

    public static function cantidadActivos(int $torneoId): int
    {
        $stmt = static::db()->prepare(
            'SELECT COUNT(*) AS cantidad FROM participantes WHERE torneo_id = ? AND estado = "activo"'
        );
        $stmt->execute([$torneoId]);
        return (int) $stmt->fetch()['cantidad'];
    }

    /**
     * Da de baja a un participante. Mientras el torneo todavía está en
     * inscripción (no se jugó ningún enfrentamiento) se borra la fila de
     * verdad — nadie más depende de ese registro todavía. Si el torneo ya
     * está en curso, se conserva la fila pero se marca como "retirado",
     * para no perder el historial de que esa persona formó parte del
     * torneo en algún momento.
     */
    public static function darDeBaja(int $participanteId, string $estadoDelTorneo): bool
    {
        if ($estadoDelTorneo === 'inscripcion') {
            return self::delete($participanteId);
        }
        return self::update($participanteId, ['estado' => 'retirado']);
    }
}
