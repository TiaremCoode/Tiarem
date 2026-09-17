-- ============================================================================
-- SGDM — 03_seed.sql
-- Datos de referencia (catálogos) necesarios para que el sistema funcione
-- desde el primer arranque. A propósito NO se inserta ningún torneo, ronda,
-- participante ni resultado de prueba: la base arranca limpia y el listado
-- de torneos muestra el estado vacío real hasta que un organizador cree el
-- primero.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO roles (codigo, nombre, descripcion) VALUES
    ('admin_general', 'Administrador general', 'Control total del sistema.'),
    ('organizador',   'Organizador',           'Puede crear y administrar sus propios torneos.'),
    ('participante',  'Participante',          'Puede anotarse a torneos y consultar su actividad.');

INSERT INTO modulos_competencia (codigo, nombre, descripcion) VALUES
    ('suizo',               'Sistema suizo',        'Enfrenta a participantes con rendimiento similar, sin repetir rivales.'),
    ('liga',                'Liga',                  'Todos contra todos, con tabla de posiciones y puntaje acumulado.'),
    ('eliminacion_directa', 'Eliminación directa',  'Llaves a un partido. Quien pierde queda eliminado del torneo.');

-- Disciplinas con su organización real: cada una lleva su propia
-- modalidad y, si corresponde, el rango de integrantes por equipo. Esto
-- es lo que permite que el mismo sistema sirva tanto para un torneo de
-- ajedrez (cada participante compite solo) como para uno de fútbol
-- (cada participante representa a un equipo con un mínimo de jugadores).
-- formato_resultado adapta además cómo se carga el resultado de cada
-- partido a lo que realmente se juega en esa disciplina, y
-- sets_para_ganar (solo en formato 'sets') cuántos sets hace falta ganar
-- para cerrar el partido en esa disciplina puntual (pádel se juega al
-- mejor de 3, tenis y vóley al mejor de 5).
INSERT INTO tipos_torneo (nombre, modalidad, jugadores_por_equipo_min, jugadores_por_equipo_max, formato_resultado, sets_para_ganar) VALUES
    ('General',      'individual', NULL, NULL, 'simple',   NULL),
    ('Ajedrez',      'individual', NULL, NULL, 'decision', NULL),
    ('Videojuegos',  'individual', NULL, NULL, 'simple',   NULL),
    ('Tenis',        'individual', NULL, NULL, 'sets',     3),
    ('Fútbol 5',     'equipo', 5, 7, 'simple', NULL),
    ('Vóley',        'equipo', 6, 8, 'sets',   3),
    ('Pádel',        'equipo', 2, 2, 'sets',   2),
    ('Truco',        'equipo', 2, 2, 'simple', NULL);
