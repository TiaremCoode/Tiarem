<?php
/**
 * CompetenciaController.php
 * Módulo de resultados (módulo funcional mínimo #7 de la letra): dispara
 * el arranque del torneo y la carga de resultados desde el detalle
 * público. Toda la lógica de negocio (armar cruces, actualizar
 * posiciones, avanzar de ronda, finalizar) vive en Core/Competencia.php
 * y en los módulos de Formatos/ — este controlador solo valida permisos,
 * lee el formulario y traduce los errores de negocio a un mensaje para
 * el usuario, igual que el resto de los controladores.
 *
 * Mismo criterio de permisos que ParticipanteController: solo el
 * organizador de ESE torneo o un admin general pueden iniciar el torneo
 * o cargar resultados.
 */
class CompetenciaController extends Controller
{
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

    /** Arranca el torneo: genera el fixture inicial según su formato y lo pasa a "en curso". */
    public function iniciar(string $codigo): void
    {
        $torneo = $this->autorizar($codigo);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->volverConError($codigo, 'Tu sesión de formulario venció. Volvé a intentarlo.');
        }

        try {
            Competencia::iniciar($torneo, (int) Auth::user()['id']);
        } catch (RuntimeException $e) {
            $this->volverConError($codigo, $e->getMessage());
        }

        $_SESSION['torneo_mensaje'] = 'El torneo arrancó: ya se armó el calendario de enfrentamientos.';
        $this->redirect("/torneos/{$codigo}");
    }

    /** Carga el resultado de un enfrentamiento pendiente de la ronda abierta. */
    public function resultado(string $codigo, string $enfrentamientoId): void
    {
        $torneo = $this->autorizar($codigo);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->volverConError($codigo, 'Tu sesión de formulario venció. Volvé a intentarlo.');
        }

        $enfrentamiento = Enfrentamiento::encontrarDelTorneo((int) $enfrentamientoId, (int) $torneo['id']);
        if (!$enfrentamiento) {
            $this->volverConError($codigo, 'No encontramos ese enfrentamiento en este torneo.');
        }

        if (!is_numeric($_POST['puntaje1'] ?? null) || !is_numeric($_POST['puntaje2'] ?? null)) {
            $this->volverConError($codigo, 'Cargá un puntaje válido para los dos participantes.');
        }

        try {
            Competencia::registrarResultado(
                $torneo,
                $enfrentamiento,
                (float) $_POST['puntaje1'],
                (float) $_POST['puntaje2'],
                (int) Auth::user()['id']
            );
        } catch (RuntimeException $e) {
            $this->volverConError($codigo, $e->getMessage());
        }

        $_SESSION['torneo_mensaje'] = 'Resultado cargado.';
        $this->redirect("/torneos/{$codigo}");
    }

    private function volverConError(string $codigo, string $mensaje): void
    {
        $_SESSION['torneo_error'] = $mensaje;
        $this->redirect("/torneos/{$codigo}");
    }
}
