<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ocurrió un error — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>
  <div id="nav-mount" data-active=""></div>
  <main class="page">
    <div class="container error-page">
      <h1 class="display error-page-title">Algo salió mal de nuestro lado</h1>
      <p class="error-page-copy error-page-copy-ancho">
        No es nada que hayas hecho vos. Ya quedó registrado; probá de nuevo en un momento, o volvé al inicio.
      </p>
      <a href="/" class="btn btn-primary">Volver al inicio</a>

      <?php if (!empty($detalleTecnico)): ?>
        <div class="card error-page-detail">
          <p class="eyebrow error-page-detail-label">Detalle técnico (solo visible fuera de producción)</p>
          <p class="error-page-detail-text">
            <?= htmlspecialchars($detalleTecnico) ?>
          </p>
        </div>
      <?php endif; ?>
    </div>
  </main>
  <div id="footer-mount"></div>
  <?php if (!isset($usuarioActual)) { $usuarioActual = null; } require __DIR__ . '/../partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
