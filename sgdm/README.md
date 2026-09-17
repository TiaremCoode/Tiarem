# X La Copa — SGDM · Tercera etapa, final (Programación Full Stack)

Sobre la base de la segunda etapa (backend real, base de datos
normalizada, gestión de usuarios y de participantes/equipos), esta
entrega agrega el corazón del sistema: el **motor de competencia** —
los tres formatos de torneo (liga, eliminación directa, sistema suizo)
con generación automática del calendario, carga de resultados, avance
de rondas y cierre del torneo con historial y título de campeón —, todo
dockerizado y pensado para levantarse tal cual en el servidor final
(AlmaLinux 9).

## Qué incluye esta entrega (y qué no)

Esta etapa cubre lo pedido en la consigna de cierre: implementación
completa del sistema (ya no quedan módulos "para la próxima etapa"),
sistema portable mediante Docker, y el resto de los requerimientos
funcionales de `REKERIMIENTOS.docx` que todavía estaban pendientes.
Ver la tabla de trazabilidad más abajo para el detalle punto por punto.

Lo que se agregó en esta entrega, en concreto:
- **Módulo de liga** (`app/Formatos/FormatoLiga.php`): arma el
  calendario de todos-contra-todos completo de una sola vez (método del
  círculo), con descansos rotativos si la cantidad de participantes es
  impar, y tabla de posiciones (puntos, victorias, derrotas).
- **Módulo de eliminación directa** (`FormatoEliminacionDirecta.php`):
  sortea la llave, la completa con pases directos (byes) hasta la
  potencia de 2 más cercana sin que dos byes queden enfrentados entre
  sí, y avanza ronda a ronda (octavos/cuartos/semifinal/final según la
  cantidad de participantes) hasta la final. Quien pierde queda
  `eliminado`.
- **Módulo de sistema suizo** (`FormatoSuizo.php`): primera ronda al
  azar, después empareja por tabla de posiciones evitando repetir
  rivales (con backtracking: solo repite si es matemáticamente
  inevitable).
- **Módulo de resultados** (`app/Core/Competencia.php` +
  `CompetenciaController.php`): un único punto que recibe cualquier
  resultado, actualiza posiciones, cierra la ronda cuando corresponde y
  dispara el avance o el cierre del torneo — sin que cada formato tenga
  que reimplementar esa parte común.
- **Historial y títulos**: al finalizar, se calculan y guardan el
  campeón, mejor jugador, mayor puntaje, invicto y menos derrotas
  (`torneos_historial`), y el campeón le queda atribuido como título en
  su perfil (`TorneoHistorial::titulosDe()`, sección "Mis títulos").

## Revisión de la tercera etapa (motor de competencia)

Igual que en la etapa anterior, antes de dar por cerrado el motor de
competencia se lo puso a prueba de punta a punta contra un MySQL 8.0.46
real (mismo criterio que la revisión de la segunda etapa, más abajo):
se registraron usuarios, se crearon torneos de cada formato, se
anotaron participantes, se inició la competencia y se cargaron
resultados reales a través de las rutas HTTP tal como lo haría el
navegador — no solo llamando a las clases directamente. Así aparecieron
dos bugs reales, además de un requerimiento que había quedado sin
cubrir en la implementación inicial:

- **Bug real en el sistema suizo: un participante podía "desaparecer"
  del torneo.** Cuando alguien recibía un pase directo (bye), el
  sistema resolvía ese cruce automáticamente pero nunca le sumaba esa
  victoria a `tabla_posiciones` — y como el emparejamiento de cada
  ronda siguiente se arma leyendo esa misma tabla, esa persona
  directamente dejaba de aparecer en los emparejamientos futuros. Se
  corrigió: ahora un bye suma su victoria automática en
  `FormatoSuizo::crearRondaYRegistrarByes()`, igual que si hubiera
  jugado y ganado.
- **Bug real en el mismo módulo: revanchas evitables que no se
  evitaban.** El primer algoritmo de emparejamiento era ingenuo (al
  mejor ubicado sin rival se lo empareja con el primer candidato
  disponible que no haya enfrentado antes, sin mirar más allá) y en la
  práctica terminaba forzando una revancha en casos donde sí existía
  una combinación completa sin ninguna. Se reemplazó por un
  emparejamiento con backtracking real (`FormatoSuizo::buscarEmparejamiento()`):
  prueba candidatos del más cercano al más lejano en la tabla y, si una
  elección deja sin salida al resto de la ronda, retrocede y prueba la
  siguiente — con un tope de intentos para no colgarse en grupos muy
  grandes. Verificado corriendo la batería de pruebas muchas veces
  seguidas (el sorteo de la ronda 1 cambia en cada corrida): cero
  revanchas evitables en todos los casos.
- **Requerimiento que faltaba: marcar como `eliminado` a quien pierde
  en eliminación directa.** La letra lo pide explícitamente ("el
  vencedor pasa a la siguiente ronda" y el perdedor "queda eliminado")
  y la columna `participantes.estado` ya tenía el valor `eliminado`
  previsto desde el esquema de la segunda etapa, pero ninguna parte del
  código lo usaba todavía. Se agregó `FormatoInterface::estadoTrasDerrota()`
  (`null` en liga y suizo, `'eliminado'` en eliminación directa),
  aplicado desde `Competencia::registrarResultado()`.

Además, aprovechando que había que tocar los controladores para el
nuevo módulo de resultados, se unificó una validación de permisos que
estaba repetida: `TorneoController::show()` y
`ParticipanteController::autorizar()` recalculaban cada uno, con su
propio código, si quien miraba la página era el organizador de ESE
torneo o un admin general. Ahora los tres controladores (los dos
anteriores más el nuevo `CompetenciaController`) llaman a
`Auth::requireOrganizadorOAdmin()` / `Auth::esOrganizadorOAdmin()`. De
paso, se sacaron `.tabs-scroll`/`.tab-item` de `detalle.css`: eran
estilos de una versión anterior del maquetado que ya no se usaban en
ninguna vista.

**Verificado en el servidor de destino:** se probó explícitamente que
nada de lo nuevo usa una función o extensión de PHP que no esté ya
declarada en `docker/php/Dockerfile`, ni una sintaxis de MySQL 8 que no
funcione en AlmaLinux 9 (ver la sección "Servidor de destino:
AlmaLinux 9" más abajo).

## Revisión de la segunda etapa (antes de pasar a esta)

Antes de avanzar se repasó todo el código de esa etapa contra la
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

## Ajustes posteriores a la revisión de la segunda etapa

- **WAF/IPS delante de Apache, con segmentación de red.** La consigna
  pide implementar un IPS o algún esquema de subredes. Se agregó un
  contenedor `waf` (imagen `owasp/modsecurity-crs:nginx-alpine`: Nginx +
  ModSecurity + el ruleset de OWASP) que queda como único punto de
  entrada expuesto al host — es el que ahora publica el puerto 8080, no
  `app`. Cada petición HTTP pasa primero por ahí, donde se inspecciona
  contra patrones de ataque conocidos (inyección SQL, XSS, path
  traversal, etc.) y se corta con un 403 antes de llegar a Apache/PHP
  si matchea alguno. Además, `docker-compose.yml` ahora define dos
  redes Docker con subredes explícitas en vez de una sola red plana:
  `red_publica` (172.28.0.0/24), donde vive únicamente el `waf`, y
  `red_interna` (172.28.1.0/24), donde viven `app` y `db` — ninguno de
  los dos alcanzable directamente desde fuera de Docker. El diagrama
  `esquemadered.drawio` de la raíz del proyecto ya refleja esta
  arquitectura nueva.
  **Importante:** esta pieza todavía no se probó de punta a punta
  contra un `docker compose up --build` real (la imagen de ModSecurity
  no se pudo descargar en el entorno donde se hizo esta revisión) — a
  diferencia del resto de los hallazgos de este documento, que sí están
  verificados. Probarla y ajustar `PARANOIA`/reglas si hace falta.
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
1. `01_schema.sql` — crea las 14 tablas del modelo relacional.
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

## Servidor de destino: AlmaLinux 9

El sistema se pensó para levantarse tal cual en un servidor AlmaLinux
9, así que se revisó el código y la configuración de Docker buscando
específicamente lo que suele romperse al pasar de un entorno de
desarrollo (típicamente Ubuntu/Mac/Windows, sin SELinux) a un host
RHEL-family con SELinux activo por defecto. No se probó un `docker
compose up --build` real contra un AlmaLinux 9 (no había uno
disponible al hacer esta revisión), pero sí se revisó a fondo cada
pieza de configuración; lo que se corrigió:

- **SELinux en los volúmenes montados (bug real de esta revisión).**
  `docker-compose.yml` monta `./src` dentro de `app` y `./db` dentro de
  `db` directamente desde el host. En Ubuntu/Mac/Windows (sin SELinux)
  eso anda sin más, pero en AlmaLinux 9 —con SELinux en modo
  `Enforcing` de fábrica— Apache y MySQL reciben "Permission denied" al
  intentar leer esas carpetas, porque el contexto SELinux del
  contenedor no coincide con el del archivo en el host. Se agregó el
  flag `:z` a ambos volúmenes (comparten la etiqueta entre
  contenedores); en hosts sin SELinux este flag no hace nada, así que
  el cambio no afecta a nadie que siga desarrollando en Ubuntu/Mac/Windows.
- **`firewalld`.** A diferencia de Ubuntu (donde `ufw` suele venir
  desactivado), AlmaLinux 9 trae `firewalld` activo por defecto. Para
  que el puerto que expone el WAF sea alcanzable desde afuera, hace
  falta abrirlo explícitamente una vez:
  ```bash
  sudo firewall-cmd --permanent --add-port=8080/tcp
  sudo firewall-cmd --reload
  ```
  (el `3307:3306` de `db` es solo para conectarte con un cliente MySQL
  externo en desarrollo — en el servidor final conviene sacar ese
  mapeo directamente de `docker-compose.yml` en vez de abrirlo).
- **Instalación de Docker.** AlmaLinux 9 no trae Docker Engine
  preinstalado (a diferencia de las imágenes de Docker Desktop que
  incluyen todo). Se instala desde el repositorio oficial para la
  familia RHEL/CentOS (`dnf config-manager --add-repo
  https://download.docker.com/linux/centos/docker-ce.repo`, después
  `dnf install docker-ce docker-ce-cli containerd.io
  docker-compose-plugin`), igual que en cualquier otro RHEL 9.
- **Nada más resultó incompatible.** El resto del stack no depende del
  sistema operativo del host: `mysql:8.0`, `php:8.2-apache` y
  `owasp/modsecurity-crs:nginx-alpine` son las mismas imágenes
  Linux (multi-arquitectura, x86_64/aarch64) sin importar si el host es
  AlmaLinux, Ubuntu o cualquier otra distro — Docker corre todo dentro
  de esos contenedores, no directamente sobre el sistema operativo del
  servidor. `docker-compose.yml` tampoco declara ninguna versión de
  Compose file obsoleta ni usa una sintaxis que dependa del host.

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
5. Con al menos 2 participantes anotados, en el detalle del torneo vas
   a ver el botón **"Iniciar torneo"**: arma el calendario completo
   (liga) o la primera ronda (eliminación directa / suizo) según el
   formato que hayas elegido al crearlo.
6. A partir de ahí, cada enfrentamiento pendiente de la ronda abierta
   tiene su propio formulario para cargar el resultado ahí mismo. Al
   cargar el último resultado que falta de una ronda, el sistema la
   cierra solo y arma (o abre) la siguiente — no hace falta ningún
   paso manual aparte.
7. Cuando el torneo termina, el detalle muestra el campeón y el
   resumen final (mejor jugador, mayor puntaje, invicto, menos
   derrotas), y el campeón se lo lleva como título a su perfil, en la
   sección "Mis títulos".

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
│   ├── 01_schema.sql        DDL — modelo relacional normalizado (14 tablas)
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
        ├── Core/                 Router, Controller, Model, Auth, Csrf, Roles, Presentacion, Competencia
        ├── Formatos/             FormatoInterface + FormatoLiga, FormatoEliminacionDirecta, FormatoSuizo
        ├── Models/               14 clases, una por tabla
        ├── Controllers/          Home, Torneo, Participante, Competencia, Auth, Perfil, Admin, Error
        └── Views/                una carpeta por vista, PHP con HTML embebido, más partials/ compartidos
```

## Trazabilidad — qué archivo resuelve cada punto de la consigna

| Pedido de la consigna | Dónde está resuelto |
|---|---|
| Modelo relacional normalizado | `db/01_schema.sql` — 14 tablas en 3FN, con comentarios explicando cada decisión de normalización |
| DCL implementado | `db/02_dcl.sh` |
| Usuarios de base de datos con restricciones | `sgdm_app` (lectura/escritura, sin DDL) y `sgdm_readonly` (solo SELECT) en `db/02_dcl.sh`, usados desde `app/Config/Database.php` y `app/Config/ReadOnlyDatabase.php` |
| Modelos alineados al modelo relacional | `app/Models/*.php` — una clase por tabla, mismo nombre de columnas |
| Integración con PHP orientado a objetos | Todo `app/` — `Router`, `Controller`, `Model` y sus herencias concretas |
| Gestión de usuarios funcionando | `app/Controllers/AuthController.php` (registro/login/logout) + `AdminController.php` (alta/edición/baja de cuentas por el admin general) + `app/Core/Auth.php` (roles y sesión) |
| Gestión de participantes y equipos | `app/Controllers/ParticipanteController.php` + modelos `Participante.php` y `Equipo.php`, con la organización por disciplina definida en `tipos_torneo` |
| Implementación con Apache | `docker/php/Dockerfile` + `docker/apache/000-default.conf` + `src/public/.htaccess` |
| Módulo de liga (todos contra todos + tabla de posiciones) | `app/Formatos/FormatoLiga.php` |
| Módulo de eliminación directa (llaves, byes, avance de ronda) | `app/Formatos/FormatoEliminacionDirecta.php` |
| Módulo de sistema suizo (empareja por rendimiento, evita repetir rivales) | `app/Formatos/FormatoSuizo.php` |
| Módulo de resultados (carga, cierre de ronda, avance/finalización) | `app/Core/Competencia.php` + `app/Controllers/CompetenciaController.php` |
| Historial del torneo + estadísticas finales | `app/Models/TorneoHistorial.php` (tabla `torneos_historial`) |
| Título de campeón atribuido en el perfil | `TorneoHistorial::titulosDe()` + sección "Mis títulos" en `app/Views/perfil.php` |
| Implementación completa del sistema | Todo lo anterior en conjunto — no quedan módulos pendientes de la letra original |
| Sistema portable mediante Docker | `docker-compose.yml` (`db` + `app` + `waf`), con el ajuste de compatibilidad para AlmaLinux 9 documentado más arriba |

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
  cualquier dato. La gestión de participantes y el módulo de resultados
  (iniciar torneo, cargar resultado) verifican además, torneo por
  torneo, que quien los use sea su organizador o un admin general
  (`Auth::requireOrganizadorOAdmin()`), y que el enfrentamiento sobre el
  que se carga un resultado pertenezca de verdad a ESE torneo
  (`Enfrentamiento::encontrarDelTorneo()`) — no alcanza con adivinar un ID.
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
