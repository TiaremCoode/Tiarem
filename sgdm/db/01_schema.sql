-- ============================================================================
-- SGDM — Sistema de Gestión Deportiva Modular
-- 01_schema.sql — Modelo relacional normalizado (DDL)
--
-- Normalización: todas las tablas cumplen 3FN.
--   1FN: todos los atributos son atómicos (sin listas ni valores repetidos
--        dentro de una misma columna).
--   2FN: no hay dependencias parciales — todas las tablas usan clave
--        primaria simple (id autoincremental), por lo que 2FN se cumple
--        automáticamente sobre 1FN.
--   3FN: no hay dependencias transitivas — los datos que dependen de otra
--        entidad (ej: nombre del rol, nombre del formato) se resolvieron
--        en tablas de referencia (roles, modulos_competencia, tipos_torneo)
--        en vez de repetirse como texto suelto en cada fila.
--
-- Este script lo ejecuta el contenedor de MySQL en el arranque, contra el
-- usuario root del contenedor (ver docker-entrypoint-initdb.d en el
-- docker-compose). El usuario de la aplicación (sgdm_app) se crea después,
-- en 02_dcl.sh, con privilegios restringidos sobre esta base.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- roles
-- Catálogo de roles del sistema. Se resuelve en tabla propia (en vez de un
-- ENUM en `usuarios`) para poder agregar roles nuevos sin alterar la
-- estructura de la tabla de usuarios, y para no repetir el nombre del rol
-- como texto libre en cada fila de usuario (3FN).
-- ----------------------------------------------------------------------------
CREATE TABLE roles (
    id              TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30)  NOT NULL UNIQUE,   -- admin_general | organizador | participante
    nombre          VARCHAR(60)  NOT NULL,
    descripcion     VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- usuarios
-- Cuenta de acceso al sistema. El participante de un torneo puntual se
-- modela en la tabla `participantes`; esta tabla es la identidad única de
-- la persona, reutilizable entre torneos.
-- ----------------------------------------------------------------------------
CREATE TABLE usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_publico      CHAR(8)      NOT NULL UNIQUE,   -- ID único visible (RF: "otorgar un ID única a cada usuario")
    nombre          VARCHAR(80)  NOT NULL,
    apellido        VARCHAR(80)  NOT NULL,
    email           VARCHAR(160) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,            -- toda cuenta se crea con contraseña propia
    rol_id          TINYINT UNSIGNED NOT NULL,
    estado          ENUM('activo','suspendido') NOT NULL DEFAULT 'activo',
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_acceso   DATETIME     NULL,

    CONSTRAINT fk_usuarios_rol
        FOREIGN KEY (rol_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_usuarios_rol ON usuarios(rol_id);

-- ----------------------------------------------------------------------------
-- tipos_torneo
-- Disciplina/categoría del torneo (ajedrez, fútbol 5, truco, etc.). Es un
-- catálogo abierto: el organizador puede elegir uno existente al crear el
-- torneo o dejarlo en "General" para no complicar el alta rápida.
--
-- A partir de esta corrección, cada disciplina lleva su propia
-- organización: `modalidad` indica si en esa disciplina cada participante
-- compite solo (ajedrez, videojuegos 1v1) o representando a un equipo
-- (fútbol, vóley, pádel). Cuando la modalidad es 'equipo', el rango
-- jugadores_por_equipo_min/max define cuántos integrantes se esperan por
-- equipo — el módulo de participantes (participantes.php /
-- ParticipanteController) usa este rango para avisarle al organizador si
-- un equipo quedó incompleto o si se pasó del máximo, sin tener que
-- programar esa regla de nuevo para cada deporte.
-- ----------------------------------------------------------------------------
CREATE TABLE tipos_torneo (
    id                          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre                      VARCHAR(60) NOT NULL UNIQUE,
    modalidad                   ENUM('individual','equipo') NOT NULL DEFAULT 'individual',
    jugadores_por_equipo_min    SMALLINT UNSIGNED NULL,
    jugadores_por_equipo_max    SMALLINT UNSIGNED NULL,

    CONSTRAINT chk_tipo_torneo_rango_equipo CHECK (
        (modalidad = 'individual' AND jugadores_por_equipo_min IS NULL AND jugadores_por_equipo_max IS NULL)
        OR
        (modalidad = 'equipo' AND jugadores_por_equipo_min IS NOT NULL AND jugadores_por_equipo_max IS NOT NULL
         AND jugadores_por_equipo_max >= jugadores_por_equipo_min)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- modulos_competencia
-- Los tres formatos mínimos exigidos: liga, eliminación directa, suizo.
-- Se modelan como catálogo (y no como ENUM en `torneos`) porque cada
-- formato tiene nombre, descripción y reglas propias que hoy se muestran
-- en el frontend (tarjetas de "Formatos") y que a futuro pueden crecer
-- (ej: reglas de desempate) sin tocar la tabla de torneos.
-- ----------------------------------------------------------------------------
CREATE TABLE modulos_competencia (
    id              TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30)  NOT NULL UNIQUE,   -- liga | eliminacion_directa | suizo
    nombre          VARCHAR(60)  NOT NULL,
    descripcion     VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- torneos
-- Entidad central. Guarda solo los datos propios del torneo; la
-- configuración opcional (banderas de comportamiento) se separó a
-- `configuraciones_torneo` en una relación 1:1 para no llenar esta tabla
-- de columnas nulas específicas de cada formato (3FN).
-- ----------------------------------------------------------------------------
CREATE TABLE torneos (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_publico          CHAR(8)      NOT NULL UNIQUE,  -- RF: "ID único... los usuarios pueden buscarlo"
    nombre                  VARCHAR(120) NOT NULL,
    tipo_torneo_id          SMALLINT UNSIGNED NOT NULL,
    modulo_competencia_id   TINYINT UNSIGNED NOT NULL,
    organizador_id          INT UNSIGNED NOT NULL,
    max_participantes       SMALLINT UNSIGNED NOT NULL,
    estado                  ENUM('inscripcion','en_curso','finalizado','cancelado')
                                 NOT NULL DEFAULT 'inscripcion',
    fecha_creacion          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio            DATETIME NULL,
    fecha_fin               DATETIME NULL,

    CONSTRAINT fk_torneos_tipo
        FOREIGN KEY (tipo_torneo_id) REFERENCES tipos_torneo(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_torneos_modulo
        FOREIGN KEY (modulo_competencia_id) REFERENCES modulos_competencia(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_torneos_organizador
        FOREIGN KEY (organizador_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_torneos_max_participantes
        CHECK (max_participantes >= 2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_torneos_estado ON torneos(estado);
CREATE INDEX idx_torneos_organizador ON torneos(organizador_id);

-- ----------------------------------------------------------------------------
-- configuraciones_torneo (1:1 con torneos)
-- ----------------------------------------------------------------------------
CREATE TABLE configuraciones_torneo (
    id                              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id                       INT UNSIGNED NOT NULL UNIQUE,
    visible_publico                 BOOLEAN NOT NULL DEFAULT TRUE,
    permite_correccion_resultados   BOOLEAN NOT NULL DEFAULT FALSE,
    evitar_repetir_rivales          BOOLEAN NOT NULL DEFAULT TRUE, -- relevante en formato suizo

    CONSTRAINT fk_config_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- equipos
-- Entidad independiente del torneo (un equipo puede anotarse a varios
-- torneos a lo largo del tiempo). La pertenencia de un equipo a un torneo
-- puntual queda representada a través de `participantes`.
-- ----------------------------------------------------------------------------
CREATE TABLE equipos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    creado_por      INT UNSIGNED NOT NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_equipos_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- participantes
-- Representa la inscripción de un usuario a un torneo puntual (RF: solo
-- usuarios registrados pueden anotarse; el organizador carga nombre,
-- apellido e id de usuario al anotarlos). Es la entidad sobre la que se
-- arman enfrentamientos y tabla de posiciones — nunca se referencia al
-- usuario directamente en esas tablas, para poder distinguir la misma
-- persona participando en dos torneos distintos al mismo tiempo.
-- ----------------------------------------------------------------------------
CREATE TABLE participantes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id           INT UNSIGNED NOT NULL,
    usuario_id          INT UNSIGNED NOT NULL,
    equipo_id           INT UNSIGNED NULL,
    estado              ENUM('activo','eliminado','retirado') NOT NULL DEFAULT 'activo',
    fecha_inscripcion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_participantes_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_participantes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_participantes_equipo
        FOREIGN KEY (equipo_id) REFERENCES equipos(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT uq_participante_por_torneo UNIQUE (torneo_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_participantes_torneo ON participantes(torneo_id);

-- ----------------------------------------------------------------------------
-- rondas
-- ----------------------------------------------------------------------------
CREATE TABLE rondas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id       INT UNSIGNED NOT NULL,
    numero          SMALLINT UNSIGNED NOT NULL,
    estado          ENUM('pendiente','abierta','cerrada') NOT NULL DEFAULT 'pendiente',
    fecha_apertura  DATETIME NULL,
    fecha_cierre    DATETIME NULL,

    CONSTRAINT fk_rondas_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT uq_ronda_por_torneo UNIQUE (torneo_id, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- enfrentamientos
-- participante2_id puede ser NULL para representar un "bye" (pase directo)
-- en eliminación directa cuando el número de participantes no es potencia
-- de 2.
-- ----------------------------------------------------------------------------
CREATE TABLE enfrentamientos (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ronda_id            INT UNSIGNED NOT NULL,
    participante1_id    INT UNSIGNED NOT NULL,
    participante2_id    INT UNSIGNED NULL,
    orden               SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- posición dentro de la llave/ronda
    estado              ENUM('pendiente','jugado','walkover') NOT NULL DEFAULT 'pendiente',

    CONSTRAINT fk_enfrentamientos_ronda
        FOREIGN KEY (ronda_id) REFERENCES rondas(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_enfrentamientos_p1
        FOREIGN KEY (participante1_id) REFERENCES participantes(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    CONSTRAINT fk_enfrentamientos_p2
        FOREIGN KEY (participante2_id) REFERENCES participantes(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,

    CONSTRAINT chk_enfrentamiento_rivales_distintos
        CHECK (participante2_id IS NULL OR participante1_id <> participante2_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_enfrentamientos_ronda ON enfrentamientos(ronda_id);

-- ----------------------------------------------------------------------------
-- resultados (1:1 con enfrentamientos)
-- ----------------------------------------------------------------------------
CREATE TABLE resultados (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enfrentamiento_id       INT UNSIGNED NOT NULL UNIQUE,
    puntaje_participante1   DECIMAL(6,2) NOT NULL DEFAULT 0,
    puntaje_participante2   DECIMAL(6,2) NULL,
    ganador_id              INT UNSIGNED NULL,   -- NULL = empate
    cargado_por             INT UNSIGNED NOT NULL,
    fecha_carga             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_resultados_enfrentamiento
        FOREIGN KEY (enfrentamiento_id) REFERENCES enfrentamientos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_resultados_ganador
        FOREIGN KEY (ganador_id) REFERENCES participantes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_resultados_cargado_por
        FOREIGN KEY (cargado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- tabla_posiciones
-- Se mantiene como tabla física (en vez de calcularla siempre al vuelo)
-- porque el RF pide mostrarla públicamente con buen tiempo de respuesta;
-- se recalcula al cargar cada resultado.
-- ----------------------------------------------------------------------------
CREATE TABLE tabla_posiciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id       INT UNSIGNED NOT NULL,
    participante_id INT UNSIGNED NOT NULL,
    puntos          DECIMAL(7,2) NOT NULL DEFAULT 0,
    victorias       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    derrotas        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    empates         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_posiciones_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_posiciones_participante
        FOREIGN KEY (participante_id) REFERENCES participantes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT uq_posicion_por_torneo UNIQUE (torneo_id, participante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_posiciones_torneo ON tabla_posiciones(torneo_id, puntos DESC);

-- ----------------------------------------------------------------------------
-- auditoria
-- Registro de trazabilidad general (RNF: "deben quedar registrados todos
-- los cambios en la base de datos"). usuario_id nulo = acción del sistema.
-- ----------------------------------------------------------------------------
CREATE TABLE auditoria (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NULL,
    accion          VARCHAR(60)  NOT NULL,   -- ej: 'crear_torneo', 'login', 'cargar_resultado'
    entidad         VARCHAR(60)  NOT NULL,   -- ej: 'torneos', 'usuarios'
    entidad_id      INT UNSIGNED NULL,
    detalle         TEXT NULL,
    ip              VARCHAR(45)  NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_auditoria_usuario ON auditoria(usuario_id);
CREATE INDEX idx_auditoria_fecha ON auditoria(creado_en);

SET FOREIGN_KEY_CHECKS = 1;
