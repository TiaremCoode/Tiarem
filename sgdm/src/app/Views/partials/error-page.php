<?php
/**
 * partials/error-page.php
 * Esqueleto compartido por las páginas de error simples (403, 404), que
 * antes repetían el mismo HTML y los mismos estilos inline con solo el
 * título y el texto distintos. errors/500.php no usa este parcial porque
 * además muestra el detalle técnico en desarrollo.
 *
 * Variables esperadas: $tituloPestana (título de <title>), $titulo (h1),
 * $texto (parrafo).
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($tituloPestana) ?> — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>
  <div id="nav-mount" data-active=""></div>
  <main class="page">
    <div class="container error-page">
      <h1 class="display error-page-title"><?= htmlspecialchars($titulo) ?></h1>
      <p class="error-page-copy"><?= htmlspecialchars($texto) ?></p>
      <a href="/" class="btn btn-primary">Volver al inicio</a>
    </div>
  </main>
  <div id="footer-mount"></div>
  <?php $usuarioActual = Auth::user(); require __DIR__ . '/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
