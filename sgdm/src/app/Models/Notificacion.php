<?php
/**
 * Notificacion.php — alineado a la tabla `notificaciones`.
 * Bandeja de entrada privada de cada usuario. Cubre los cinco casos que
 * pide la letra (invitación, torneo iniciado, aviso del organizador,
 * alguien se unió, torneo completo) con un solo tipo de fila — ver el
 * comentario completo de la tabla en 01_schema.sql.
 */
class Notificacion extends Model
{
    protected static string $table = 'notificaciones';

    public static function deUsuario(int $usuarioId): array
    {
        $stmt = static::db()->prepare(
            'SELECT n.*, t.nombre AS torneo_nombre, t.codigo_publico
             FROM notificaciones n
             LEFT JOIN torneos t ON t.id = n.torneo_id
             WHERE n.usuario_id = ?
             ORDER BY n.leida ASC, n.creado_en DESC'
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public static function cantidadNoLeidas(int $usuarioId): int
    {
        $stmt = static::db()->prepare('SELECT COUNT(*) AS c FROM notificaciones WHERE usuario_id = ? AND leida = 0');
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetch()['c'];
    }

    public static function marcarLeida(int $id): bool
    {
        return self::update($id, ['leida' => true]);
    }

    public static function marcarTodasLeidas(int $usuarioId): void
    {
        $stmt = static::db()->prepare('UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0');
        $stmt->execute([$usuarioId]);
    }

    /** ¿Ya hay una invitación pendiente para esta persona en este torneo? Evita mandarle dos. */
    public static function invitacionPendiente(int $torneoId, int $usuarioId): bool
    {
        $stmt = static::db()->prepare(
            "SELECT id FROM notificaciones
             WHERE torneo_id = ? AND usuario_id = ? AND tipo = 'invitacion' AND estado_invitacion = 'pendiente'
             LIMIT 1"
        );
        $stmt->execute([$torneoId, $usuarioId]);
        return (bool) $stmt->fetch();
    }

    public static function crearInvitacion(int $torneoId, int $usuarioId, ?int $equipoId, string $nombreTorneo): int
    {
        return self::create([
            'usuario_id'        => $usuarioId,
            'torneo_id'         => $torneoId,
            'tipo'              => 'invitacion',
            'mensaje'           => "Te invitaron a sumarte a \"{$nombreTorneo}\".",
            'equipo_id'         => $equipoId,
            'estado_invitacion' => 'pendiente',
        ]);
    }

    /** RF: "avise que un torneo al que pertenece está por empezar" — se dispara al arrancar de verdad (Competencia::iniciar). */
    public static function porTorneoIniciado(array $torneo, array $participantes): void
    {
        foreach ($participantes as $p) {
            self::create([
                'usuario_id' => $p['usuario_id'],
                'torneo_id'  => $torneo['id'],
                'tipo'       => 'torneo_iniciado',
                'mensaje'    => "\"{$torneo['nombre']}\" arrancó — ya podés ver el calendario.",
            ]);
        }
    }

    public static function porAviso(array $torneo, array $participantes, string $mensajeAviso): void
    {
        $extracto = mb_strlen($mensajeAviso) > 160 ? mb_substr($mensajeAviso, 0, 160) . '…' : $mensajeAviso;
        foreach ($participantes as $p) {
            self::create([
                'usuario_id' => $p['usuario_id'],
                'torneo_id'  => $torneo['id'],
                'tipo'       => 'aviso_organizador',
                'mensaje'    => "Nuevo aviso en \"{$torneo['nombre']}\": {$extracto}",
            ]);
        }
    }

    /** Al organizador: alguien se sumó (directo o por invitación aceptada) y, si con eso se llenó, que también se entere. */
    public static function porAltaDeParticipante(array $torneo, array $usuario): void
    {
        self::create([
            'usuario_id' => $torneo['organizador_id'],
            'torneo_id'  => $torneo['id'],
            'tipo'       => 'union_torneo',
            'mensaje'    => Usuario::nombreCompleto($usuario) . " se sumó a \"{$torneo['nombre']}\".",
        ]);

        if (Participante::cantidadActivos((int) $torneo['id']) >= (int) $torneo['max_participantes']) {
            self::create([
                'usuario_id' => $torneo['organizador_id'],
                'torneo_id'  => $torneo['id'],
                'tipo'       => 'torneo_completo',
                'mensaje'    => "\"{$torneo['nombre']}\" ya llegó al máximo de participantes.",
            ]);
        }
    }

    /** Acepta una invitación: valida que el torneo la siga pudiendo recibir y recién ahí crea la inscripción de verdad. */
    public static function aceptarInvitacion(array $notificacion): int
    {
        $torneo = Torneo::find((int) $notificacion['torneo_id']);
        if (!$torneo || $torneo['estado'] !== 'inscripcion') {
            throw new RuntimeException('Ese torneo ya no está en etapa de inscripción.');
        }
        if (Participante::cantidadActivos((int) $torneo['id']) >= (int) $torneo['max_participantes']) {
            throw new RuntimeException('Ese torneo ya llegó al máximo de participantes.');
        }
        if (Participante::yaInscripto((int) $torneo['id'], (int) $notificacion['usuario_id'])) {
            throw new RuntimeException('Ya estás anotado en ese torneo.');
        }

        $participanteId = Participante::create([
            'torneo_id'  => $torneo['id'],
            'usuario_id' => $notificacion['usuario_id'],
            'equipo_id'  => $notificacion['equipo_id'],
        ]);

        self::update((int) $notificacion['id'], ['estado_invitacion' => 'aceptada', 'leida' => true]);
        self::porAltaDeParticipante($torneo, Usuario::find((int) $notificacion['usuario_id']));

        return $participanteId;
    }

    public static function rechazarInvitacion(int $notificacionId): bool
    {
        return self::update($notificacionId, ['estado_invitacion' => 'rechazada', 'leida' => true]);
    }
}
