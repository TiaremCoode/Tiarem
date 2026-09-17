<?php
/**
 * FormatoInterface.php — contrato común a los tres módulos de formato de
 * competencia (letra del proyecto: "Módulo de liga", "Módulo de
 * eliminación directa", "Módulo de sistema suizo"). Cada uno vive en su
 * propio archivo (FormatoLiga.php, FormatoEliminacionDirecta.php,
 * FormatoSuizo.php) e implementa esta interfaz; Core/Competencia.php es
 * el único que los conoce y decide cuál usar según
 * torneos.modulo_competencia_id -> modulos_competencia.codigo.
 *
 * Bajo acoplamiento: a un Formato nunca le importa CÓMO se guarda un
 * resultado ni cómo se cierra una ronda (eso lo maneja Competencia); solo
 * sabe armar cruces y decidir un campeón.
 */
interface FormatoInterface
{
    /** Cantidad total de rondas que tendrá el torneo, dada su cantidad de participantes activos. */
    public function totalRondas(int $cantidadParticipantes): int;

    /** ¿Puede un enfrentamiento de este formato terminar en empate? (eliminación directa necesita siempre un ganador para avanzar). */
    public function permiteEmpate(): bool;

    /** ¿Este formato acumula puntaje en `tabla_posiciones`? (liga y suizo sí; eliminación directa no). */
    public function usaTablaPosiciones(): bool;

    /** Estado que pasa a tener en `participantes.estado` quien pierde un enfrentamiento (NULL si perder un cruce no saca a nadie del torneo, como en liga o suizo). */
    public function estadoTrasDerrota(): ?string;

    /**
     * Genera el arranque de la competencia. En liga arma TODO el
     * calendario de una vez (todas las rondas no dependen de resultados);
     * en eliminación directa y suizo arma solo la ronda 1.
     * @param array $participantes filas de `participantes` activos del torneo
     */
    public function generarPrimeraRonda(array $torneo, array $participantes): void;

    /**
     * Se llama una única vez, justo cuando una ronda se cierra (todos sus
     * enfrentamientos ya tienen resultado). Devuelve true si con esa
     * ronda el torneo debe darse por finalizado; si devuelve false, ya
     * dejó armada (y abierta) la ronda siguiente.
     */
    public function avanzarRonda(array $torneo, array $rondaCerrada): bool;

    /** Participante ganador del torneo (fila de `participantes`), una vez finalizado. */
    public function campeon(array $torneo): ?array;
}
