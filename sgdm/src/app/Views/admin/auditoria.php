<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auditoría — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/detalle.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active=""></div>

  <main class="page">
    <div class="container page-head">
      <h1 class="display">Registro de auditoría</h1>
      <p class="page-subhead">Últimas 50 acciones registradas en el sistema.</p>
    </div>

    <section class="container">
      <?php if (empty($registros)): ?>
        <div class="card empty-state"><p>Todavía no hay actividad registrada.</p></div>
      <?php else: ?>
        <div class="table-scroll card">
          <table class="standings-table">
            <thead>
              <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th></tr>
            </thead>
            <tbody>
              <?php foreach ($registros as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['creado_en']) ?></td>
                  <td><?= $r['nombre'] ? htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) : '—' ?></td>
                  <td><?= htmlspecialchars($r['accion']) ?></td>
                  <td><?= htmlspecialchars($r['entidad']) ?><?= $r['entidad_id'] ? ' #' . (int) $r['entidad_id'] : '' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/../partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
