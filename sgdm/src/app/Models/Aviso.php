<?php
/**
 * Aviso.php — alineado a la tabla `avisos`.
 * Cartelera pública y permanente del organizador sobre SU torneo (ver el
 * comentario completo de la tabla en 01_schema.sql). Publicar un aviso
 * además genera una notificación privada para cada participante activo
 * — eso lo hace Notificacion::porAviso(), llamada desde
 * AvisoController::store(), no este modelo.
 */
class Aviso extends Model
{
    protected static string $table = 'avisos';

    public static function delTorneo(int $torneoId): array
    {
        return self::where('torneo_id', $torneoId, 'creado_en DESC');
    }
}
