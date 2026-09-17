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

    /**
     * Busca un enfrentamiento verificando que pertenezca a ESE torneo (no
     * alcanza con el ID solo: alguien podría probar con el ID de un
     * enfrentamiento de otro torneo). Trae además el estado de su ronda,
     * para no tener que hacer una segunda consulta al validar que la
     * ronda esté abierta antes de aceptar un resultado.
     */
    public static function encontrarDelTorneo(int $id, int $torneoId): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT e.*, ro.estado AS ronda_estado
             FROM enfrentamientos e
             JOIN rondas ro ON ro.id = e.ronda_id
             WHERE e.id = ? AND ro.torneo_id = ?
             LIMIT 1'
        );
        $stmt->execute([$id, $torneoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Pares de participantes que ya se enfrentaron en este torneo (en
     * cualquier ronda anterior), como set de claves "menorId-mayorId" para
     * poder consultarlo con isset() en O(1). La usa el sistema suizo para
     * evitar repetir rivales (RF: "randomizar... evitando que se enfrenten
     * en muchas ocasiones los mismos rivales"). Los byes (participante2_id
     * NULL) no cuentan como enfrentamiento real entre dos personas.
     */
    public static function paresJugados(int $torneoId): array
    {
        $stmt = static::db()->prepare(
            'SELECT e.participante1_id, e.participante2_id
             FROM enfrentamientos e
             JOIN rondas ro ON ro.id = e.ronda_id
             WHERE ro.torneo_id = ? AND e.participante2_id IS NOT NULL'
        );
        $stmt->execute([$torneoId]);

        $pares = [];
        foreach ($stmt->fetchAll() as $fila) {
            $clave = self::clavePar((int) $fila['participante1_id'], (int) $fila['participante2_id']);
            $pares[$clave] = true;
        }
        return $pares;
    }

    public static function clavePar(int $a, int $b): string
    {
        return $a < $b ? "{$a}-{$b}" : "{$b}-{$a}";
    }

    /**
     * Ganadores de una ronda ya cerrada, en el mismo orden posicional en
     * que se jugaron sus enfrentamientos — así la siguiente ronda de
     * eliminación directa avanza a cada ganador a la posición de llave que
     * le corresponde, sin mezclar los cruces.
     */
    public static function ganadoresDeRonda(int $rondaId): array
    {
        $stmt = static::db()->prepare(
            'SELECT r.ganador_id
             FROM enfrentamientos e
             JOIN resultados r ON r.enfrentamiento_id = e.id
             WHERE e.ronda_id = ?
             ORDER BY e.orden ASC'
        );
        $stmt->execute([$rondaId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'ganador_id'));
    }
}
