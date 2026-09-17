<?php
/**
 * FormatoLiga.php — "Módulo de liga".
 * RF: "crear un calendario de todos contra todos... con una tabla de
 * posiciones con puntajes, victorias y derrotas".
 *
 * El fixture completo NO depende de resultados (a diferencia de
 * eliminación directa o suizo), así que se arma entero de una sola vez
 * con el método del círculo: se fija el primer participante y se van
 * rotando los demás una posición por ronda. Si la cantidad de
 * participantes es impar, se agrega un "descanso" (bye) que en liga NO
 * cuenta como partido — nunca se genera un enfrentamiento para ese
 * descanso, simplemente esa fecha esa persona no juega (a diferencia de
 * eliminación/suizo, donde un bye sí es una victoria automática que hace
 * avanzar de ronda).
 */
class FormatoLiga implements FormatoInterface
{
    public function totalRondas(int $cantidadParticipantes): int
    {
        return $cantidadParticipantes % 2 === 0 ? $cantidadParticipantes - 1 : $cantidadParticipantes;
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
        shuffle($ids); // el orden del fixture no favorece a nadie en particular

        if (count($ids) % 2 !== 0) {
            $ids[] = null; // descanso rotativo
        }

        $cantidadFilas = count($ids);
        $numero = 1;
        for ($ronda = 0; $ronda < $cantidadFilas - 1; $ronda++) {
            $pares = [];
            for ($i = 0; $i < $cantidadFilas / 2; $i++) {
                $a = $ids[$i];
                $b = $ids[$cantidadFilas - 1 - $i];
                if ($a !== null && $b !== null) {
                    $pares[] = [$a, $b];
                }
            }
            // Todas las rondas se crean ya armadas; solo la primera queda
            // "abierta" para jugarse — el resto se abre cuando le toca
            // (ver Competencia::avanzarOFinalizar / FormatoLiga::avanzarRonda).
            $creada = Ronda::crearConEnfrentamientos((int) $torneo['id'], $numero, $pares);
            if ($numero > 1) {
                Ronda::update((int) $creada['id'], ['estado' => 'pendiente', 'fecha_apertura' => null]);
            }
            $numero++;

            // Rotación del método del círculo: el primero queda fijo, el
            // último pasa a la segunda posición y todos los demás se corren uno.
            $ultimo = array_pop($ids);
            array_splice($ids, 1, 0, [$ultimo]);
        }
    }

    public function avanzarRonda(array $torneo, array $rondaCerrada): bool
    {
        $siguiente = Ronda::where('torneo_id', (int) $torneo['id'], 'numero ASC');
        foreach ($siguiente as $r) {
            if ((int) $r['numero'] === (int) $rondaCerrada['numero'] + 1) {
                Ronda::update((int) $r['id'], ['estado' => 'abierta', 'fecha_apertura' => date('Y-m-d H:i:s')]);
                return false;
            }
        }
        return true; // no había ronda siguiente: esa era la última fecha del calendario
    }

    public function campeon(array $torneo): ?array
    {
        $tabla = TablaPosicion::delTorneo((int) $torneo['id']);
        if (empty($tabla)) {
            return null;
        }
        return Participante::find((int) $tabla[0]['participante_id']);
    }
}
