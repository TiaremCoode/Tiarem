<?php
/**
 * Presentacion.php
 * Utilidades puras de presentación (sin acceso a datos ni permisos) para
 * no repetir el mismo mapeo "estado interno -> clase CSS + texto" en cada
 * vista. Antes de esta corrección, ese ternario estaba copiado igual en
 * home.php, buscar.php, perfil.php y detalle.php (torneo), y otra
 * variante en participantes.php (equipo).
 */
final class Presentacion
{
    /** @return array{0: string, 1: string} [claseCss, textoVisible] */
    public static function tagEstadoTorneo(string $estado): array
    {
        $clase = match ($estado) {
            'en_curso'   => 'en-curso',
            'finalizado' => 'cerrado',
            default      => 'proximo', // inscripcion, cancelado
        };
        return [$clase, ucfirst(str_replace('_', ' ', $estado))];
    }

    /** @return array{0: string, 1: string} [claseCss, textoVisible] */
    public static function tagEstadoEquipo(string $estado): array
    {
        $clase = match ($estado) {
            'completo' => 'en-curso',
            'excedido' => 'cerrado',
            default    => 'proximo', // incompleto
        };
        return [$clase, ucfirst($estado)];
    }

    /**
     * Nombre a mostrar de una ronda, según el formato del torneo:
     * "Fecha N" en liga (término habitual del todos-contra-todos),
     * "Ronda N de TOTAL" en suizo, y el nombre de instancia de eliminación
     * directa (Final / Semifinal / Cuartos / Octavos) contando desde el
     * final hacia atrás según cuántas rondas le queden al torneo.
     */
    public static function nombreRonda(string $formatoCodigo, int $numero, int $totalRondas): string
    {
        if ($formatoCodigo === 'eliminacion_directa') {
            return match ($totalRondas - $numero) {
                0       => 'Final',
                1       => 'Semifinal',
                2       => 'Cuartos de final',
                3       => 'Octavos de final',
                default => "Ronda {$numero}",
            };
        }
        if ($formatoCodigo === 'suizo') {
            return "Ronda {$numero} de {$totalRondas}";
        }
        return "Fecha {$numero}";
    }

    /** @return array{0: string, 1: string} [claseCss, textoVisible] */
    public static function tagEstadoRonda(string $estado): array
    {
        $clase = match ($estado) {
            'abierta' => 'en-curso',
            'cerrada' => 'cerrado',
            default   => 'proximo', // pendiente
        };
        $texto = match ($estado) {
            'abierta' => 'En juego',
            'cerrada' => 'Cerrada',
            default   => 'Por jugar',
        };
        return [$clase, $texto];
    }

    /** Puntaje sin decimales innecesarios: 3.00 -> "3", 2.50 -> "2.5". */
    public static function numero($valor): string
    {
        if ($valor === null) {
            return '—';
        }
        $texto = rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');
        return $texto === '' ? '0' : $texto;
    }

    /** Duración entre dos fechas datetime, en un formato breve y legible. */
    public static function duracion(?string $inicio, ?string $fin): string
    {
        if (!$inicio || !$fin) {
            return '—';
        }
        $segundos = max(0, strtotime($fin) - strtotime($inicio));
        $dias = intdiv($segundos, 86400);
        $horas = intdiv($segundos % 86400, 3600);
        $minutos = intdiv($segundos % 3600, 60);

        $partes = [];
        if ($dias > 0) {
            $partes[] = "{$dias} d";
        }
        if ($horas > 0) {
            $partes[] = "{$horas} h";
        }
        if ($dias === 0 && $minutos > 0) {
            $partes[] = "{$minutos} min";
        }
        return $partes ? implode(' ', $partes) : 'Menos de un minuto';
    }

    /**
     * Texto del resultado de un enfrentamiento ya jugado, adaptado a la
     * disciplina (RF: "que el puntaje se adapte al deporte" — acotado a
     * cómo se muestra/carga el resultado):
     * 'simple'   -> "Resultado: 3–1"
     * 'sets'     -> "3 a 1 sets"
     * 'decision' -> "Ganó Fulano (por tiempo)" / "Empate"
     * Devuelve texto plano sin escapar — quien llama aplica htmlspecialchars().
     */
    public static function resultadoTexto(array $enfrentamiento, string $formatoResultado): string
    {
        if ($formatoResultado === 'decision') {
            if ($enfrentamiento['ganador_id'] === null) {
                $texto = 'Empate';
            } else {
                $ganoP1 = (int) $enfrentamiento['ganador_id'] === (int) $enfrentamiento['participante1_id'];
                $texto = 'Ganó ' . ($ganoP1 ? $enfrentamiento['p1_nombre'] : $enfrentamiento['p2_nombre']);
            }
            $motivo = match ($enfrentamiento['motivo'] ?? 'normal') {
                'tiempo'   => ' (por tiempo)',
                'abandono' => ' (abandono)',
                default    => '',
            };
            return $texto . $motivo;
        }

        $p1 = self::numero($enfrentamiento['puntaje_participante1']);
        $p2 = self::numero($enfrentamiento['puntaje_participante2']);

        return $formatoResultado === 'sets' ? "{$p1} a {$p2} sets" : "Resultado: {$p1}–{$p2}";
    }
}
