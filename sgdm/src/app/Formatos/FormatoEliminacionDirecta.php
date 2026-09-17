<?php
/**
 * FormatoEliminacionDirecta.php — "Módulo de eliminación directa".
 * RF: "crear 2 lados de llaves, donde se jueguen octavos, cuartos,
 * semifinales o final, dependiendo el número de participantes... el que
 * pierde queda eliminado, y el vencedor pasa a la siguiente ronda".
 *
 * La llave se sortea una sola vez, al azar, y se completa con byes (pases
 * directos) hasta la potencia de 2 más cercana. Emparejar de a pares
 * consecutivos sobre una lista ya mezclada arma naturalmente los "2
 * lados" de la llave: la primera mitad de los cruces de la ronda 1 forma
 * un lado del cuadro, la segunda mitad el otro, y ambos lados se van
 * reduciendo a la mitad ronda a ronda hasta encontrarse en la final.
 */
class FormatoEliminacionDirecta implements FormatoInterface
{
    public function totalRondas(int $cantidadParticipantes): int
    {
        return (int) ceil(log(max($cantidadParticipantes, 2), 2));
    }

    public function permiteEmpate(): bool
    {
        return false; // siempre tiene que haber un ganador que avance
    }

    public function usaTablaPosiciones(): bool
    {
        return false; // acá el resultado se lee directo de la llave, no de un acumulado de puntos
    }

    public function estadoTrasDerrota(): ?string
    {
        return 'eliminado';
    }

    public function generarPrimeraRonda(array $torneo, array $participantes): void
    {
        $ids = array_map(fn ($p) => (int) $p['id'], $participantes);
        shuffle($ids); // sorteo de la llave

        $tamanioLlave = 2 ** $this->totalRondas(count($ids));
        $cantidadByes = $tamanioLlave - count($ids);

        // Los primeros $cantidadByes participantes (ya mezclados al azar)
        // reciben un pase directo cada uno. Nunca puede haber más byes
        // que la mitad de los cupos de la llave, así que jamás quedan dos
        // byes enfrentados entre sí.
        $pares = [];
        for ($i = 0; $i < $cantidadByes; $i++) {
            $pares[] = [$ids[$i], null];
        }
        for ($i = $cantidadByes; $i < count($ids); $i += 2) {
            $pares[] = [$ids[$i], $ids[$i + 1]];
        }

        Ronda::crearConEnfrentamientos((int) $torneo['id'], 1, $pares);
    }

    public function avanzarRonda(array $torneo, array $rondaCerrada): bool
    {
        $ganadores = Enfrentamiento::ganadoresDeRonda((int) $rondaCerrada['id']);

        if (count($ganadores) <= 1) {
            return true; // esa ronda era la final: ya hay campeón
        }

        $pares = [];
        for ($i = 0; $i < count($ganadores); $i += 2) {
            $pares[] = [$ganadores[$i], $ganadores[$i + 1]];
        }

        Ronda::crearConEnfrentamientos((int) $torneo['id'], (int) $rondaCerrada['numero'] + 1, $pares);
        return false;
    }

    public function campeon(array $torneo): ?array
    {
        $rondas = Ronda::where('torneo_id', (int) $torneo['id'], 'numero DESC');
        if (empty($rondas)) {
            return null;
        }
        $ultima = $rondas[0];
        $ganadores = Enfrentamiento::ganadoresDeRonda((int) $ultima['id']);
        return $ganadores ? Participante::find($ganadores[0]) : null;
    }
}
