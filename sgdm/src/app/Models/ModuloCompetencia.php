<?php
/**
 * ModuloCompetencia.php — alineado a la tabla `modulos_competencia`
 * (los tres formatos mínimos: liga, eliminación directa, suizo).
 */
class ModuloCompetencia extends Model
{
    protected static string $table = 'modulos_competencia';

    public static function porCodigo(string $codigo): ?array
    {
        return self::findBy('codigo', $codigo);
    }

    public static function todosOrdenados(): array
    {
        return self::all('id ASC');
    }
}
