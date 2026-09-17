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

    /** Carga el resultado de un enfrentamiento pendiente de la ronda abierta, con el formulario que corresponda a la disciplina del torneo. */
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

        [$puntaje1, $puntaje2, $motivo] = $torneo['formato_resultado'] === 'decision'
            ? $this->leerDecision($codigo)
            : $this->leerPuntajes($codigo);

        try {
            Competencia::registrarResultado($torneo, $enfrentamiento, $puntaje1, $puntaje2, (int) Auth::user()['id'], $motivo);
        } catch (RuntimeException $e) {
            $this->volverConError($codigo, $e->getMessage());
        }

        $_SESSION['torneo_mensaje'] = 'Resultado cargado.';
        $this->redirect("/torneos/{$codigo}");
    }

    /** Formato 'simple' o 'sets': dos números, como antes. */
    private function leerPuntajes(string $codigo): array
    {
        if (!is_numeric($_POST['puntaje1'] ?? null) || !is_numeric($_POST['puntaje2'] ?? null)) {
            $this->volverConError($codigo, 'Cargá un puntaje válido para los dos participantes.');
        }
        return [(float) $_POST['puntaje1'], (float) $_POST['puntaje2'], 'normal'];
    }

    /** Formato 'decision' (ajedrez y similares): quién ganó y, opcionalmente, el motivo — se traduce a un puntaje 1/0 (o 0.5/0.5 en empate) para reutilizar el mismo motor de competencia. */
    private function leerDecision(string $codigo): array
    {
        $ganador = $_POST['ganador'] ?? null;
        $motivo = in_array($_POST['motivo'] ?? 'normal', ['normal', 'abandono', 'tiempo'], true) ? $_POST['motivo'] : 'normal';

        return match ($ganador) {
            'p1'      => [1.0, 0.0, $motivo],
            'p2'      => [0.0, 1.0, $motivo],
            'empate'  => [0.5, 0.5, $motivo],
            default   => $this->volverConError($codigo, 'Elegí quién ganó (o empate) antes de guardar.'),
        };
    }

    private function volverConError(string $codigo, string $mensaje): void
    {
        $_SESSION['torneo_error'] = $mensaje;
        $this->redirect("/torneos/{$codigo}");
    }
}
