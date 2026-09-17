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

    /** La propia inscripción de un usuario a un torneo puntual (para el portón de reglas y el resto de vistas de "mi participación"). */
    public static function deUsuarioEnTorneo(int $torneoId, int $usuarioId): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM participantes WHERE torneo_id = ? AND usuario_id = ? AND estado = "activo" LIMIT 1'
        );
        $stmt->execute([$torneoId, $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** RF: tiene que aceptar las reglas para poder seguir viendo el torneo del que participa. */
    public static function aceptarReglas(int $participanteId): bool
    {
        return self::update($participanteId, ['acepto_reglas' => true]);
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
     * Corrección post-revisión: en una disciplina de equipo (fútbol,
     * pádel, etc.) el motor de competencia tiene que armar los cruces
     * entre EQUIPOS, no entre cada jugador individual — antes,
     * Competencia::iniciar() le pasaba a Formatos/ todos los
     * participantes sueltos tal cual, y por eso en un torneo de fútbol
     * por equipos terminaba enfrentando "Jugador Fútbol 8 vs Jugador
     * Fútbol 10" en vez de a sus respectivos equipos.
     *
     * La tabla `enfrentamientos` sigue guardando participante1_id /
     * participante2_id (no se tocó ese esquema, ya revisado en la 2ª
     * entrega): lo que cambia es A QUIÉN se elige para representar a
     * cada equipo en esa fila. Se usa siempre el participante con menor
     * id del equipo (el primero que se anotó) — el mismo criterio que ya
     * usaba TorneoHistorial::titulosDe() para atribuirle el título a todo
     * el equipo, no solo a ese representante. Los demás integrantes del
     * equipo quedan igual registrados en `participantes` (para gestión,
     * historial y una futura carga de goleadores por jugador), solo que
     * no aparecen sueltos en el fixture.
     *
     * @return array Participantes "competidores": uno por equipo si la
     *               disciplina es de equipo, o todos si es individual.
     */
    public static function competidoresActivos(int $torneoId, bool $esDeEquipo): array
    {
        $participantes = self::delTorneo($torneoId);
        if (!$esDeEquipo) {
            return $participantes;
        }

        $representantePorEquipo = [];
        foreach ($participantes as $p) {
            $equipoId = $p['equipo_id'];
            if ($equipoId === null) {
                // Defensivo: alguien anotado sin equipo en una disciplina
                // de equipo (no debería pasar, ParticipanteController lo
                // exige) — se lo deja competir por su cuenta antes que
                // perderlo del fixture.
                $representantePorEquipo['individual-' . $p['id']] = $p;
                continue;
            }
            if (!isset($representantePorEquipo[$equipoId]) || (int) $p['id'] < (int) $representantePorEquipo[$equipoId]['id']) {
                $representantePorEquipo[$equipoId] = $p;
            }
        }

        return array_values($representantePorEquipo);
    }

    /**
     * Cantidad de "competidores" activos: equipos distintos si la
     * disciplina es de equipo, participantes sueltos si es individual.
     * La usan Competencia::iniciar()/totalRondas() y FormatoSuizo, para
     * no calcular la cantidad de rondas ni el mínimo para arrancar sobre
     * la cantidad de jugadores cuando en realidad hay que contar equipos.
     */
    public static function cantidadCompetidoresActivos(int $torneoId, bool $esDeEquipo): int
    {
        if (!$esDeEquipo) {
            return self::cantidadActivos($torneoId);
        }
        return count(self::competidoresActivos($torneoId, true));
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
