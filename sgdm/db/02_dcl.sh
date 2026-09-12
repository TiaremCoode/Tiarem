#!/bin/bash
# ============================================================================
# SGDM — 02_dcl.sh
# DCL (Data Control Language): crea los usuarios de base de datos que va a
# usar la aplicación, cada uno con el mínimo privilegio necesario. Ninguno
# de estos usuarios tiene GRANT OPTION ni acceso a la base `mysql` interna,
# así que aunque alguien comprometiera las credenciales de la app, no podría
# crear usuarios nuevos ni modificar la estructura de la base.
#
# Se ejecuta automáticamente al iniciar el contenedor de MySQL por estar en
# docker-entrypoint-initdb.d/, usando las variables de entorno definidas en
# docker-compose.yml (que a su vez vienen del .env).
#
# Nota de diseño: no hace falta un REVOKE explícito sobre la base `mysql`
# interna. Un usuario recién creado con CREATE USER arranca sin ningún
# privilegio en ningún lado hasta que se le otorga uno con GRANT — como acá
# solo se le da GRANT sobre la base de la aplicación, "mysql" queda fuera de
# su alcance por defecto. (Un REVOKE sobre un privilegio que nunca se
# otorgó, de hecho, tira error en MySQL: "There is no such grant defined".)
#
# Usuarios que se crean:
#   sgdm_app       -> el que usa la aplicación PHP para todo lo que implica
#                     escribir datos (crear torneos, inscribir participantes,
#                     cargar resultados, etc). SELECT + INSERT + UPDATE +
#                     DELETE sobre las tablas de negocio. Sin DROP, sin
#                     ALTER, sin CREATE, sin acceso a otras bases.
#
#   sgdm_readonly  -> el que usa la aplicación para todo lo que es consulta
#                     pública (buscar torneos, ver detalle, tablas de
#                     posiciones) sin necesidad de sesión iniciada. Solo
#                     SELECT. Si existiera una falla en ese endpoint público,
#                     este usuario no tiene forma de alterar ni un solo
#                     registro.
# ============================================================================
set -e

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    -- Usuario de lectura/escritura para la aplicación autenticada
    CREATE USER IF NOT EXISTS 'sgdm_app'@'%' IDENTIFIED BY '${SGDM_APP_DB_PASSWORD}';
    GRANT SELECT, INSERT, UPDATE, DELETE
        ON \`${MYSQL_DATABASE}\`.*
        TO 'sgdm_app'@'%';

    -- Usuario exclusivo de solo lectura para las vistas públicas
    CREATE USER IF NOT EXISTS 'sgdm_readonly'@'%' IDENTIFIED BY '${SGDM_READONLY_DB_PASSWORD}';
    GRANT SELECT
        ON \`${MYSQL_DATABASE}\`.*
        TO 'sgdm_readonly'@'%';

    FLUSH PRIVILEGES;
EOSQL

echo "DCL aplicado: usuarios sgdm_app (lectura/escritura) y sgdm_readonly (solo lectura) creados."
