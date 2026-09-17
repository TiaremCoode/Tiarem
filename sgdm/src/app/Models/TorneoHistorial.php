<?php
/**
 * TorneoHistorial.php — alineado a la tabla `torneos_historial`.
 * RF 3ª entrega: guardar, al finalizar el torneo, sus estadísticas
 * (mejor jugador, mayor puntaje, invicto, jugador con menos derrotas) y
 * atribuirle el título de campeón al usuario en su perfil.
 *
 * Las estadísticas se calculan siempre igual sin importar el formato
 * (a partir de enfrentamientos + resultados directamente, no de
 * tabla_posiciones, porque eliminación directa no llena esa tabla) — solo
 * el campeón varía según el formato, y por eso lo decide el propio
 * Formato correspondiente (ver Formatos/) antes de llamar a
 * calcularYGuardar().
 */
class TorneoHistorial extends Model
{
    protected static string $table = 'torneos_historial';

    /**
     * Calcula victorias, derrotas y puntaje anotado de cada participante
     * que jugó en el torneo, determina las cuatro estadísticas y guarda
     * la fila de historial. $campeonParticipanteId ya viene resuelto por
     * el Formato (ver FormatoInterface::campeon()).
     */
    public static function calcularYGuardar(int $torneoId, ?int $campeonParticipanteId): array
    {
        $stmt = static::db()->prepare(
            'SELECT p.id AS participante_id,
                    SUM(r.ganador_id = p.id) AS victorias,
                    SUM(r.ganador_id IS NOT NULL AND r.ganador_id <> p.id) AS derrotas,
                    SUM(CASE WHEN e.participante1_id = p.id THEN r.puntaje_participante1
                             WHEN e.participante2_id = p.id THEN r.puntaje_participante2
                             ELSE 0 END) AS puntaje_total,
                    COUNT(r.id) AS partidos_jugados
             FROM participantes p
             JOIN enfrentamientos e ON (e.participante1_id = p.id OR e.participante2_id = p.id)
             JOIN resultados r      ON r.enfrentamiento_id = e.id
             JOIN rondas ro         ON ro.id = e.ronda_id
             WHERE ro.torneo_id = ?
             GROUP BY p.id'
        );
        $stmt->execute([$torneoId]);
        $filas = $stmt->fetchAll();

        $mejorJugador = self::elMayor($filas, 'victorias', 'puntaje_total');
        $mayorPuntaje = self::elMayor($filas, 'puntaje_total', 'victorias');
        $invicto = self::elMenor(array_filter($filas, fn ($f) => (int) $f['derrotas'] === 0), 'derrotas', 'victorias');
        $menosDerrotas = self::elMenor($filas, 'derrotas', 'victorias');

        $datos = [
            'torneo_id'                      => $torneoId,
            'campeon_participante_id'        => $campeonParticipanteId,
            'mejor_jugador_participante_id'  => $mejorJugador['participante_id'] ?? null,
            'mayor_puntaje_participante_id'  => $mayorPuntaje['participante_id'] ?? null,
            'invicto_participante_id'        => $invicto['participante_id'] ?? null,
            'menos_derrotas_participante_id' => $menosDerrotas['participante_id'] ?? null,
        ];

        self::create($datos);
        return $datos;
    }

    /** Fila con mayor valor de $campo (empate se desempata por $desempate, de mayor a mayor también). */
    private static function elMayor(array $filas, string $campo, string $desempate): ?array
    {
        if (empty($filas)) {
            return null;
        }
        usort($filas, fn ($a, $b) => $b[$campo] <=> $a[$campo] ?: $b[$desempate] <=> $a[$desempate]);
        return $filas[0];
    }

    /** Fila con menor valor de $campo (empate se desempata por $desempate, de mayor a mayor). */
    private static function elMenor(array $filas, string $campo, string $desempate): ?array
    {
        $filas = array_values($filas);
        if (empty($filas)) {
            return null;
        }
        usort($filas, fn ($a, $b) => $a[$campo] <=> $b[$campo] ?: $b[$desempate] <=> $a[$desempate]);
        return $filas[0];
    }

    /** Historial de un torneo ya finalizado, con los nombres resueltos para mostrar en el detalle. */
    public static function delTorneo(int $torneoId): ?array
    {
        $stmt = static::db()->prepare(
            "SELECT th.*, t.fecha_inicio, t.fecha_fin,
                    uc.nombre AS campeon_nombre, uc.apellido AS campeon_apellido, ec.nombre AS campeon_equipo,
                    umj.nombre AS mejor_jugador_nombre, umj.apellido AS mejor_jugador_apellido,
                    ump.nombre AS mayor_puntaje_nombre, ump.apellido AS mayor_puntaje_apellido,
                    ui.nombre  AS invicto_nombre,        ui.apellido  AS invicto_apellido,
                    umd.nombre AS menos_derrotas_nombre, umd.apellido AS menos_derrotas_apellido
             FROM torneos_historial th
             JOIN torneos t ON t.id = th.torneo_id
             LEFT JOIN participantes pc  ON pc.id = th.campeon_participante_id
             LEFT JOIN usuarios uc       ON uc.id = pc.usuario_id
             LEFT JOIN equipos ec        ON ec.id = pc.equipo_id
             LEFT JOIN participantes pmj ON pmj.id = th.mejor_jugador_participante_id
             LEFT JOIN usuarios umj      ON umj.id = pmj.usuario_id
             LEFT JOIN participantes pmp ON pmp.id = th.mayor_puntaje_participante_id
             LEFT JOIN usuarios ump      ON ump.id = pmp.usuario_id
             LEFT JOIN participantes pi  ON pi.id = th.invicto_participante_id
             LEFT JOIN usuarios ui       ON ui.id = pi.usuario_id
             LEFT JOIN participantes pmd ON pmd.id = th.menos_derrotas_participante_id
             LEFT JOIN usuarios umd      ON umd.id = pmd.usuario_id
             WHERE th.torneo_id = ?
             LIMIT 1"
        );
        $stmt->execute([$torneoId]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Torneos donde este usuario fue campeón — como jugador individual, o
     * como integrante del equipo que ganó (el título se le atribuye a
     * todo el equipo, no solo a quien haya quedado registrado como
     * participante "representante" del cruce final).
     */
    public static function titulosDe(int $usuarioId): array
    {
        $stmt = static::db()->prepare(
            'SELECT DISTINCT t.id, t.codigo_publico, t.nombre, t.fecha_fin, mc.nombre AS formato_nombre
             FROM torneos_historial th
             JOIN torneos t              ON t.id = th.torneo_id
             JOIN modulos_competencia mc ON mc.id = t.modulo_competencia_id
             JOIN participantes pcamp    ON pcamp.id = th.campeon_participante_id
             JOIN participantes pyo      ON pyo.torneo_id = t.id AND pyo.usuario_id = :uid
             WHERE pyo.id = pcamp.id
                OR (pcamp.equipo_id IS NOT NULL AND pyo.equipo_id = pcamp.equipo_id)
             ORDER BY t.fecha_fin DESC'
        );
        $stmt->execute(['uid' => $usuarioId]);
        return $stmt->fetchAll();
    }
}
