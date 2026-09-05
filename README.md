# X La Copa — SGDM · Segunda etapa (Programación Full Stack)

Backend real sobre el maquetado de la primera etapa: base de datos MySQL
normalizada, DCL con usuarios restringidos, modelos PHP orientados a
objetos alineados al modelo relacional, gestión de usuarios (registro,
login tradicional, login con Google y administración de cuentas desde
el rol admin general), un módulo real de participantes y equipos
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
# El login con Google podés dejarlo vacío por ahora (ver más abajo).

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
   correo y contraseña (o con Google, si ya configuraste las
   credenciales — ver abajo).
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

### Torneo de prueba (opcional)

Para no tener que crear 37 cuentas a mano solo para ver cómo se ve un
torneo lleno, `db/demo/` tiene un torneo de fútbol 5 completo (6 equipos,
36 jugadores con nombres de futbolistas reales) listo para cargar. **No
se ejecuta solo** — Docker únicamente corre lo que está directamente
dentro de `db/`, nunca en subcarpetas — así que es 100% opcional y no
afecta al sistema real de ninguna forma.

Cargarlo:
```bash
docker compose exec -T db mysql -u root -p sgdm_db < db/demo/CARGAR_torneo_demo.sql
```

Todas las cuentas de prueba terminan en `@demo.test` y comparten
la contraseña `Golazo2026!` (por ejemplo, `jugador01@demo.test` es
Cristiano Ronaldo). El torneo queda con el código público `DEMOFUT1`.

Borrarlo cuando quieras, sin dejar rastro:
```bash
docker compose exec -T db mysql -u root -p sgdm_db < db/demo/BORRAR_torneo_demo.sql
```

## Login con Google — cómo configurarlo

1. Entrá a [Google Cloud Console → Credenciales](https://console.cloud.google.com/apis/credentials).
2. Creá un proyecto (o usá uno existente) y creá un **ID de cliente de
   OAuth 2.0** de tipo **Aplicación web**.
3. En **URI de redireccionamiento autorizados**, agregá exactamente:
   ```
   http://localhost:8080/auth/google/callback
   ```
4. Copiá el **ID de cliente** y el **Secreto del cliente** a tu `.env`:
   ```
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   ```
5. Reiniciá el contenedor de la app: `docker compose restart app`.

Si estas variables están vacías, el botón "Continuar con Google" va a
mostrar un mensaje claro en vez de romperse.

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
│   ├── php/Dockerfile        Apache + PHP 8.2 + extensiones (pdo_mysql, curl)
│   └── apache/000-default.conf
└── src/
    ├── public/                DocumentRoot de Apache
    │   ├── index.php          front controller (único punto de entrada)
    │   ├── .htaccess           reescritura de URLs hacia index.php
    │   ├── css/ js/ assets/    igual que en el maquetado de la 1ª etapa
    └── app/                    fuera del DocumentRoot — nunca accesible por HTTP
        ├── bootstrap.php        autoloader propio + manejo centralizado de errores
        ├── Config/               Database.php, ReadOnlyDatabase.php
        ├── Core/                 Router, Controller, Model, Auth, Csrf, Roles
        ├── Models/               13 clases, una por tabla
        ├── Controllers/          Home, Torneo, Participante, Auth, GoogleAuth, Perfil, Admin, Error
        └── Views/                una carpeta por vista, PHP con HTML embebido
```

## Trazabilidad — qué archivo resuelve cada punto de la consigna

| Pedido de la consigna | Dónde está resuelto |
|---|---|
| Modelo relacional normalizado | `db/01_schema.sql` — 13 tablas en 3FN, con comentarios explicando cada decisión de normalización |
| DCL implementado | `db/02_dcl.sh` |
| Usuarios de base de datos con restricciones | `sgdm_app` (lectura/escritura, sin DDL) y `sgdm_readonly` (solo SELECT) en `db/02_dcl.sh`, usados desde `app/Config/Database.php` y `app/Config/ReadOnlyDatabase.php` |
| Modelos alineados al modelo relacional | `app/Models/*.php` — una clase por tabla, mismo nombre de columnas |
| Integración con PHP orientado a objetos | Todo `app/` — `Router`, `Controller`, `Model` y sus herencias concretas |
| Gestión de usuarios funcionando | `app/Controllers/AuthController.php` (registro/login/logout) + `GoogleAuthController.php` (login con Google) + `AdminController.php` (alta/edición/baja de cuentas por el admin general) + `app/Core/Auth.php` (roles y sesión) |
| Gestión de participantes y equipos | `app/Controllers/ParticipanteController.php` + modelos `Participante.php` y `Equipo.php`, con la organización por disciplina definida en `tipos_torneo` |
| Implementación con Apache | `docker/php/Dockerfile` + `docker/apache/000-default.conf` + `src/public/.htaccess` |

## Seguridad aplicada (buenas prácticas / OWASP Top 10)

- **Inyección SQL**: toda consulta usa PDO con prepared statements
  (`app/Core/Model.php`); nunca se concatenan valores del usuario
  directamente en el SQL.
- **Contraseñas**: hasheadas con `password_hash()` (bcrypt), nunca en
  texto plano ni siquiera en los logs.
- **CSRF**: todos los formularios que modifican datos incluyen un token
  validado en el servidor (`app/Core/Csrf.php`). La cookie de sesión
  además va con `SameSite=Lax`, una segunda barrera para el mismo
  problema.
- **Límite de intentos de login**: `AuthController::login()` frena a
  las 5 fallas seguidas por correo en 15 minutos (`IntentoLogin.php`),
  antes de siquiera verificar la contraseña — cierra fuerza bruta sobre
  cuentas puntuales.
- **Cookie de sesión endurecida**: `HttpOnly` (JavaScript no puede
  leerla) y `Secure` cuando la conexión ya es HTTPS (`app/Core/Auth.php`).
- **Cabeceras HTTP de seguridad**: `X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy` y un `Content-Security-Policy`
  ajustado a lo que el sitio realmente carga, en
  `docker/apache/000-default.conf`. Por eso `partials/user-context.php`
  pasa el estado de sesión por un `<meta data-user>` en vez de un
  `<script>` inline: así el CSP puede exigir `script-src 'self'` sin el
  agujero de `'unsafe-inline'`.
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

Si ya tenés el proyecto corriendo desde antes (con tu cuenta y el
torneo de prueba cargados), el límite de intentos de login necesita una
tabla nueva que tu base todavía no tiene. No hace falta borrar nada — se
agrega sola con:
```bash
Get-Content db/migraciones/001_intentos_login.sql | docker compose exec -T db mysql -u root -p sgdm_db
```

## Accesibilidad

- **Contraste**: la paleta se revisó contra WCAG AA (4.5:1 en texto
  normal). `--color-plata-tenue` se ajustó de `#8D97A1` a `#929CA6` — el
  valor original daba 4.27:1 sobre las tarjetas, por debajo del mínimo.
- **Lectores de pantalla**: los íconos puramente decorativos (flechas,
  lupa, el ícono de "volver") llevan `aria-hidden="true"` para no
  generar ruido; los mensajes de error del servidor llevan
  `role="alert"` para que se anuncien solos apenas aparecen, sin que la
  persona tenga que ir a buscarlos.
- **Menos movimiento**: el sitio ya respetaba
  `prefers-reduced-motion: reduce` (desactiva transiciones y el scroll
  suave) para quien lo tiene configurado así a nivel sistema operativo.

## Calidad de código

- **PHPStan** (análisis estático, nivel 5) sobre `app/Core`, `app/Config`,
  `app/Models` y `app/Controllers` — no analiza `app/Views` a propósito,
  porque esas plantillas reciben sus variables por `extract()` y
  PHPStan no tiene forma de saberlo. Es una herramienta de desarrollo,
  no una dependencia del proyecto — el backend sigue corriendo sin
  Composer. Para usarla hace falta tener Composer instalado en tu PC
  (no en el contenedor):
  ```bash
  composer install
  vendor/bin/phpstan analyse
  ```
  De hecho, el bug de esta semana en `GoogleAuthController` (un método
  con una firma incompatible con la clase padre) es exactamente el tipo
  de error que PHPStan detecta al instante, sin necesitar Docker
  corriendo.
- **`scripts/smoke-test.sh`**: prueba en segundos que las rutas
  principales respondan lo que deberían (200, 302, 404 según
  corresponda) después de levantar el proyecto. Se corre con
  `bash scripts/smoke-test.sh` una vez que `docker compose up` ya está
  arriba.
- **`.github/workflows/smoke-test.yml`**: el mismo smoke test, pero
  corrido automáticamente por GitHub Actions en cada push — levanta el
  proyecto entero con Docker Compose, como en cualquier PC, y avisa si
  algo se rompió.
- **`.sqlfluff`**: configuración para lintear los `.sql` de `db/` con
  [sqlfluff](https://sqlfluff.com/), si querés revisar el estilo del SQL
  antes de agregar algo nuevo.

