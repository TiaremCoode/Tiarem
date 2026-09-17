<?php
/**
 * FormatoSuizo.php — "Módulo de sistema suizo".
 * RF: "calcular los promedios de cada participante... y enfrentar a los
 * participantes con el promedio más similar posible", "randomizar...
 * evitando que se enfrenten en muchas ocasiones los mismos rivales".
 *
 * La ronda 1 no tiene con qué medir rendimiento todavía, así que se
 * sortea al azar. De ahí en más, cada ronda empareja de a pares
 * consecutivos según la tabla de posiciones (mismo puntaje = "promedio"
 * más parecido posible) y, si dos candidatos ya se enfrentaron antes,
 * busca el siguiente disponible más cercano en la tabla — así nunca se
 * fuerza una revancha salvo que sea matemáticamente inevitable (grupos
 * muy chicos con muchas rondas).
 */
class FormatoSuizo implements FormatoInterface
{
    public function totalRondas(int $cantidadParticipantes): int
    {
        return max(1, (int) ceil(log(max($cantidadParticipantes, 2), 2)));
    }

    public function permiteEmpate(): bool
    {
        return true;
    }

    public function usaTablaPosiciones(): bool
    {
        return true;
    }

    public function estadoTrasDerrota(): ?string
    {
        return null;
    }

    public function generarPrimeraRonda(array $torneo, array $participantes): void
    {
        $ids = array_map(fn ($p) => (int) $p['id'], $participantes);
        shuffle($ids); // sin partidos jugados todavía, el primer cruce es al azar

        $this->crearRondaYRegistrarByes((int) $torneo['id'], 1, $this->emparejarSecuencial($ids));
    }

    public function avanzarRonda(array $torneo, array $rondaCerrada): bool
    {
        // Corrección: en un suizo de equipos, la cantidad de rondas se
        // calcula sobre la cantidad de EQUIPOS en competencia, no sobre
        // la cantidad de jugadores sueltos (ver Competencia::esDeEquipo()
        // y Participante::cantidadCompetidoresActivos()).
        $cantidadCompetidores = Participante::cantidadCompetidoresActivos((int) $torneo['id'], Competencia::esDeEquipo($torneo));
        $totalRondas = $this->totalRondas($cantidadCompetidores);
        if ((int) $rondaCerrada['numero'] >= $totalRondas) {
            return true;
        }

        $ordenados = array_map(
            fn ($fila) => (int) $fila['participante_id'],
            TablaPosicion::delTorneo((int) $torneo['id'])
        );

        $pares = $this->emparejarEvitandoRivales($ordenados, Enfrentamiento::paresJugados((int) $torneo['id']));
        $this->crearRondaYRegistrarByes((int) $torneo['id'], (int) $rondaCerrada['numero'] + 1, $pares);
        return false;
    }

    /**
     * Crea la ronda y, si alguno de sus pares es un pase directo (bye), le
     * suma esa victoria automática en la tabla de posiciones — si no,
     * ese participante queda sin ninguna fila en tabla_posiciones y
     * desaparece de los emparejamientos de las rondas siguientes (que se
     * arman leyendo esa misma tabla).
     */
    private function crearRondaYRegistrarByes(int $torneoId, int $numero, array $pares): array
    {
        $ronda = Ronda::crearConEnfrentamientos($torneoId, $numero, $pares);
        foreach ($pares as [$p1, $p2]) {
            if ($p2 === null) {
                TablaPosicion::aplicarResultado($torneoId, $p1, 'victoria');
            }
        }
        return $ronda;
    }

    public function campeon(array $torneo): ?array
    {
        $tabla = TablaPosicion::delTorneo((int) $torneo['id']);
        if (empty($tabla)) {
            return null;
        }
        return Participante::find((int) $tabla[0]['participante_id']);
    }

    /** Empareja una lista ya en el orden deseado, de a pares consecutivos; a quien sobra (cantidad impar) le toca el pase directo del bye. */
    private function emparejarSecuencial(array $ids): array
    {
        $pares = [];
        for ($i = 0; $i < count($ids); $i += 2) {
            $pares[] = isset($ids[$i + 1]) ? [$ids[$i], $ids[$i + 1]] : [$ids[$i], null];
        }
        return $pares;
    }

    /**
     * Empareja siguiendo el orden de posiciones (mismo puntaje = "promedio"
     * más parecido posible), probando con backtracking evitar por completo
     * cualquier revancha: al mejor ubicado sin rival todavía se lo prueba
     * contra cada candidato —del más cercano al más lejano en la tabla—
     * y, si esa elección deja sin salida al resto de la ronda, se
     * retrocede y se prueba con el siguiente candidato. Con la cantidad
     * de participantes de un torneo de este tipo (decenas, no miles),
     * esto resuelve al instante. Solo si de verdad no existe ninguna
     * combinación sin revanchas (grupos muy chicos con muchas rondas) se
     * arma la ronda igual, aceptando el mínimo de repeticiones posible.
     */
    private function emparejarEvitandoRivales(array $ordenados, array $paresJugados): array
    {
        $intentos = 0;
        $resultado = $this->buscarEmparejamiento($ordenados, $paresJugados, $intentos);
        return $resultado ?? $this->emparejarSecuencial($ordenados);
    }

    /** @return array|null null si ninguna combinación evita por completo las revanchas (o se agotó el presupuesto de búsqueda) */
    private function buscarEmparejamiento(array $pendientes, array $paresJugados, int &$intentos): ?array
    {
        if ($intentos++ > 5000) {
            return null; // presupuesto de búsqueda agotado: se resuelve con el método simple
        }
        if (count($pendientes) <= 1) {
            return $pendientes ? [[$pendientes[0], null]] : []; // impar: pase directo para quien queda sin rival
        }

        $a = $pendientes[0];
        $resto = array_values(array_slice($pendientes, 1));

        foreach ($resto as $indice => $candidato) {
            if (isset($paresJugados[Enfrentamiento::clavePar($a, $candidato)])) {
                continue;
            }
            $siguientes = $resto;
            unset($siguientes[$indice]);

            $sub = $this->buscarEmparejamiento(array_values($siguientes), $paresJugados, $intentos);
            if ($sub !== null) {
                return array_merge([[$a, $candidato]], $sub);
            }
        }

        return null;
    }
}
