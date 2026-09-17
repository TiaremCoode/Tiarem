<?php
/**
 * scripts/seed_demo.php
 * Carga una cuenta admin y tres torneos de ejemplo —uno por cada
 * formato— ya con todos los participantes anotados, para poder probar
 * el sistema entero sin tener que crear cuentas a mano. Se corre UNA
 * sola vez, a mano, después de levantar los contenedores (no es parte
 * de db/, que solo corre scripts de SQL/shell dentro del contenedor de
 * MySQL — esto necesita PHP para armar los torneos con las mismas
 * clases que usa la aplicación real, contraseñas incluidas).
 *
 * Uso: docker compose exec app php scripts/seed_demo.php
 * (instrucciones completas al final del README).
 */
require __DIR__ . '/../app/bootstrap.php';

const PASSWORD_DEMO = 'Demo1234';

function linea(string $texto): void { echo $texto . "\n"; }

if (Usuario::porEmail('admin@xlacopa.demo')) {
    linea('Ya existe admin@xlacopa.demo — parece que la demo ya se cargó antes. No se hizo nada, para no duplicar torneos.');
    exit(0);
}

// --- Cuenta admin, y de paso organizadora de los tres torneos de demo ---
$adminId = Usuario::registrar('Admin', 'Demo', 'admin@xlacopa.demo', PASSWORD_DEMO);
Usuario::cambiarRol($adminId, Rol::porCodigo(Roles::ADMIN_GENERAL)['id']);
$admin = Usuario::find($adminId);
linea("Cuenta admin creada: admin@xlacopa.demo / " . PASSWORD_DEMO);

function crearJugador(string $email, string $nombre, string $apellido): array
{
    $id = Usuario::registrar($nombre, $apellido, $email, PASSWORD_DEMO);
    return Usuario::find($id);
}

// ============================================================
// 1) LIGA DE FÚTBOL 5 — 3 equipos de 5, formato simple, con reglas y un aviso
// ============================================================
$tipoFutbol = TipoTorneo::findBy('nombre', 'Fútbol 5');
$liga = Torneo::crearConConfiguracion([
    'nombre'                => 'Liga de Fútbol 5 — Demo',
    'tipo_torneo_id'        => $tipoFutbol['id'],
    'modulo_competencia_id' => ModuloCompetencia::porCodigo('liga')['id'],
    'organizador_id'        => $adminId,
    'max_participantes'     => 15,
]);
Torneo::actualizarReglas((int) $liga['id'],
    "1. Se juega a dos tiempos de 20 minutos.\n" .
    "2. Máximo 5 cambios por partido.\n" .
    "3. Prohibido el juego brusco — tarjeta roja directa."
);

$equiposFutbol = ['Los Tiburones', 'Real Demo FC', 'Atlético Prueba'];
foreach ($equiposFutbol as $i => $nombreEquipo) {
    $equipoId = Equipo::create(['nombre' => $nombreEquipo, 'creado_por' => $adminId]);
    for ($j = 1; $j <= 5; $j++) {
        $n = $i * 5 + $j;
        $jugador = crearJugador("futbol{$n}@xlacopa.demo", 'Jugador', "Fútbol {$n}");
        Participante::create(['torneo_id' => $liga['id'], 'usuario_id' => $jugador['id'], 'equipo_id' => $equipoId]);
    }
}
Aviso::create(['torneo_id' => $liga['id'], 'mensaje' => 'Bienvenidos a la liga — el fixture completo se arma apenas se inicie el torneo.']);
linea("Torneo de liga creado: {$liga['codigo_publico']} ({$liga['nombre']}) — 3 equipos de 5, todavía en inscripción para que puedas iniciarlo vos.");

// ============================================================
// 2) COPA DE PÁDEL — 5 equipos de 2, eliminación directa, formato sets,
//    YA INICIADA (para mostrar una llave con byes en curso)
// ============================================================
$tipoPadel = TipoTorneo::findBy('nombre', 'Pádel');
$copa = Torneo::crearConConfiguracion([
    'nombre'                => 'Copa de Pádel — Demo',
    'tipo_torneo_id'        => $tipoPadel['id'],
    'modulo_competencia_id' => ModuloCompetencia::porCodigo('eliminacion_directa')['id'],
    'organizador_id'        => $adminId,
    'max_participantes'     => 10,
]);

$duplasPadel = ['Vamos Che', 'Los Zurdos', 'Doble Pared', 'Smash Bros', 'Bandeja Ganadora'];
foreach ($duplasPadel as $i => $nombreEquipo) {
    $equipoId = Equipo::create(['nombre' => $nombreEquipo, 'creado_por' => $adminId]);
    for ($j = 1; $j <= 2; $j++) {
        $n = $i * 2 + $j;
        $jugador = crearJugador("padel{$n}@xlacopa.demo", 'Jugador', "Pádel {$n}");
        Participante::create(['torneo_id' => $copa['id'], 'usuario_id' => $jugador['id'], 'equipo_id' => $equipoId]);
    }
}
$copa = Torneo::porCodigoPublico($copa['codigo_publico']);
Competencia::iniciar($copa, $adminId);
linea("Torneo de pádel creado e iniciado: {$copa['codigo_publico']} ({$copa['nombre']}) — 5 parejas, llave con byes ya armada (formato de resultado: sets).");

// ============================================================
// 3) SUIZO DE AJEDREZ — 6 confirmados + 1 invitación pendiente, formato decisión
// ============================================================
$tipoAjedrez = TipoTorneo::findBy('nombre', 'Ajedrez');
$suizo = Torneo::crearConConfiguracion([
    'nombre'                => 'Suizo de Ajedrez — Demo',
    'tipo_torneo_id'        => $tipoAjedrez['id'],
    'modulo_competencia_id' => ModuloCompetencia::porCodigo('suizo')['id'],
    'organizador_id'        => $adminId,
    'max_participantes'     => 10,
]);

for ($n = 1; $n <= 6; $n++) {
    $jugador = crearJugador("ajedrez{$n}@xlacopa.demo", 'Jugador', "Ajedrez {$n}");
    Participante::create(['torneo_id' => $suizo['id'], 'usuario_id' => $jugador['id']]);
}
// El 7mo jugador prefiere aceptar antes de sumarse: queda como invitación
// pendiente en sus notificaciones (RF de privacidad al anotar).
$jugador7 = crearJugador('ajedrez7@xlacopa.demo', 'Jugador', 'Ajedrez 7');
Usuario::cambiarPreferenciaAgregado((int) $jugador7['id'], false);
Notificacion::crearInvitacion((int) $suizo['id'], (int) $jugador7['id'], null, $suizo['nombre']);

linea("Torneo de ajedrez creado: {$suizo['codigo_publico']} ({$suizo['nombre']}) — 6 confirmados + 1 invitación pendiente (ajedrez7@xlacopa.demo), formato de resultado: decisión.");

linea('');
linea('Listo. Todas las cuentas de demo usan la contraseña: ' . PASSWORD_DEMO);
linea('Instrucciones completas para usarlas en el README, al final.');
