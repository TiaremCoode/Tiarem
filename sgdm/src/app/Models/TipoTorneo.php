<?php
/**
 * TipoTorneo.php — alineado a la tabla `tipos_torneo` (disciplina o
 * categoría del torneo: ajedrez, fútbol 5, etc).
 *
 * A partir de la corrección de la 2ª entrega, cada disciplina define su
 * propia organización: si se compite de forma individual o por equipos,
 * y en ese segundo caso, cuántos integrantes se esperan por equipo. El
 * módulo de participantes (ParticipanteController) usa estos datos para
 * saber si al inscribir a alguien hace falta pedirle un equipo, y para
 * avisar si un equipo quedó incompleto o excedido — sin tener que
 * repetir esa lógica para cada deporte.
 */
class TipoTorneo extends Model
{
    protected static string $table = 'tipos_torneo';

    public static function esDeEquipo(array $tipoTorneo): bool
    {
        return $tipoTorneo['modalidad'] === 'equipo';
    }

    /**
     * Compara la cantidad actual de integrantes de un equipo contra el
     * rango esperado para la disciplina. Devuelve 'incompleto', 'completo'
     * o 'excedido' — o null si la disciplina es individual (no aplica).
     */
    public static function estadoDelEquipo(array $tipoTorneo, int $cantidadIntegrantes): ?string
    {
        if (!self::esDeEquipo($tipoTorneo)) {
            return null;
        }

        if ($cantidadIntegrantes < (int) $tipoTorneo['jugadores_por_equipo_min']) {
            return 'incompleto';
        }
        if ($cantidadIntegrantes > (int) $tipoTorneo['jugadores_por_equipo_max']) {
            return 'excedido';
        }
        return 'completo';
    }
}
