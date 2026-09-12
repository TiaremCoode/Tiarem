<?php
/**
 * Auditoria.php — alineado a la tabla `auditoria`.
 * Cumple el requerimiento no funcional/general de que todo cambio quede
 * registrado en la base de datos. Se llama desde los puntos clave de los
 * controladores (login, logout, alta de usuario, alta de torneo, carga de
 * resultado) en vez de intentar registrar cambios de forma automática con
 * triggers, para poder guardar además el motivo/detalle de cada acción.
 */
class Auditoria extends Model
{
    protected static string $table = 'auditoria';

    public static function registrar(?int $usuarioId, string $accion, string $entidad, ?int $entidadId, ?string $detalle = null): void
    {
        self::create([
            'usuario_id' => $usuarioId,
            'accion'     => $accion,
            'entidad'    => $entidad,
            'entidad_id' => $entidadId,
            'detalle'    => $detalle,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public static function recientes(int $limite = 50): array
    {
        $stmt = static::db()->prepare(
            'SELECT a.*, u.nombre, u.apellido
             FROM auditoria a
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             ORDER BY a.creado_en DESC
             LIMIT ' . (int) $limite
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
