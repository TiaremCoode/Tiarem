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

    /**
     * Crea una ronda junto con todos sus enfrentamientos, en una sola
     * transacción. $pares es una lista de [participante1_id, participante2_id],
     * donde participante2_id puede venir en NULL para representar un pase
     * directo (bye) — el propio sistema lo resuelve ahí mismo, generando su
     * resultado automáticamente (RF: "generar automáticamente... el
     * calendario de enfrentamientos"), sin que nadie tenga que cargar nada.
     *
     * Usada por los tres módulos de formato (Formatos/): liga solo la usa
     * con pares completos (nunca pasa un bye, ver FormatoLiga), eliminación
     * directa y suizo sí pueden pasar byes.
     */
    public static function crearConEnfrentamientos(int $torneoId, int $numero, array $pares): array
    {
        $db = static::db();
        $db->beginTransaction();
        try {
            $rondaId = self::create([
                'torneo_id'      => $torneoId,
                'numero'         => $numero,
                'estado'         => 'abierta',
                'fecha_apertura' => date('Y-m-d H:i:s'),
            ]);

            foreach ($pares as $orden => $par) {
                [$p1, $p2] = $par;
                $enfrentamientoId = Enfrentamiento::create([
                    'ronda_id'         => $rondaId,
                    'participante1_id' => $p1,
                    'participante2_id' => $p2,
                    'orden'            => $orden,
                    'estado'           => $p2 === null ? 'walkover' : 'pendiente',
                ]);

                if ($p2 === null) {
                    Resultado::create([
                        'enfrentamiento_id'     => $enfrentamientoId,
                        'puntaje_participante1' => 1,
                        'puntaje_participante2' => null,
                        'ganador_id'            => $p1,
                        'cargado_por'           => null, // resuelto por el sistema, no por una persona
                    ]);
                }
            }

            $db->commit();
            return self::find($rondaId);
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** ¿Ya tienen resultado (jugado o walkover) todos los enfrentamientos de esta ronda? */
    public static function estaCompleta(int $rondaId): bool
    {
        $stmt = static::db()->prepare(
            "SELECT COUNT(*) AS pendientes FROM enfrentamientos WHERE ronda_id = ? AND estado = 'pendiente'"
        );
        $stmt->execute([$rondaId]);
        return (int) $stmt->fetch()['pendientes'] === 0;
    }

    public static function cerrar(int $rondaId): bool
    {
        return self::update($rondaId, ['estado' => 'cerrada', 'fecha_cierre' => date('Y-m-d H:i:s')]);
    }
}
