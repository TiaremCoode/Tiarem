<?php
/**
 * HomeController.php
 * Página de inicio: presentación del sistema, los tres formatos (leídos
 * de la base, no hardcodeados) y los últimos torneos activos reales.
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $formatos = ModuloCompetencia::todosOrdenados();
        $torneosActivos = Torneo::buscarPublicos(estado: '', limite: 5);
        // Solo se muestran en la home los que no están cancelados
        $torneosActivos = array_values(array_filter(
            $torneosActivos,
            fn ($t) => $t['estado'] !== 'cancelado'
        ));

        $this->view('home', [
            'formatos'       => $formatos,
            'torneosActivos' => $torneosActivos,
        ]);
    }
}
