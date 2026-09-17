<?php
/**
 * partials/user-context.php
 * Expone al frontend, en variables globales de JS, si hay o no una
 * sesión iniciada (y el nombre a mostrar) y cuántas notificaciones sin
 * leer tiene, para que nav.js pueda armar el menú y el contador sin
 * tener que volver a consultar al servidor. Ambas variables las computa
 * Controller::view() para cada vista, así que siempre están definidas
 * acá (ver app/Core/Controller.php).
 *
 * También expone permiteAgregadoDirecto y un token CSRF: nav.js los usa
 * para armar, en cualquier página, el menú de Ajustes con el interruptor
 * de privacidad (RF: "crear un menú de ajustes"), sin tener que
 * duplicar ese formulario en cada vista.
 */
?>
<script>
  window.SGDM_USER = <?= $usuarioActual
      ? json_encode([
          'nombre'                 => $usuarioActual['nombre'],
          'apellido'               => $usuarioActual['apellido'],
          'permiteAgregadoDirecto' => (bool) $usuarioActual['permite_agregado_directo'],
        ], JSON_UNESCAPED_UNICODE)
      : 'null' ?>;
  window.SGDM_NOTIFICACIONES_NO_LEIDAS = <?= (int) ($notificacionesNoLeidas ?? 0) ?>;
  window.SGDM_CSRF_TOKEN = <?= json_encode(Csrf::token()) ?>;
</script>
