-- ============================================================================
-- Migración 001 — sets_para_ganar + disciplina "Tenis"
--
-- Por qué existe este archivo (y no alcanza con el 01_schema.sql actualizado):
-- los scripts de db/ (01_schema.sql, 02_dcl.sh, 03_seed.sql) los corre MySQL
-- una única vez, la primera vez que arranca con el volumen de datos vacío
-- (ver el comentario en docker-compose.yml, servicio "db"). Si ya habías
-- levantado el proyecto antes de esta corrección —y ya tenés torneos
-- cargados, como el de fútbol de la prueba—, tu base de datos ya existe y
-- esos scripts NO se vuelven a ejecutar solos: hay que aplicar el cambio a
-- mano, una sola vez, con este script. (01_schema.sql y 03_seed.sql quedan
-- igual actualizados para cuando el proyecto se levante de cero, por
-- ejemplo en el servidor final).
--
-- Cómo correrlo (una sola vez), desde la carpeta del proyecto — reemplazá
-- TU_MYSQL_ROOT_PASSWORD y TU_BASE por los valores reales de tu archivo
-- .env (MYSQL_ROOT_PASSWORD y MYSQL_DATABASE, los mismos que usaste al
-- levantar el proyecto):
--
--   docker compose exec -T db mysql -u root -pTU_MYSQL_ROOT_PASSWORD TU_BASE < db/migraciones/001_sets_para_ganar_y_tenis.sql
--
-- (sin espacio entre -p y la contraseña. La "-T" es necesaria para que
-- funcione bien la redirección del archivo con "<").
-- ============================================================================

-- 1) La columna nueva, todavía sin CHECK (se agrega recién al final: el
--    CHECK exige que toda fila 'sets' tenga un valor, y en este punto
--    Vóley/Pádel todavía no lo tienen).
ALTER TABLE tipos_torneo
  ADD COLUMN sets_para_ganar SMALLINT UNSIGNED NULL AFTER formato_resultado;

-- 2) Se completa el valor para las disciplinas 'sets' que ya existían.
UPDATE tipos_torneo SET sets_para_ganar = 3 WHERE nombre = 'Vóley';
UPDATE tipos_torneo SET sets_para_ganar = 2 WHERE nombre = 'Pádel';

-- 3) Se agrega la disciplina "Tenis" (individual, por sets) si todavía no
--    existe — por si esta migración se corre más de una vez por las dudas.
INSERT INTO tipos_torneo (nombre, modalidad, jugadores_por_equipo_min, jugadores_por_equipo_max, formato_resultado, sets_para_ganar)
SELECT 'Tenis', 'individual', NULL, NULL, 'sets', 3
WHERE NOT EXISTS (SELECT 1 FROM tipos_torneo WHERE nombre = 'Tenis');

-- 4) Recién ahora se agrega el CHECK: toda fila 'sets' ya tiene su valor
--    (paso 2 y 3), así que no lo puede violar ninguna fila existente.
ALTER TABLE tipos_torneo
  ADD CONSTRAINT chk_tipo_torneo_sets_para_ganar CHECK (
      (formato_resultado <> 'sets' AND sets_para_ganar IS NULL)
      OR
      (formato_resultado = 'sets' AND sets_para_ganar IS NOT NULL AND sets_para_ganar >= 1)
  );
