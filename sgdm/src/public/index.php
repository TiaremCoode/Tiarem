<?php
/**
 * public/index.php
 * Front controller único: todas las peticiones HTTP pasan por acá
 * (ver public/.htaccess), que arma el Router y le delega el resto.
 * Es el único archivo PHP que Apache sirve directamente.
 */

require __DIR__ . '/../app/bootstrap.php';

$router = new Router();

// -------- Públicas --------
$router->get('/', [HomeController::class, 'index']);
$router->get('/torneos', [TorneoController::class, 'buscar']);

// -------- Autenticación --------
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/registro', [AuthController::class, 'showRegister']);
$router->post('/registro', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// -------- Requieren sesión iniciada --------
// IMPORTANTE: '/torneos/crear' se registra antes que '/torneos/{codigo}'.
// El Router prueba las rutas en el orden en que se registran y devuelve
// la primera que matchea, así que si el comodín fuera primero, una
// petición a /torneos/crear la interpretaría como un torneo con código
// "crear". Cualquier ruta fija dentro de /torneos/... debe ir antes que
// la ruta con parámetro.
$router->get('/torneos/crear', [TorneoController::class, 'createForm']);
$router->post('/api/torneos', [TorneoController::class, 'store']);
$router->get('/perfil', [PerfilController::class, 'show']);
$router->post('/perfil', [PerfilController::class, 'update']);
$router->post('/perfil/privacidad', [PerfilController::class, 'actualizarPrivacidad']);

// -------- Módulo de notificaciones (cada quien ve y responde solo las suyas) --------
$router->get('/notificaciones', [NotificacionController::class, 'index']);
$router->post('/notificaciones/{id}/aceptar', [NotificacionController::class, 'aceptar']);
$router->post('/notificaciones/{id}/rechazar', [NotificacionController::class, 'rechazar']);

// -------- Módulo de participantes y equipos (solo organizador/admin gestionan) --------
$router->get('/torneos/{codigo}/participantes', [ParticipanteController::class, 'index']);
$router->post('/torneos/{codigo}/participantes', [ParticipanteController::class, 'store']);
$router->post('/torneos/{codigo}/participantes/{id}/eliminar', [ParticipanteController::class, 'destroy']);

// -------- Módulo de resultados / competencia (solo organizador/admin gestionan) --------
$router->post('/torneos/{codigo}/iniciar', [CompetenciaController::class, 'iniciar']);
$router->post('/torneos/{codigo}/enfrentamientos/{id}/resultado', [CompetenciaController::class, 'resultado']);

// -------- Reglas y avisos del torneo (leer: cualquiera; publicar: organizador/admin) --------
$router->post('/torneos/{codigo}/reglas', [TorneoController::class, 'actualizarReglas']);
$router->post('/torneos/{codigo}/aceptar-reglas', [TorneoController::class, 'aceptarReglas']);
$router->post('/torneos/{codigo}/avisos', [AvisoController::class, 'store']);

// -------- Pública, con parámetro (va después de las rutas fijas de /torneos/...) --------
$router->get('/torneos/{codigo}', [TorneoController::class, 'show']);

// -------- Solo administrador general --------
$router->get('/admin/auditoria', [AdminController::class, 'auditoria']);
$router->get('/admin/usuarios', [AdminController::class, 'usuarios']);
$router->get('/admin/usuarios/crear', [AdminController::class, 'crearUsuarioForm']);
$router->post('/admin/usuarios/crear', [AdminController::class, 'crearUsuario']);
$router->get('/admin/usuarios/{id}/editar', [AdminController::class, 'editarUsuarioForm']);
$router->post('/admin/usuarios/{id}/editar', [AdminController::class, 'actualizarUsuario']);
$router->post('/admin/usuarios/{id}/eliminar', [AdminController::class, 'eliminarUsuario']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
