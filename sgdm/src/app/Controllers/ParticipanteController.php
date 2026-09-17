<?php
/**
 * ParticipanteController.php
 * Módulo de gestión de participantes y equipos (módulo funcional mínimo
 * #2 de la letra del proyecto, que en la entrega anterior había quedado
 * sin implementar).
 *
 * Diseño pensado para no complicar al organizador (pedido explícito del
 * cliente): anotar a alguien es UNA sola acción — buscarlo por su ID
 * público o su email y confirmar — incluso cuando la disciplina es de
 * equipo, donde alcanza con elegir un equipo ya cargado o escribir el
 * nombre de uno nuevo en el mismo formulario, sin un paso aparte para
 * "crear el equipo" y otro para "agregarle gente".
 *
 * Solo el organizador de ESE torneo puntual (torneos.organizador_id) o
 * un administrador general pueden gestionar participantes; cualquier
 * otra persona ve la lista en modo solo lectura desde el detalle
 * público del torneo.
 */
class ParticipanteController extends Controller
{
    /** Resuelve el torneo por código y valida permisos de gestión. Termina la petición si no corresponde. */
    private function autorizar(string $codigo): array
    {
        $torneo = Torneo::porCodigoPublico(strtoupper($codigo));
        if (!$torneo) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            exit;
        }

        Auth::requireOrganizadorOAdmin($torneo);
        return $torneo;
    }

    public function index(string $codigo): void
    {
        $torneo = $this->autorizar($codigo);
        $tipoTorneo = TipoTorneo::find((int) $torneo['tipo_torneo_id']);
        $participantes = Participante::delTorneo((int) $torneo['id']);
        $equipos = TipoTorneo::esDeEquipo($tipoTorneo) ? Equipo::delTorneo((int) $torneo['id']) : [];

        // Para disciplinas de equipo, se arma un resumen por equipo con
        // su cantidad de integrantes y si está incompleto/completo/excedido,
        // usando el rango definido en la propia disciplina.
        $resumenEquipos = [];
        foreach ($equipos as $equipo) {
            $cantidad = Equipo::cantidadIntegrantes((int) $equipo['id'], (int) $torneo['id']);
            $resumenEquipos[] = [
                'equipo'      => $equipo,
                'integrantes' => Equipo::integrantes((int) $equipo['id'], (int) $torneo['id']),
                'cantidad'    => $cantidad,
                'estado'      => TipoTorneo::estadoDelEquipo($tipoTorneo, $cantidad),
            ];
        }

        $this->view('participantes', [
            'torneo'         => $torneo,
            'tipoTorneo'     => $tipoTorneo,
            'participantes'  => $participantes,
            'resumenEquipos' => $resumenEquipos,
            'cantidadActiva' => Participante::cantidadActivos((int) $torneo['id']),
            'mensaje'        => $_SESSION['participantes_mensaje'] ?? null,
            'error'          => $_SESSION['participantes_error'] ?? null,
        ]);
        unset($_SESSION['participantes_mensaje'], $_SESSION['participantes_error']);
    }

    /**
     * Inscribe a un participante. Si la disciplina es de equipo, en el
     * mismo envío se puede elegir un equipo existente o escribir el
     * nombre de uno nuevo (se crea al vuelo) — una sola acción, tal como
     * pide el cliente que sea de simple la gestión del torneo.
     */
    public function store(string $codigo): void
    {
        $torneo = $this->autorizar($codigo);
        $tipoTorneo = TipoTorneo::find((int) $torneo['tipo_torneo_id']);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->volverConError($codigo, 'Tu sesión de formulario venció. Volvé a intentarlo.');
        }

        if ($torneo['estado'] !== 'inscripcion') {
            $this->volverConError($codigo, 'Este torneo ya no está en etapa de inscripción.');
        }

        if (Participante::cantidadActivos((int) $torneo['id']) >= (int) $torneo['max_participantes']) {
            $this->volverConError($codigo, 'Ya se llegó al máximo de participantes para este torneo.');
        }

        $busqueda = trim((string) ($_POST['usuario_busqueda'] ?? ''));
        $usuario = Usuario::porIdPublicoOEmail($busqueda);
        if (!$usuario) {
            $this->volverConError($codigo, 'No encontramos a nadie registrado con ese ID o correo. Pedile que se registre primero.');
        }

        if (Participante::yaInscripto((int) $torneo['id'], (int) $usuario['id'])) {
            $this->volverConError($codigo, 'Esa persona ya está anotada en este torneo.');
        }

        $equipoId = null;
        if (TipoTorneo::esDeEquipo($tipoTorneo)) {
            $equipoExistenteId = (int) ($_POST['equipo_id'] ?? 0);
            $equipoNuevoNombre = trim((string) ($_POST['equipo_nuevo_nombre'] ?? ''));

            if ($equipoExistenteId > 0) {
                $equipoId = $equipoExistenteId;
            } elseif ($equipoNuevoNombre !== '') {
                $equipoId = Equipo::create([
                    'nombre'     => $equipoNuevoNombre,
                    'creado_por' => $usuario['id'],
                ]);
            } else {
                $this->volverConError($codigo, 'Esta disciplina se juega por equipos: elegí uno existente o escribí el nombre de uno nuevo.');
            }
        }

        $participanteId = Participante::create([
            'torneo_id'  => $torneo['id'],
            'usuario_id' => $usuario['id'],
            'equipo_id'  => $equipoId,
        ]);

        Auditoria::registrar((int) Auth::user()['id'], 'inscribir_participante', 'participantes', $participanteId, Usuario::nombreCompleto($usuario));

        $_SESSION['participantes_mensaje'] = Usuario::nombreCompleto($usuario) . ' quedó anotado.';
        $this->redirect("/torneos/{$codigo}/participantes");
    }

    /** Da de baja a un participante (borrado real si el torneo todavía no arrancó, o marca "retirado" si ya está en curso). */
    public function destroy(string $codigo, string $participanteId): void
    {
        $torneo = $this->autorizar($codigo);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->volverConError($codigo, 'Tu sesión de formulario venció. Volvé a intentarlo.');
        }

        $participante = Participante::find((int) $participanteId);
        if (!$participante || (int) $participante['torneo_id'] !== (int) $torneo['id']) {
            $this->volverConError($codigo, 'No encontramos a ese participante en este torneo.');
        }

        Participante::darDeBaja((int) $participanteId, $torneo['estado']);
        Auditoria::registrar((int) Auth::user()['id'], 'dar_de_baja_participante', 'participantes', (int) $participanteId);

        $_SESSION['participantes_mensaje'] = 'Se dio de baja al participante.';
        $this->redirect("/torneos/{$codigo}/participantes");
    }

    private function volverConError(string $codigo, string $mensaje): void
    {
        $_SESSION['participantes_error'] = $mensaje;
        $this->redirect("/torneos/{$codigo}/participantes");
    }
}
