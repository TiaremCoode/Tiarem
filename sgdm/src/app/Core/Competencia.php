<?php
/**
 * Competencia.php — orquesta el ciclo de vida competitivo de un torneo:
 * arranque, carga de resultados, cierre de ronda, avance a la ronda
 * siguiente o finalización. Todo lo específico de cada formato (cómo se
 * arman los cruces, quién es el campeón) lo delega en el módulo de
 * Formatos/ correspondiente — acá solo vive lo que es igual sin importar
 * el formato: guardar el resultado, actualizar la tabla de posiciones si
 * corresponde, y decidir cuándo una ronda quedó completa.
 *
 * No mapea una tabla propia (no es un Model) ni arma una respuesta HTTP
 * (no es un Controller): coordina Models y Formatos, por eso vive en
 * Core/ junto con el resto de las piezas de infraestructura del sistema.
 */
class Competencia
{
    /** Arranca la competencia: valida, genera el fixture inicial y pasa el torneo a "en_curso". */
    public static function iniciar(array $torneo, ?int $usuarioId): void
    {
        if ($torneo['estado'] !== 'inscripcion') {
            throw new RuntimeException('El torneo ya fue iniciado.');
        }

        $esDeEquipo = self::esDeEquipo($torneo);
        // Corrección: en un torneo de equipo, quien compite es cada
        // equipo — no cada jugador suelto — así que tanto el mínimo para
        // arrancar como el fixture que arma el Formato tienen que
        // pensarse en esa unidad (ver Participante::competidoresActivos()).
        $competidores = Participante::competidoresActivos((int) $torneo['id'], $esDeEquipo);
        if (count($competidores) < 2) {
            $unidad = $esDeEquipo ? 'equipos' : 'participantes';
            throw new RuntimeException("Hacen falta al menos 2 {$unidad} para iniciar el torneo.");
        }

        Torneo::update((int) $torneo['id'], [
            'estado'       => 'en_curso',
            'fecha_inicio' => date('Y-m-d H:i:s'),
        ]);
        self::formato($torneo['formato_codigo'])->generarPrimeraRonda($torneo, $competidores);
        $participantes = Participante::delTorneo((int) $torneo['id']);
        Auditoria::registrar($usuarioId, 'iniciar_torneo', 'torneos', (int) $torneo['id'], count($participantes) . ' participantes');
        Notificacion::porTorneoIniciado($torneo, $participantes);

        // Defensivo: en la inmensa mayoría de los casos la ronda 1 recién
        // creada todavía tiene enfrentamientos pendientes, pero si algún
        // formato llegara a resolverla entera de una (todo por bye) esto
        // la cierra y avanza igual, en vez de dejar el torneo trabado.
        $primeraRonda = Ronda::where('torneo_id', (int) $torneo['id'], 'numero ASC')[0];
        self::revisarCierre($torneo, $primeraRonda);
    }

    /**
     * Guarda el resultado de un enfrentamiento pendiente y dispara todo
     * lo que dependa de él: actualiza la tabla de posiciones (si el
     * formato la usa), y si con este resultado la ronda queda completa,
     * la cierra y avanza a la siguiente o finaliza el torneo.
     */
    public static function registrarResultado(array $torneo, array $enfrentamiento, float $puntaje1, float $puntaje2, int $usuarioId, string $motivo = 'normal'): void
    {
        if ($torneo['estado'] !== 'en_curso') {
            throw new RuntimeException('El torneo no está en curso.');
        }
        if ($enfrentamiento['ronda_estado'] !== 'abierta' || $enfrentamiento['estado'] !== 'pendiente') {
            throw new RuntimeException('Ese enfrentamiento no está pendiente en una ronda abierta.');
        }

        $formato = self::formato($torneo['formato_codigo']);
        $p1 = (int) $enfrentamiento['participante1_id'];
        $p2 = (int) $enfrentamiento['participante2_id'];

        if ($puntaje1 == $puntaje2 && !$formato->permiteEmpate()) {
            throw new RuntimeException('Este formato no admite empates: tiene que haber un ganador que avance.');
        }

        // Corrección: antes se aceptaba cualquier combinación de sets
        // (por ej. 4-2 en un partido al mejor de 5), aunque esa
        // disciplina ya no pudiera seguir jugándose en ese punto. Un
        // resultado de "sets" válido es aquel donde alguno de los dos
        // llegó exactamente a los sets que hacen falta para ganar
        // (tipos_torneo.sets_para_ganar) y el otro se quedó por debajo.
        if ($torneo['formato_resultado'] === 'sets') {
            $setsParaGanar = (int) ($torneo['sets_para_ganar'] ?? 0);
            $mayor = max($puntaje1, $puntaje2);
            $menor = min($puntaje1, $puntaje2);
            if ($setsParaGanar < 1 || $mayor != $setsParaGanar || $menor >= $setsParaGanar) {
                throw new RuntimeException(
                    "Ese resultado no es válido para esta disciplina: se juega al mejor de "
                    . (2 * $setsParaGanar - 1) . " sets, así que alguno de los dos tiene que llegar a {$setsParaGanar}"
                    . " y el otro quedar por debajo."
                );
            }
        }
        $ganadorId = match (true) {
            $puntaje1 > $puntaje2 => $p1,
            $puntaje2 > $puntaje1 => $p2,
            default               => null, // empate
        };

        Resultado::create([
            'enfrentamiento_id'     => $enfrentamiento['id'],
            'puntaje_participante1' => $puntaje1,
            'puntaje_participante2' => $puntaje2,
            'ganador_id'            => $ganadorId,
            'motivo'                => $motivo,
            'cargado_por'           => $usuarioId,
        ]);
        Enfrentamiento::update((int) $enfrentamiento['id'], ['estado' => 'jugado']);

        if ($formato->usaTablaPosiciones()) {
            if ($ganadorId === null) {
                TablaPosicion::aplicarResultado((int) $torneo['id'], $p1, 'empate');
                TablaPosicion::aplicarResultado((int) $torneo['id'], $p2, 'empate');
            } else {
                TablaPosicion::aplicarResultado((int) $torneo['id'], $ganadorId, 'victoria');
                TablaPosicion::aplicarResultado((int) $torneo['id'], $ganadorId === $p1 ? $p2 : $p1, 'derrota');
            }
        }

        $estadoTrasDerrota = $formato->estadoTrasDerrota();
        if ($ganadorId !== null && $estadoTrasDerrota !== null) {
            Participante::update($ganadorId === $p1 ? $p2 : $p1, ['estado' => $estadoTrasDerrota]);
        }

        Auditoria::registrar($usuarioId, 'cargar_resultado', 'enfrentamientos', (int) $enfrentamiento['id'], "{$puntaje1} - {$puntaje2}");

        self::revisarCierre($torneo, Ronda::find((int) $enfrentamiento['ronda_id']), $usuarioId);
    }

    /** Si la ronda ya no tiene enfrentamientos pendientes, la cierra y le pregunta al Formato qué sigue. */
    private static function revisarCierre(array $torneo, array $ronda, ?int $usuarioId = null): void
    {
        if ($ronda['estado'] === 'cerrada' || !Ronda::estaCompleta((int) $ronda['id'])) {
            return;
        }

        Ronda::cerrar((int) $ronda['id']);
        $rondaCerrada = Ronda::find((int) $ronda['id']);

        if (self::formato($torneo['formato_codigo'])->avanzarRonda($torneo, $rondaCerrada)) {
            self::finalizar($torneo, $usuarioId);
        }
    }

    /** Determina el campeón según el formato, cierra el torneo y guarda su historial (RF: estadísticas + título en el perfil). */
    public static function finalizar(array $torneo, ?int $usuarioId = null): void
    {
        $campeon = self::formato($torneo['formato_codigo'])->campeon($torneo);

        Torneo::update((int) $torneo['id'], [
            'estado'    => 'finalizado',
            'fecha_fin' => date('Y-m-d H:i:s'),
        ]);
        TorneoHistorial::calcularYGuardar((int) $torneo['id'], $campeon['id'] ?? null);
        Auditoria::registrar(
            $usuarioId,
            'finalizar_torneo',
            'torneos',
            (int) $torneo['id'],
            $campeon ? "campeón: participante #{$campeon['id']}" : 'sin campeón determinado'
        );
    }

    /** Cantidad total de rondas que va a tener (o tuvo) el torneo, según su formato y su cantidad de competidores activos (equipos si es de equipo, participantes si es individual). Uso presentacional (nombrar rondas). */
    public static function totalRondas(array $torneo): int
    {
        $cantidad = Participante::cantidadCompetidoresActivos((int) $torneo['id'], self::esDeEquipo($torneo));
        return self::formato($torneo['formato_codigo'])->totalRondas($cantidad);
    }

    /** ¿Este torneo es de una disciplina de equipo? (tipos_torneo.modalidad, ver Torneo::porCodigoPublico()). */
    public static function esDeEquipo(array $torneo): bool
    {
        return ($torneo['modalidad'] ?? 'individual') === 'equipo';
    }

    /** ¿Este torneo admite que un enfrentamiento termine en empate? Uso presentacional (el formulario de resultado tipo "decisión"). */
    public static function permiteEmpate(array $torneo): bool
    {
        return self::formato($torneo['formato_codigo'])->permiteEmpate();
    }

    private static function formato(string $codigo): FormatoInterface
    {
        return match ($codigo) {
            'liga'                => new FormatoLiga(),
            'eliminacion_directa' => new FormatoEliminacionDirecta(),
            'suizo'               => new FormatoSuizo(),
            default               => throw new RuntimeException("Formato de competencia desconocido: {$codigo}"),
        };
    }
}
