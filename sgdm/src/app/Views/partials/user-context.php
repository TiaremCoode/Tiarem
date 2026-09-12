<?php
/**
 * partials/user-context.php
 * Expone al frontend, en una variable global de JS, si hay o no una
 * sesión iniciada (y el nombre a mostrar), para que nav.js pueda decidir
 * si mostrar "Iniciar sesión" o "Mi perfil / Cerrar sesión" sin tener que
 * volver a consultar al servidor.
 */
?>
<script>
  window.SGDM_USER = <?= $usuarioActual
      ? json_encode(['nombre' => $usuarioActual['nombre'], 'apellido' => $usuarioActual['apellido']], JSON_UNESCAPED_UNICODE)
      : 'null' ?>;
</script>
