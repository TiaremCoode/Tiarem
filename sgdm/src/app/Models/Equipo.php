<?php
/**
 * Equipo.php — alineado a la tabla `equipos`.
 *
 * Un equipo es una entidad independiente de cualquier torneo puntual (el
 * mismo equipo puede anotarse a varios torneos a lo largo del tiempo).
 * Quiénes lo integran EN UN TORNEO ESPECÍFICO se define a través de
 * `participantes.equipo_id` — por eso los métodos de conteo de
 * integrantes siempre piden también el torneo: la cantidad de jugadores
 * de "Los Tigres" puede ser distinta en la Liga de Vóley que en el
 * Torneo de Pádel, si en cada uno se anotaron personas distintas.
 */
class Equipo extends Model
{
    protected static string $table = 'equipos';

    public static function delCreador(int $usuarioId): array
    {
        return self::where('creado_por', $usuarioId, 'nombre ASC');
    }

    /** Integrantes de un equipo dentro de un torneo puntual. */
    public static function integrantes(int $equipoId, int $torneoId): array
    {
        $stmt = static::db()->prepare(
            'SELECT p.id AS participante_id, u.id AS usuario_id, u.nombre, u.apellido, u.id_publico
             FROM participantes p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.equipo_id = ? AND p.torneo_id = ? AND p.estado = "activo"
             ORDER BY u.apellido, u.nombre'
        );
        $stmt->execute([$equipoId, $torneoId]);
        return $stmt->fetchAll();
    }

    public static function cantidadIntegrantes(int $equipoId, int $torneoId): int
    {
        return count(self::integrantes($equipoId, $torneoId));
    }

    /** Equipos que ya tienen al menos un integrante inscripto en ese torneo. */
    public static function delTorneo(int $torneoId): array
    {
        $stmt = static::db()->prepare(
            'SELECT DISTINCT e.id, e.nombre
             FROM equipos e
             JOIN participantes p ON p.equipo_id = e.id
             WHERE p.torneo_id = ? AND p.estado = "activo"
             ORDER BY e.nombre'
        );
        $stmt->execute([$torneoId]);
        return $stmt->fetchAll();
    }
}
