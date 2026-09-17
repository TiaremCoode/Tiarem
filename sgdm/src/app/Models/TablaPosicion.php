<?php
/**
 * TablaPosicion.php — alineado a la tabla `tabla_posiciones`.
 * Solo la usan los formatos de liga y sistema suizo (RF de ambos: mostrar
 * tabla de posiciones / calcular promedios por ronda). Eliminación directa
 * no acumula puntaje entre rondas — ahí el resultado se ve directamente en
 * la llave — así que ningún enfrentamiento de ese formato pasa por acá
 * (ver FormatoInterface::usaTablaPosiciones()).
 */
class TablaPosicion extends Model
{
    protected static string $table = 'tabla_posiciones';

    // Esquema de puntaje estándar de todos-contra-todos (fútbol, vóley,
    // ajedrez por puntos, etc.), el mismo tanto para liga como para suizo.
    public const PUNTOS_VICTORIA = 3;
    public const PUNTOS_EMPATE = 1;
    public const PUNTOS_DERROTA = 0;

    /**
     * Tabla de posiciones del torneo, con el nombre a mostrar (el del
     * equipo si la disciplina es de equipo, si no el de la persona) y
     * cuánto anotó a favor y en contra en total — goles en fútbol, sets
     * en pádel/vóley — para poder mostrar esas columnas además de
     * puntos/victorias/derrotas (RF post-revisión: "que no quede solo
     * puntos, PG y PP"). Se calcula igual que
     * TorneoHistorial::calcularYGuardar() calcula el puntaje total: sumando
     * el resultado propio en cada enfrentamiento jugado, sea como
     * participante1 o como participante2.
     */
    public static function delTorneo(int $torneoId): array
    {
        $stmt = static::db()->prepare(
            'SELECT tp.*, u.nombre, u.apellido, eq.nombre AS equipo_nombre,
                    COALESCE(marcador.a_favor, 0) AS a_favor,
                    COALESCE(marcador.en_contra, 0) AS en_contra
             FROM tabla_posiciones tp
             JOIN participantes p ON p.id = tp.participante_id
             JOIN usuarios u      ON u.id = p.usuario_id
             LEFT JOIN equipos eq ON eq.id = p.equipo_id
             LEFT JOIN (
                 SELECT pp.id AS participante_id,
                        SUM(CASE WHEN e.participante1_id = pp.id THEN r.puntaje_participante1
                                 WHEN e.participante2_id = pp.id THEN r.puntaje_participante2
                                 ELSE 0 END) AS a_favor,
                        SUM(CASE WHEN e.participante1_id = pp.id THEN r.puntaje_participante2
                                 WHEN e.participante2_id = pp.id THEN r.puntaje_participante1
                                 ELSE 0 END) AS en_contra
                 FROM participantes pp
                 JOIN enfrentamientos e ON (e.participante1_id = pp.id OR e.participante2_id = pp.id)
                 JOIN resultados r      ON r.enfrentamiento_id = e.id
                 JOIN rondas ro         ON ro.id = e.ronda_id
                 WHERE ro.torneo_id = :torneo_id1
                 GROUP BY pp.id
             ) marcador ON marcador.participante_id = tp.participante_id
             WHERE tp.torneo_id = :torneo_id2
             ORDER BY tp.puntos DESC, tp.victorias DESC'
        );
        // Mismo criterio que Torneo::delUsuario(): con PDO::ATTR_EMULATE_PREPARES
        // en false (ver Config/Database.php), el driver nativo de MySQL no
        // admite repetir el mismo parámetro con nombre dos veces en la
        // misma consulta, así que cada aparición necesita su propio alias
        // aunque el valor sea idéntico.
        $stmt->execute(['torneo_id1' => $torneoId, 'torneo_id2' => $torneoId]);
        return $stmt->fetchAll();
    }

    /**
     * Suma el efecto de un resultado a la fila de posiciones de un
     * participante (la crea si todavía no jugó ningún partido en este
     * torneo). $resultado es 'victoria' | 'empate' | 'derrota'.
     */
    public static function aplicarResultado(int $torneoId, int $participanteId, string $resultado): void
    {
        $puntos = match ($resultado) {
            'victoria' => self::PUNTOS_VICTORIA,
            'empate'   => self::PUNTOS_EMPATE,
            default    => self::PUNTOS_DERROTA,
        };

        $stmt = static::db()->prepare(
            'INSERT INTO tabla_posiciones (torneo_id, participante_id, puntos, victorias, derrotas, empates)
             VALUES (:torneo_id, :participante_id, :puntos, :victorias, :derrotas, :empates)
             ON DUPLICATE KEY UPDATE
                puntos    = puntos + VALUES(puntos),
                victorias = victorias + VALUES(victorias),
                derrotas  = derrotas + VALUES(derrotas),
                empates   = empates + VALUES(empates)'
        );
        $stmt->execute([
            'torneo_id'       => $torneoId,
            'participante_id' => $participanteId,
            'puntos'          => $puntos,
            'victorias'       => $resultado === 'victoria' ? 1 : 0,
            'derrotas'        => $resultado === 'derrota' ? 1 : 0,
            'empates'         => $resultado === 'empate' ? 1 : 0,
        ]);
    }
}
