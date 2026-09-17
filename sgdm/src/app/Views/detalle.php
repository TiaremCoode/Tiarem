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

    <?php require __DIR__ . '/partials/mensajes.php'; ?>

    <?php if ($historial): ?>
      <!-- Resultado final del torneo (RF: historial y estadísticas al finalizar) -->
      <section class="container">
        <div class="card champion-banner">
          <p class="champion-label">🏆 Campeón</p>
          <p class="champion-name">
            <?php if ($historial['campeon_nombre']): ?>
              <?= htmlspecialchars($historial['campeon_nombre'] . ' ' . $historial['campeon_apellido']) ?>
              <?php if ($historial['campeon_equipo']): ?>
                <span class="champion-team">(<?= htmlspecialchars($historial['campeon_equipo']) ?>)</span>
              <?php endif; ?>
            <?php else: ?>
              Sin determinar
            <?php endif; ?>
          </p>
        </div>
        <div class="stats-grid">
          <div class="card stat-card">
            <p class="stat-label">Mejor jugador</p>
            <p class="stat-value"><?= $historial['mejor_jugador_nombre'] ? htmlspecialchars($historial['mejor_jugador_nombre'] . ' ' . $historial['mejor_jugador_apellido']) : '—' ?></p>
          </div>
          <div class="card stat-card">
            <p class="stat-label">Mayor puntaje</p>
            <p class="stat-value"><?= $historial['mayor_puntaje_nombre'] ? htmlspecialchars($historial['mayor_puntaje_nombre'] . ' ' . $historial['mayor_puntaje_apellido']) : '—' ?></p>
          </div>
          <div class="card stat-card">
            <p class="stat-label">Invicto</p>
            <p class="stat-value"><?= $historial['invicto_nombre'] ? htmlspecialchars($historial['invicto_nombre'] . ' ' . $historial['invicto_apellido']) : 'Nadie' ?></p>
          </div>
          <div class="card stat-card">
            <p class="stat-label">Menos derrotas</p>
            <p class="stat-value"><?= $historial['menos_derrotas_nombre'] ? htmlspecialchars($historial['menos_derrotas_nombre'] . ' ' . $historial['menos_derrotas_apellido']) : '—' ?></p>
          </div>
          <div class="card stat-card">
            <p class="stat-label">Duración</p>
            <p class="stat-value"><?= htmlspecialchars(Presentacion::duracion($historial['fecha_inicio'], $historial['fecha_fin'])) ?></p>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($puedeGestionar && $torneo['estado'] === 'inscripcion'): ?>
      <section class="container">
        <div class="card iniciar-torneo-card">
          <?php if ($cantidadActiva >= 2): ?>
            <p>Ya se puede iniciar la competencia: se va a armar el calendario de enfrentamientos con <?= (int) $cantidadActiva ?> participantes anotados.</p>
            <form method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/iniciar"
                  onsubmit="return confirm('¿Iniciar el torneo? Ya no se van a poder anotar más participantes.');">
              <?= Csrf::field() ?>
              <button type="submit" class="btn btn-primary btn-block">Iniciar torneo</button>
            </form>
          <?php else: ?>
            <p>Hacen falta al menos 2 participantes anotados para iniciar el torneo (hay <?= (int) $cantidadActiva ?>).</p>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- Calendario de enfrentamientos -->
    <section class="container tab-panel">
      <div class="section-heading">
        <h2 class="display">Calendario de enfrentamientos</h2>
      </div>

      <?php if (empty($rondasConEnfrentamientos)): ?>
        <div class="card empty-state">
          <p>
            <?= $torneo['estado'] === 'inscripcion'
                ? 'Todavía se está armando la inscripción. El calendario aparece acá en cuanto el organizador inicie el torneo.'
                : 'Este torneo todavía no tiene enfrentamientos cargados.' ?>
          </p>
        </div>
      <?php else: ?>
        <?php foreach ($rondasConEnfrentamientos as $bloque): $ronda = $bloque['ronda']; ?>
          <div class="round-block">
            <div class="section-heading round-heading">
              <h3><?= htmlspecialchars(Presentacion::nombreRonda($torneo['formato_codigo'], (int) $ronda['numero'], $totalRondas)) ?></h3>
              <?php [$claseRonda, $textoRonda] = Presentacion::tagEstadoRonda($ronda['estado']); ?>
              <span class="tag tag-<?= $claseRonda ?>"><?= htmlspecialchars($textoRonda) ?></span>
            </div>
            <div class="matches-list">
              <?php foreach ($bloque['enfrentamientos'] as $e): ?>
                <div class="card match-row">
                  <div class="match-players">
                    <span class="player-name"><?= htmlspecialchars($e['p1_nombre'] . ' ' . $e['p1_apellido']) ?></span>
                    <span class="match-vs">vs</span>
                    <span class="player-name"><?= $e['p2_nombre'] ? htmlspecialchars($e['p2_nombre'] . ' ' . $e['p2_apellido']) : 'Libre' ?></span>
                  </div>

                  <?php if ($e['estado'] === 'jugado'): ?>
                    <span class="tag tag-cerrado">Resultado: <?= htmlspecialchars(Presentacion::numero($e['puntaje_participante1'])) ?>–<?= htmlspecialchars(Presentacion::numero($e['puntaje_participante2'])) ?></span>
                  <?php elseif ($e['estado'] === 'walkover'): ?>
                    <span class="tag tag-en-curso">Pase directo</span>
                  <?php elseif ($puedeGestionar && $ronda['estado'] === 'abierta'): ?>
                    <form method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/enfrentamientos/<?= (int) $e['id'] ?>/resultado" class="result-form">
                      <?= Csrf::field() ?>
                      <input type="number" step="0.01" name="puntaje1" class="input input-score" required aria-label="Puntaje de <?= htmlspecialchars($e['p1_nombre']) ?>">
                      <span class="result-form-sep">–</span>
                      <input type="number" step="0.01" name="puntaje2" class="input input-score" required aria-label="Puntaje de <?= htmlspecialchars($e['p2_nombre']) ?>">
                      <button type="submit" class="btn btn-secondary btn-sm">Cargar</button>
                    </form>
                  <?php else: ?>
                    <span class="tag tag-proximo">Pendiente</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- Tabla de posiciones: solo tiene sentido en liga y suizo -->
    <?php if ($torneo['formato_codigo'] !== 'eliminacion_directa'): ?>
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
    <?php else: ?>
      <section class="container">
        <div class="card empty-state">
          <p>En eliminación directa no hay tabla de posiciones: el campeón es quien queda en pie después de la final.</p>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($puedeGestionar): ?>
      <section class="container organizer-actions">
        <div class="section-heading">
          <h2 class="display">Panel del organizador</h2>
        </div>
        <div class="organizer-actions-grid">
          <a href="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/participantes" class="btn btn-primary btn-block">Gestionar participantes</a>
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
