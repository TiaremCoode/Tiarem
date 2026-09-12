<?php
/**
 * partials/mensajes.php
 * Banda de error y/o de mensaje de confirmación, mostrada arriba del
 * contenido de una página. Antes este mismo bloque estaba repetido, con
 * la misma indentación de estilos inline, en perfil.php, participantes.php
 * y admin/usuarios.php. Cada vista solo necesita definir $error y/o
 * $mensaje antes de incluir este parcial (ambas variables son opcionales).
 */
$error = $error ?? null;
$mensaje = $mensaje ?? null;
?>
<?php if ($error): ?>
  <div class="container"><div class="auth-error page-banner"><?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>
<?php if ($mensaje): ?>
  <div class="container"><div class="card page-banner"><p class="page-banner-text"><?= htmlspecialchars($mensaje) ?></p></div></div>
<?php endif; ?>
