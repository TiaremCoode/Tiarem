<?php
/**
 * TorneoController.php
 * Módulo de torneos: búsqueda pública, detalle, y alta en 3 pasos.
 *
 * Diseño elegido para no complicar a organizadores ni participantes
 * (pedido explícito del cliente): cualquier usuario logueado puede crear
 * un torneo — no hace falta pedirle a un administrador que lo "ascienda"
 * a organizador antes. Al crearlo, ese usuario queda como
 * torneos.organizador_id de ESE torneo puntual, y eso es lo que le da los
 * permisos de organizador sobre él. El rol global "organizador" de la
 * tabla roles queda reservado para cuentas que un administrador general
 * quiera dejar fijas con ese perfil desde /admin.
 */
class TorneoController extends Controller
{
    /** Búsqueda pública de torneos (con filtros por texto, formato y estado). */
    public function buscar(): void
    {
        $texto   = trim((string) ($_GET['q'] ?? ''));
        $formato = trim((string) ($_GET['formato'] ?? ''));
        $estado  = trim((string) ($_GET['estado'] ?? ''));

        $torneos = Torneo::buscarPublicos($texto, $formato, $estado, 50);
        $hayFiltrosActivos = $texto !== '' || $formato !== '' || $estado !== '';

        $this->view('buscar', [
            'torneos'           => $torneos,
            'texto'             => $texto,
            'formatoActivo'     => $formato,
            'estadoActivo'      => $estado,
            'hayFiltrosActivos' => $hayFiltrosActivos,
            'formatos'          => ModuloCompetencia::todosOrdenados(),
        ]);
    }

    /** Detalle público de un torneo por su código (RF: buscar por ID único). */
    public function show(string $codigo): void
    {
        $torneo = Torneo::porCodigoPublico(strtoupper($codigo));
        if (!$torneo) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        // Todas las rondas con sus enfrentamientos (no solo la última): en
        // eliminación directa y sistema suizo el detalle muestra el
        // recorrido completo del torneo, más reciente primero.
        $rondas = array_reverse(Ronda::delTorneo((int) $torneo['id']));
        $rondasConEnfrentamientos = array_map(
            fn ($r) => ['ronda' => $r, 'enfrentamientos' => Enfrentamiento::deLaRonda((int) $r['id'])],
            $rondas
        );

        $this->view('detalle', [
            'torneo'                   => $torneo,
            'rondasConEnfrentamientos' => $rondasConEnfrentamientos,
            'totalRondas'              => $torneo['estado'] === 'inscripcion' ? 0 : Competencia::totalRondas($torneo),
            'posiciones'               => TablaPosicion::delTorneo((int) $torneo['id']),
            'historial'                => $torneo['estado'] === 'finalizado' ? TorneoHistorial::delTorneo((int) $torneo['id']) : null,
            'cantidadActiva'           => Participante::cantidadActivos((int) $torneo['id']),
            'puedeGestionar'           => Auth::esOrganizadorOAdmin($torneo),
            'mensaje'                  => $_SESSION['torneo_mensaje'] ?? null,
            'error'                    => $_SESSION['torneo_error'] ?? null,
        ]);
        unset($_SESSION['torneo_mensaje'], $_SESSION['torneo_error']);
    }

    /** Formulario (asistente de 3 pasos). Requiere sesión iniciada. */
    public function createForm(): void
    {
        Auth::requireLogin();

        $this->view('crear', [
            'formatos'  => ModuloCompetencia::todosOrdenados(),
            'tipos'     => TipoTorneo::all('nombre ASC'),
        ]);
    }

    /**
     * Alta de torneo. Se llama por fetch() desde crear-torneo.js (JSON),
     * para que el asistente de 3 pasos no recargue la página y el alta
     * siga siendo rápida.
     */
    public function store(): void
    {
        // No se usa Auth::requireLogin() acá porque ese método redirige a
        // /login con un header Location — correcto para una página normal,
        // pero este endpoint lo consume fetch() en JSON, así que si la
        // sesión venció hay que devolver un JSON claro en vez de un
        // redirect que el navegador no va a seguir dentro del fetch.
        $usuario = Auth::user();
        if (!$usuario) {
            $this->json(['ok' => false, 'error' => 'Tu sesión venció. Volvé a iniciar sesión para crear el torneo.'], 401);
        }

        $data = $this->input();

        if (!Csrf::validate($data['csrf_token'] ?? null)) {
            $this->json(['ok' => false, 'error' => 'Tu sesión de formulario venció. Recargá la página e intentá de nuevo.'], 419);
        }

        $moduloId = (int) ($data['modulo_competencia_id'] ?? 0);
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $maxParticipantes = (int) ($data['max_participantes'] ?? 0);
        $tipoId = (int) ($data['tipo_torneo_id'] ?? 0);

        $errores = [];
        if (!ModuloCompetencia::find($moduloId)) {
            $errores['modulo_competencia_id'] = 'Elegí uno de los formatos disponibles.';
        }
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            $errores['nombre'] = 'Escribí un nombre de hasta 120 caracteres.';
        }
        if ($maxParticipantes < 2 || $maxParticipantes > 512) {
            $errores['max_participantes'] = 'El número de participantes debe estar entre 2 y 512.';
        }
        if (!$tipoId || !TipoTorneo::find($tipoId)) {
            $tipoGeneral = TipoTorneo::findBy('nombre', 'General');
            $tipoId = $tipoGeneral ? (int) $tipoGeneral['id'] : 1;
        }

        if ($errores) {
            $this->json(['ok' => false, 'errores' => $errores], 422);
        }

        $torneo = Torneo::crearConConfiguracion([
            'nombre'                => $nombre,
            'tipo_torneo_id'        => $tipoId,
            'modulo_competencia_id' => $moduloId,
            'organizador_id'        => $usuario['id'],
            'max_participantes'     => $maxParticipantes,
        ]);

        Auditoria::registrar((int) $usuario['id'], 'crear_torneo', 'torneos', (int) $torneo['id'], $nombre);

        $this->json([
            'ok'     => true,
            'codigo' => $torneo['codigo_publico'],
            'url'    => '/torneos/' . $torneo['codigo_publico'],
        ], 201);
    }
}
