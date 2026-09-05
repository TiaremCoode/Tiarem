# X La Copa — SGDM · Segunda etapa (Programación Full Stack)

Backend real sobre el maquetado de la primera etapa: base de datos MySQL
normalizada, DCL con usuarios restringidos, modelos PHP orientados a
objetos alineados al modelo relacional, gestión de usuarios (registro,
login y administración de cuentas desde el rol admin general), un
módulo real de participantes y equipos
preparado para cualquier disciplina, manejo centralizado de errores, y
todo corriendo sobre Apache, dockerizado.

## Qué incluye esta entrega (y qué no)

Esta etapa cubre lo pedido en la consigna: modelo relacional
normalizado, DCL, usuarios de base de datos con restricciones, modelos en
PHP OOP, integración PHP OOP, gestión de usuarios funcionando y
despliegue con Apache. Ver la tabla de trazabilidad más abajo.

Esta versión corrige los huecos que había quedado la vuelta anterior:
- El admin general ahora puede de verdad **crear, editar el rol y
  eliminar usuarios** (antes solo podía consultar auditoría).
- Existe un **módulo real de participantes y equipos** (antes la tabla
  `equipos` estaba en la base pero ningún controlador la usaba).
- Hay **manejo centralizado de errores**: una excepción sin capturar ya
  no muestra un error crudo de PHP, sino una pantalla prolija.
- El `DELETE` de `Model.php` ahora se usa de verdad en dos lugares (dar
  de baja a un participante durante la inscripción, y eliminar una
  cuenta de usuario sin actividad todavía).

**Lo que NO incluye a propósito:** los algoritmos de emparejamiento
automático de cada formato (armar llaves de eliminación directa, calcular
próximas rondas del sistema suizo, generar el fixture de liga) no están
implementados todavía — eso corresponde a los módulos de liga /
eliminación directa / suizo, que son de una envergadura propia y quedan
para la siguiente etapa. El modelo de datos ya está preparado para ellos
(tablas `rondas`, `enfrentamientos`, `resultados`, `tabla_posiciones`), y
el panel del organizador muestra dónde van a ir esas acciones, con una
nota aclaratoria en vez de fingir que funcionan.

## Revisión de esta entrega antes de pasar a la tercera etapa

Antes de avanzar se repasó todo el código de esta etapa contra la
consigna y se corrigió lo siguiente:

- **Bug real, no solo estilo**: el `Dockerfile` instalaba `pdo`,
  `pdo_mysql`, `mysqli` y `curl`, pero no `mbstring` — extensión que la
  imagen base `php:8.2-apache` NO trae compilada por defecto. El código
  usa `mb_strlen()` (validar largo de contraseña y de nombre de torneo)
  y `mb_strtoupper()`/`mb_substr()` (iniciales del avatar en el perfil),
  así que **el registro, el alta de usuario por el admin y la creación
  de torneos tiraban error fatal apenas se probaban dentro del
  contenedor real**, aunque el código se viera bien y corriera sin
  problema en un PHP local que ya trajera esa extensión instalada. Se
  agregó `mbstring` (y `libonig-dev`, la librería que necesita para
  compilar) a `docker/php/Dockerfile`.
- **Unificación de vistas**: cuatro vistas (`home`, `buscar`, `perfil`,
  `detalle`) repetían el mismo ternario para pintar el estado de un
  torneo, y `participantes` tenía uno parecido para equipos. Se movieron
  a `app/Core/Presentacion.php`.
- **Unificación de HTML repetido**: la banda de mensaje/error de
  `perfil`, `participantes` y `admin/usuarios` (con sus estilos en
  línea) pasó a `app/Views/partials/mensajes.php`; el esqueleto idéntico
  de `errors/403.php` y `errors/404.php` pasó a
  `app/Views/partials/error-page.php`.
- **Estilos en línea → CSS**: todo `style="..."` suelto que había en las
  vistas (páginas de error, formulario de admin, banda de mensajes) se
  convirtió en clases de `base.css` (`.error-page*`, `.page-banner*`,
  `.btn-outline-danger`, `.field-hint-center`, `.back-link` ahora
  compartida) para no romper el requisito de tipografía y estilos
  consistentes en toda la app.
- Se revisó el resto de la consigna (modelo relacional, DCL, roles,
  seguridad) y no se encontraron otras incompatibilidades; el detalle
  está en el documento de trazabilidad de esta revisión.

## Ajustes posteriores a la revisión

- **Tercer bug real de base de datos: `Torneo::delUsuario()` rompía "Mis
  torneos" apenas alguien entraba a su perfil.** La consulta usaba
  `SELECT DISTINCT` pero ordenaba por `t.fecha_creacion`, columna que no
  estaba en esa lista — MySQL 8 no lo permite (error 3065). Se agregó
  `t.fecha_creacion` al `SELECT` (no cambia el resultado: depende 1 a 1
  de `t.id`, que ya estaba en el `DISTINCT`, así que no puede generar
  filas de más).
- **Validación de punta a punta contra MySQL real**, no solo `php -l` y
  pruebas con datos simulados: se instaló MySQL 8.0.46 (la misma
  versión de la imagen del proyecto) y se levantó la aplicación
  completa contra ella con el servidor embebido de PHP, simulando con
  `curl` los flujos reales: registro, login, perfil, creación de
  torneo individual y por equipos, alta de participante, búsqueda
  pública, filtros, y el panel de administración completo (listado,
  alta, edición, auditoría). Así se encontraron los tres bugs de base
  de datos de esta sección — ninguno aparecía con `php -l` porque los
  tres solo se manifiestan al ejecutar SQL real contra un MySQL real.
- **Dos bugs reales en la base, encontrados corriendo todo contra un
  MySQL 8.0.46 de verdad** (hasta acá se había verificado con `php -l`
  y pruebas funcionales sin base de datos — esta vez se instaló MySQL
  8.0.46 real, la misma versión exacta de la imagen del proyecto, y se
  corrió `01_schema.sql` + `02_dcl.sh` + `03_seed.sql` de punta a punta):
  1. `01_schema.sql` no llegaba a crear ni una sola tabla completa:
     MySQL rechazaba `enfrentamientos` con el error 3823 porque
     `participante1_id`/`participante2_id` tenían `ON UPDATE CASCADE`
     en su FK **y** participaban en el `CHECK`
     `chk_enfrentamiento_rivales_distintos` — MySQL no permite that
     combinación, porque una cascada podría cambiarle el valor a una
     columna que el `CHECK` tiene que poder seguir validando. Se pasó
     esas dos FK a `ON UPDATE RESTRICT` (coherente con el `ON DELETE
     RESTRICT` que ya tenían, y sin efecto práctico real ya que el `id`
     de un participante nunca se actualiza).
  2. Como `01_schema.sql` fallaba, Docker nunca llegaba a correr
     `02_dcl.sh` — que además tenía su propio bug independiente: los
     `REVOKE ALL PRIVILEGES ON mysql.* FROM ...` fallaban porque esos
     usuarios recién creados nunca tuvieron un privilegio ahí para
     empezar (no hay nada que revocar). Se sacaron esas líneas — un
     usuario al que solo se le hace `GRANT` sobre la base de la
     aplicación ya queda automáticamente sin acceso a `mysql.*`, no
     hace falta revocarlo a mano.
  Juntos, estos dos bugs explican el error de "Access denied" que
  aparecía siempre, sin importar cuántas veces se reseteara el volumen
  de MySQL: el usuario `sgdm_app` nunca llegaba a crearse.
- **Se sacó el login con Google.** No terminaba de funcionar de forma
  confiable y no valía la pena perder tiempo del proyecto arreglándolo.
  El registro y el login ahora son únicamente con correo y contraseña
  (`AuthController.php`); se eliminó `GoogleAuthController.php`, las
  rutas `/auth/google*`, los botones correspondientes en las vistas de
  login/registro, las variables `GOOGLE_*` de `.env.example` y
  `docker-compose.yml`, y la columna `google_id` de `usuarios` (con su
  `CHECK` asociado — ahora `password_hash` es simplemente `NOT NULL`,
  porque toda cuenta se crea con contraseña propia).
- **Navegación centrada de verdad.** En la navbar de escritorio, poner
  el logo, los enlaces y los botones con `justify-content: space-between`
  no centra los enlaces cuando el logo y los botones no miden lo mismo
  — el grupo del medio queda corrido hacia el lado más angosto. Se
  corrigió dándole `flex: 1` al logo y a los botones (uno alineado a la
  izquierda, el otro a la derecha), así los enlaces quedan
  matemáticamente centrados sin importar cuánto midan los costados.
- **Hero de la home centrado.** El contenido del hero (título, bajada y
  botones) estaba pensado con texto alineado a la izquierda dentro de
  una columna centrada, lo que en pantallas grandes se veía corrido
  hacia la izquierda. Se centró el texto y los botones del hero.
- **Bug de filtros en "Buscar torneos".** El chip "Todos" se marcaba
  activo con la condición `$formatoActivo === ''`, sin mirar el filtro
  de estado — por eso, al elegir "En curso" (que solo toca
  `$estadoActivo`), "Todos" quedaba marcado como activo también. Ahora
  "Todos" solo se activa cuando ningún filtro está aplicado.

## Organización multi-deporte

El sistema tiene que poder organizar torneos de cualquier disciplina —
desde ajedrez hasta fútbol o esports — y eso exige más que una simple
etiqueta de texto. Por eso cada fila de `tipos_torneo` define:

- **modalidad**: `individual` (ajedrez, videojuegos 1v1) o `equipo`
  (fútbol, vóley, pádel, truco en pareja).
- **rango de integrantes por equipo** (mínimo y máximo), solo cuando la
  modalidad es `equipo`.

El módulo de participantes usa este dato para adaptarse solo: si la
disciplina es individual, alcanza con anotar a la persona; si es de
equipo, el mismo formulario permite elegir un equipo ya cargado o crear
uno nuevo al vuelo, y la pantalla de gestión avisa si un equipo quedó
incompleto, completo o excedido según el rango de esa disciplina — sin
tener que programar esa regla de nuevo para cada deporte.

## Requisitos

- Docker y Docker Compose (Docker Desktop en Windows/Mac, o Docker Engine
  + el plugin `docker compose` en Linux).

## Puesta en marcha

```bash
# 1) Cloná/copiá el proyecto y entrá a la carpeta
cd sgdm

# 2) Creá tu archivo de variables de entorno a partir del ejemplo
cp .env.example .env
# Editá .env y cambiá las contraseñas de ejemplo.

# 3) Levantá todo
docker compose up --build

# 4) Abrí el sitio
# http://localhost:8080
```

La primera vez que se levanta el contenedor de `db`, MySQL ejecuta en
orden los scripts de `db/`:
1. `01_schema.sql` — crea las 13 tablas del modelo relacional.
2. `02_dcl.sh` — crea los usuarios `sgdm_app` (lectura/escritura
   restringida) y `sgdm_readonly` (solo lectura).
3. `03_seed.sql` — carga los catálogos (roles, formatos, disciplinas con
   su modalidad y rango de equipo). **No carga ningún torneo de
   prueba** — la base arranca limpia.

Si alguna vez necesitás resetear la base desde cero (por ejemplo, si
editás el schema), estos scripts solo corren la primera vez que el
volumen de MySQL está vacío. Para forzar que vuelvan a correr:

```bash
docker compose down -v   # borra también el volumen de datos
docker compose up --build
```

## Cómo probarlo

1. Entrá a `http://localhost:8080`, tocá "Crear cuenta" y registrate con
   correo y contraseña.
2. Andá a "Crear torneo": el asistente de 3 pasos persiste de verdad en
   la base — al terminar te da un código público y un link real al
   torneo.
3. Desde el detalle de tu torneo vas a ver el botón **"Gestionar
   participantes"**. Ahí podés anotar a cualquier persona ya registrada
   (por su ID público o su correo) y, si la disciplina es de equipo,
   elegir o crear el equipo en el mismo paso.
4. Desde "Buscar torneos" cualquiera puede encontrarlo sin estar
   logueado (esa consulta usa el usuario de base de datos de solo
   lectura, `sgdm_readonly`).

### Convertir tu cuenta en Administrador general

Ningún usuario admin viene precargado (para no dejar una cuenta de
prueba con contraseña conocida dando vueltas). Registrate normalmente y
después promové tu cuenta desde la terminal, una única vez:

```bash
docker compose exec db mysql -u root -p sgdm_db
# (te pide la MYSQL_ROOT_PASSWORD que pusiste en .env)
```
```sql
UPDATE usuarios
SET rol_id = (SELECT id FROM roles WHERE codigo = 'admin_general')
WHERE email = 'tu_correo@ejemplo.com';
```

Con eso, en tu perfil vas a ver un bloque nuevo de "Administración" con
dos botones:
- **Gestionar usuarios**: listar, buscar, crear cuentas nuevas con el rol
  que elijas, cambiar el rol o el estado (activa/suspendida) de
  cualquier cuenta, y eliminarla si todavía no tiene actividad asociada.
- **Ver registro de auditoría**: las últimas acciones registradas en el
  sistema.

## Estructura del proyecto

```
sgdm/
├── docker-compose.yml
├── .env.example
├── db/
│   ├── 01_schema.sql        DDL — modelo relacional normalizado (13 tablas)
│   ├── 02_dcl.sh             DCL — usuarios de base de datos restringidos
│   └── 03_seed.sql           catálogos (roles, formatos, disciplinas con su organización)
├── docker/
│   ├── php/Dockerfile        Apache + PHP 8.2 + extensiones (pdo_mysql, curl, mbstring)
│   └── apache/000-default.conf
└── src/
    ├── public/                DocumentRoot de Apache
    │   ├── index.php          front controller (único punto de entrada)
    │   ├── .htaccess           reescritura de URLs hacia index.php
    │   ├── css/ js/ assets/    igual que en el maquetado de la 1ª etapa
    └── app/                    fuera del DocumentRoot — nunca accesible por HTTP
        ├── bootstrap.php        autoloader propio + manejo centralizado de errores
        ├── Config/               Database.php, ReadOnlyDatabase.php
        ├── Core/                 Router, Controller, Model, Auth, Csrf, Roles, Presentacion
        ├── Models/               13 clases, una por tabla
        ├── Controllers/          Home, Torneo, Participante, Auth, Perfil, Admin, Error
        └── Views/                una carpeta por vista, PHP con HTML embebido, más partials/ compartidos
```

## Trazabilidad — qué archivo resuelve cada punto de la consigna

| Pedido de la consigna | Dónde está resuelto |
|---|---|
| Modelo relacional normalizado | `db/01_schema.sql` — 13 tablas en 3FN, con comentarios explicando cada decisión de normalización |
| DCL implementado | `db/02_dcl.sh` |
| Usuarios de base de datos con restricciones | `sgdm_app` (lectura/escritura, sin DDL) y `sgdm_readonly` (solo SELECT) en `db/02_dcl.sh`, usados desde `app/Config/Database.php` y `app/Config/ReadOnlyDatabase.php` |
| Modelos alineados al modelo relacional | `app/Models/*.php` — una clase por tabla, mismo nombre de columnas |
| Integración con PHP orientado a objetos | Todo `app/` — `Router`, `Controller`, `Model` y sus herencias concretas |
| Gestión de usuarios funcionando | `app/Controllers/AuthController.php` (registro/login/logout) + `AdminController.php` (alta/edición/baja de cuentas por el admin general) + `app/Core/Auth.php` (roles y sesión) |
| Gestión de participantes y equipos | `app/Controllers/ParticipanteController.php` + modelos `Participante.php` y `Equipo.php`, con la organización por disciplina definida en `tipos_torneo` |
| Implementación con Apache | `docker/php/Dockerfile` + `docker/apache/000-default.conf` + `src/public/.htaccess` |

## Seguridad aplicada (buenas prácticas / OWASP Top 10)

- **Inyección SQL**: toda consulta usa PDO con prepared statements
  (`app/Core/Model.php`); nunca se concatenan valores del usuario
  directamente en el SQL.
- **Contraseñas**: hasheadas con `password_hash()` (bcrypt), nunca en
  texto plano ni siquiera en los logs.
- **CSRF**: todos los formularios que modifican datos incluyen un token
  validado en el servidor (`app/Core/Csrf.php`).
- **Control de acceso**: `Auth::requireLogin()` / `Auth::requireRole()`
  se llaman al principio de cada acción que lo necesita, antes de tocar
  cualquier dato. La gestión de participantes verifica además, torneo
  por torneo, que quien la use sea su organizador o un admin general.
- **Principio de mínimo privilegio**: el usuario de base de datos que usa
  la aplicación (`sgdm_app`) no tiene permisos de `DROP`, `ALTER` ni
  `GRANT`; las consultas públicas usan un usuario aparte que solo puede
  leer (`sgdm_readonly`).
- **Manejo centralizado de errores**: `app/bootstrap.php` registra un
  manejador único para excepciones no controladas y errores fatales de
  PHP (`set_exception_handler` + `register_shutdown_function`), que
  registra el detalle técnico en el log de Apache y le muestra a quien
  esté usando el sitio una pantalla prolija (`app/Views/errors/500.php`)
  en vez de un error crudo. Con `APP_ENV=production` en el `.env`, el
  detalle técnico deja de mostrarse en pantalla.
- **Mensajes de error**: pensados para la persona que organiza o
  participa, no para quien programó el sistema (ej: "El correo o la
  contraseña no son correctos" en vez de un stack trace).
