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
