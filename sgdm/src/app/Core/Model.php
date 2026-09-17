<?php
/**
 * Model.php
 * Modelo base del que heredan todas las entidades del sistema. Concentra
 * las operaciones CRUD genéricas usando PDO con prepared statements
 * (parámetros nombrados/posicionales, nunca concatenación de strings),
 * como defensa central contra inyección SQL (OWASP Top 10 - A03).
 *
 * Cada modelo concreto (Usuario, Torneo, Participante, etc.) solo declara
 * a qué tabla corresponde y suma los métodos de consulta propios de esa
 * entidad — la mecánica de INSERT/UPDATE/DELETE/SELECT vive acá una sola
 * vez, alineada 1 a 1 con el modelo relacional de db/01_schema.sql.
 */
abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    /**
     * Conexión a usar. Por defecto la de lectura/escritura; los modelos
     * que exponen datos públicos pueden pisar este método para usar
     * ReadOnlyDatabase en sus consultas de solo lectura.
     */
    protected static function db(): PDO
    {
        return Database::connection();
    }

    public static function all(?string $orderBy = null): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return static::db()->query($sql)->fetchAll();
    }

    public static function find($id): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBy(string $column, $value): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM ' . static::$table . " WHERE {$column} = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function where(string $column, $value, ?string $orderBy = null): array
    {
        $sql = 'SELECT * FROM ' . static::$table . " WHERE {$column} = ?";
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        $stmt = static::db()->prepare($sql);
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn ($c) => ":{$c}", $columns);

        $sql = 'INSERT INTO ' . static::$table
             . ' (' . implode(', ', $columns) . ')'
             . ' VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = static::db()->prepare($sql);
        $stmt->execute(self::normalizarValores($data));

        return (int) static::db()->lastInsertId();
    }

    public static function update($id, array $data): bool
    {
        $set = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", array_keys($data)));
        $sql = 'UPDATE ' . static::$table . " SET {$set} WHERE " . static::$primaryKey . ' = :__id';

        $data['__id'] = $id;
        $stmt = static::db()->prepare($sql);
        return $stmt->execute(self::normalizarValores($data));
    }

    /**
     * PDOStatement::execute(array) trata cada valor del array como
     * string salvo que se indique lo contrario — y PHP castea `false` a
     * '' (cadena vacía), no a '0', lo que revienta cualquier columna
     * booleana o numérica al guardar false (bug real encontrado al
     * agregar el primer campo BOOLEAN que se setea en false: ver
     * usuarios.permite_agregado_directo). Se normaliza acá, una sola
     * vez, para que ningún Model concreto tenga que acordarse de
     * castear a mano cada vez que guarda un booleano.
     */
    private static function normalizarValores(array $data): array
    {
        return array_map(fn ($v) => is_bool($v) ? (int) $v : $v, $data);
    }

    public static function delete($id): bool
    {
        $stmt = static::db()->prepare(
            'DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?'
        );
        return $stmt->execute([$id]);
    }

    /**
     * Genera un código público corto (letras mayúsculas y números, sin
     * caracteres ambiguos como 0/O o 1/I) para IDs visibles de usuario y
     * de torneo, tal como piden los requerimientos funcionales.
     */
    protected static function generarCodigoPublico(int $longitud = 8): string
    {
        $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $codigo = '';
        for ($i = 0; $i < $longitud; $i++) {
            $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $codigo;
    }
}
