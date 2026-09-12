<?php
/**
 * Torneo.php — alineado a la tabla `torneos`.
 * Las consultas de este modelo que alimentan vistas públicas (inicio,
 * búsqueda, detalle) usan ReadOnlyDatabase explícitamente, para que la
 * restricción de privilegios definida en el DCL (db/02_dcl.sh) se
 * aplique de verdad y no solo quede declarada en la base.
 */
class Torneo extends Model
{
    protected static string $table = 'torneos';

    public static function porCodigoPublico(string $codigo): ?array
    {
        $stmt = ReadOnlyDatabase::connection()->prepare(
            'SELECT t.*, tt.nombre AS tipo_nombre,
                    mc.codigo AS formato_codigo, mc.nombre AS formato_nombre,
                    u.nombre AS organizador_nombre, u.apellido AS organizador_apellido
             FROM torneos t
             JOIN tipos_torneo tt        ON tt.id = t.tipo_torneo_id
             JOIN modulos_competencia mc ON mc.id = t.modulo_competencia_id
             JOIN usuarios u             ON u.id = t.organizador_id
             WHERE t.codigo_publico = ?
             LIMIT 1'
        );
        $stmt->execute([$codigo]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Torneos visibles públicamente, con filtros opcionales de texto,
     * formato y estado. Usada tanto por el inicio (sin filtros, límite
     * chico) como por la búsqueda pública completa.
     */
    public static function buscarPublicos(string $texto = '', string $formatoCodigo = '', string $estado = '', int $limite = 50): array
    {
        $sql = 'SELECT t.id, t.codigo_publico, t.nombre, t.estado, t.max_participantes,
                       mc.codigo AS formato_codigo, mc.nombre AS formato_nombre,
                       (SELECT COUNT(*) FROM participantes p WHERE p.torneo_id = t.id AND p.estado = "activo") AS inscriptos
                FROM torneos t
                JOIN modulos_competencia mc ON mc.id = t.modulo_competencia_id
                JOIN configuraciones_torneo c ON c.torneo_id = t.id
                WHERE c.visible_publico = 1';
        $params = [];

        if ($texto !== '') {
            $sql .= ' AND (t.nombre LIKE :texto OR t.codigo_publico = :codigo_exacto)';
            $params['texto'] = "%{$texto}%";
            $params['codigo_exacto'] = $texto;
        }
        if ($formatoCodigo !== '') {
            $sql .= ' AND mc.codigo = :formato';
            $params['formato'] = $formatoCodigo;
        }
        if ($estado !== '') {
            $sql .= ' AND t.estado = :estado';
            $params['estado'] = $estado;
        }

        $sql .= ' ORDER BY t.fecha_creacion DESC LIMIT ' . (int) $limite;

        $stmt = ReadOnlyDatabase::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Torneos organizados por un usuario, o en los que participa. */
    public static function delUsuario(int $usuarioId): array
    {
        $stmt = static::db()->prepare(
            'SELECT DISTINCT t.id, t.codigo_publico, t.nombre, t.estado, t.fecha_creacion,
                    mc.nombre AS formato_nombre,
                    (t.organizador_id = :uid1) AS es_organizador
             FROM torneos t
             JOIN modulos_competencia mc ON mc.id = t.modulo_competencia_id
             LEFT JOIN participantes p ON p.torneo_id = t.id AND p.usuario_id = :uid2
             WHERE t.organizador_id = :uid3 OR p.usuario_id = :uid4
             ORDER BY t.fecha_creacion DESC'
        );
        $stmt->execute(['uid1' => $usuarioId, 'uid2' => $usuarioId, 'uid3' => $usuarioId, 'uid4' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea el torneo junto con su configuración inicial en una única
     * transacción, para que nunca quede un torneo sin fila de
     * configuración si algo falla a mitad de camino.
     */
    public static function crearConConfiguracion(array $datosTorneo): array
    {
        $db = static::db();
        $db->beginTransaction();
        try {
            $datosTorneo['codigo_publico'] = self::generarCodigoPublico();
            $torneoId = self::create($datosTorneo);

            ConfiguracionTorneo::create([
                'torneo_id' => $torneoId,
            ]);

            $db->commit();
            return self::find($torneoId);
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
