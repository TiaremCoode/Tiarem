<?php
/**
 * Usuario.php — alineado a la tabla `usuarios`.
 * Representa la cuenta de acceso (no la participación en un torneo
 * puntual, eso es responsabilidad de Participante).
 */
class Usuario extends Model
{
    protected static string $table = 'usuarios';

    public static function porEmail(string $email): ?array
    {
        return self::findBy('email', $email);
    }

    public static function porIdPublico(string $idPublico): ?array
    {
        return self::findBy('id_publico', $idPublico);
    }

    /**
     * Busca a una persona ya registrada por su ID público o por su email
     * exacto. La usa el organizador para anotar participantes: solo
     * necesita ese dato, sin tener que recorrer un listado completo de
     * usuarios (mantiene rápida la inscripción, tal como pide el
     * cliente).
     */
    public static function porIdPublicoOEmail(string $texto): ?array
    {
        $texto = trim($texto);
        if ($texto === '') {
            return null;
        }
        return self::porIdPublico(strtoupper($texto)) ?? self::porEmail($texto);
    }

    /** Búsqueda por texto libre (nombre, apellido o email) para el panel de administración. */
    public static function buscar(string $texto = '', int $limite = 100): array
    {
        $sql = 'SELECT u.*, r.codigo AS rol_codigo, r.nombre AS rol_nombre
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id';
        $params = [];

        if ($texto !== '') {
            $sql .= ' WHERE u.nombre LIKE :t OR u.apellido LIKE :t OR u.email LIKE :t OR u.id_publico = :exacto';
            $params['t'] = "%{$texto}%";
            $params['exacto'] = strtoupper($texto);
        }

        $sql .= ' ORDER BY u.creado_en DESC LIMIT ' . (int) $limite;

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Alta de un usuario con contraseña local (único método de registro
     * de esta versión).
     */
    public static function registrar(string $nombre, string $apellido, string $email, string $passwordPlano): int
    {
        $rolParticipante = Rol::porCodigo(Roles::PARTICIPANTE);

        return self::create([
            'id_publico'    => self::generarCodigoPublico(),
            'nombre'        => $nombre,
            'apellido'      => $apellido,
            'email'         => $email,
            'password_hash' => password_hash($passwordPlano, PASSWORD_BCRYPT),
            'rol_id'        => $rolParticipante['id'],
        ]);
    }

    public static function nombreCompleto(array $usuario): string
    {
        return trim($usuario['nombre'] . ' ' . $usuario['apellido']);
    }

    public static function iniciales(array $usuario): string
    {
        $n = mb_strtoupper(mb_substr($usuario['nombre'], 0, 1));
        $a = mb_strtoupper(mb_substr($usuario['apellido'], 0, 1));
        return $n . $a;
    }

    /**
     * Alta de un usuario hecha por el administrador general, con un rol
     * elegido a mano (a diferencia de registrar(), que siempre asigna el
     * rol "participante"). RF del admin general: "crear... usuarios
     * administrativos".
     */
    public static function crearPorAdmin(string $nombre, string $apellido, string $email, string $passwordPlano, int $rolId): int
    {
        return self::create([
            'id_publico'    => self::generarCodigoPublico(),
            'nombre'        => $nombre,
            'apellido'      => $apellido,
            'email'         => $email,
            'password_hash' => password_hash($passwordPlano, PASSWORD_BCRYPT),
            'rol_id'        => $rolId,
        ]);
    }

    public static function cambiarRol(int $usuarioId, int $rolId): bool
    {
        return self::update($usuarioId, ['rol_id' => $rolId]);
    }

    public static function cambiarEstado(int $usuarioId, string $estado): bool
    {
        return self::update($usuarioId, ['estado' => $estado]);
    }
}
