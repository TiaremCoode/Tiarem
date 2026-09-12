<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($torneo['nombre']) ?> — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/detalle.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active=""></div>

  <main class="page">

    <div class="container torneo-head">
      <a href="/torneos" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        Volver
      </a>
      <?php [$claseEstado, $textoEstado] = Presentacion::tagEstadoTorneo($torneo['estado']); ?>
      <span class="tag tag-<?= $claseEstado ?>">
        <span class="tag-dot"></span><?= htmlspecialchars($textoEstado) ?>
      </span>
      <h1 class="display torneo-title"><?= htmlspecialchars($torneo['nombre']) ?></h1>
      <p class="torneo-meta">
        ID #<?= htmlspecialchars($torneo['codigo_publico']) ?> ·
        <?= htmlspecialchars($torneo['formato_nombre']) ?> ·
        <?= htmlspecialchars($torneo['tipo_nombre']) ?> ·
        Organiza <?= htmlspecialchars($torneo['organizador_nombre'] . ' ' . $torneo['organizador_apellido']) ?>
      </p>
    </div>

    <!-- Calendario de enfrentamientos -->
    <section class="container tab-panel">
      <div class="section-heading">
        <h2 class="display"><?= $ultimaRonda ? 'Ronda ' . (int) $ultimaRonda['numero'] : 'Calendario' ?></h2>
      </div>

      <?php if (empty($enfrentamientos)): ?>
        <div class="card empty-state">
          <p>
            <?= $torneo['estado'] === 'inscripcion'
                ? 'Todavía se está armando la inscripción. El calendario aparece acá en cuanto el organizador abra la primera ronda.'
                : 'Este torneo todavía no tiene enfrentamientos cargados.' ?>
          </p>
        </div>
      <?php else: ?>
        <div class="matches-list">
          <?php foreach ($enfrentamientos as $e): ?>
            <div class="card match-row">
              <div class="match-players">
                <span class="player-name"><?= htmlspecialchars($e['p1_nombre'] . ' ' . $e['p1_apellido']) ?></span>
                <span class="match-vs">vs</span>
                <span class="player-name"><?= $e['p2_nombre'] ? htmlspecialchars($e['p2_nombre'] . ' ' . $e['p2_apellido']) : 'Libre' ?></span>
              </div>
              <?php if ($e['estado'] === 'jugado'): ?>
                <span class="tag tag-cerrado">Resultado: <?= htmlspecialchars((string) $e['puntaje_participante1']) ?>–<?= htmlspecialchars((string) $e['puntaje_participante2']) ?></span>
              <?php else: ?>
                <span class="tag tag-proximo">Pendiente</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Tabla de posiciones -->
    <section class="container">
      <div class="section-heading">
        <h2 class="display">Tabla de posiciones</h2>
      </div>

      <?php if (empty($posiciones)): ?>
        <div class="card empty-state">
          <p>Todavía no hay posiciones para mostrar.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll card">
          <table class="standings-table">
            <thead>
              <tr><th>#</th><th>Participante</th><th>Pts</th><th>PG</th><th>PP</th></tr>
            </thead>
            <tbody>
              <?php foreach ($posiciones as $i => $p): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></td>
                  <td><?= htmlspecialchars((string) $p['puntos']) ?></td>
                  <td><?= (int) $p['victorias'] ?></td>
                  <td><?= (int) $p['derrotas'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($puedeGestionar): ?>
      <section class="container organizer-actions">
        <div class="section-heading">
          <h2 class="display">Panel del organizador</h2>
        </div>
        <div class="organizer-actions-grid">
          <a href="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/participantes" class="btn btn-primary btn-block">Gestionar participantes</a>
          <button class="btn btn-secondary btn-block" disabled title="Disponible en la próxima etapa">Cargar resultado</button>
          <button class="btn btn-secondary btn-block" disabled title="Disponible en la próxima etapa">Cerrar ronda</button>
        </div>
      </section>
    <?php endif; ?>

  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
