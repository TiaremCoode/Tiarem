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
}
