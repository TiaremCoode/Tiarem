<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Participantes — <?= htmlspecialchars($torneo['nombre']) ?> — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/detalle.css">
  <link rel="stylesheet" href="/css/auth.css">
  <link rel="stylesheet" href="/css/participantes.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active=""></div>

  <main class="page">

    <div class="container torneo-head">
      <a href="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        Volver al torneo
      </a>
      <h1 class="display torneo-title">Participantes</h1>
      <p class="torneo-meta">
        <?= htmlspecialchars($torneo['nombre']) ?> ·
        <?= (int) $cantidadActiva ?>/<?= (int) $torneo['max_participantes'] ?> anotados ·
        <?= TipoTorneo::esDeEquipo($tipoTorneo) ? 'Por equipos' : 'Individual' ?>
      </p>
    </div>

    <?php require __DIR__ . '/partials/mensajes.php'; ?>

    <!-- Alta de participante -->
    <section class="container">
      <div class="section-heading">
        <h2 class="display">Anotar participante</h2>
      </div>

      <?php if ($torneo['estado'] !== 'inscripcion'): ?>
        <div class="card empty-state"><p>Este torneo ya no está en etapa de inscripción.</p></div>
      <?php elseif ($cantidadActiva >= (int) $torneo['max_participantes']): ?>
        <div class="card empty-state"><p>Ya se llegó al máximo de participantes para este torneo.</p></div>
      <?php else: ?>
        <form class="card" method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/participantes">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">

          <div class="field">
            <label for="usuario_busqueda">ID público o correo de la persona</label>
            <input id="usuario_busqueda" name="usuario_busqueda" class="input" type="text" placeholder="Ej: A3F9K2P1 o persona@correo.com" required>
            <span class="field-hint">Tiene que estar registrada en el sistema. Si todavía no tiene cuenta, pedile que se registre primero.</span>
          </div>

          <?php if (TipoTorneo::esDeEquipo($tipoTorneo)): ?>
            <div class="field">
              <label for="equipo_id">Equipo existente</label>
              <select id="equipo_id" name="equipo_id" class="select">
                <option value="0">— Elegir uno nuevo abajo —</option>
                <?php foreach ($resumenEquipos as $r): ?>
                  <option value="<?= (int) $r['equipo']['id'] ?>"><?= htmlspecialchars($r['equipo']['nombre']) ?> (<?= $r['cantidad'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="equipo_nuevo_nombre">O nombre de un equipo nuevo</label>
              <input id="equipo_nuevo_nombre" name="equipo_nuevo_nombre" class="input" type="text" placeholder="Ej: Los Tigres" maxlength="100">
              <span class="field-hint">Esta disciplina se juega en equipos de <?= (int) $tipoTorneo['jugadores_por_equipo_min'] ?> a <?= (int) $tipoTorneo['jugadores_por_equipo_max'] ?> integrantes.</span>
            </div>
          <?php endif; ?>

          <button type="submit" class="btn btn-primary btn-block">Agregar participante</button>
        </form>
      <?php endif; ?>
    </section>

    <!-- Listado -->
    <section class="container">
      <div class="section-heading">
        <h2 class="display">Anotados</h2>
      </div>

      <?php if (!TipoTorneo::esDeEquipo($tipoTorneo)): ?>
        <?php if (empty($participantes)): ?>
          <div class="card empty-state"><p>Todavía no hay nadie anotado.</p></div>
        <?php else: ?>
          <div class="tournaments-list">
            <?php foreach ($participantes as $p): ?>
              <div class="card tournament-row">
                <div class="tournament-row-info">
                  <h3><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></h3>
                  <p class="tournament-meta">ID <?= htmlspecialchars($p['id_publico']) ?></p>
                </div>
                <form method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/participantes/<?= (int) $p['id'] ?>/eliminar" onsubmit="return confirm('¿Dar de baja a esta persona del torneo?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                  <button type="submit" class="btn btn-secondary btn-sm">Dar de baja</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <?php if (empty($resumenEquipos)): ?>
          <div class="card empty-state"><p>Todavía no hay ningún equipo anotado.</p></div>
        <?php else: ?>
          <div class="teams-list">
            <?php foreach ($resumenEquipos as $r): [$claseEstadoEquipo, $textoEstadoEquipo] = Presentacion::tagEstadoEquipo($r['estado']); ?>
              <div class="card team-card">
                <div class="team-card-head">
                  <h3><?= htmlspecialchars($r['equipo']['nombre']) ?></h3>
                  <span class="tag tag-<?= $claseEstadoEquipo ?>">
                    <?= $r['cantidad'] ?>/<?= (int) $tipoTorneo['jugadores_por_equipo_min'] ?>–<?= (int) $tipoTorneo['jugadores_por_equipo_max'] ?>
                    · <?= $textoEstadoEquipo ?>
                  </span>
                </div>
                <ul class="team-members">
                  <?php foreach ($r['integrantes'] as $m): ?>
                    <li>
                      <span><?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?></span>
                      <form method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/participantes/<?= (int) $m['participante_id'] ?>/eliminar" onsubmit="return confirm('¿Dar de baja a esta persona del equipo?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <button type="submit" class="btn-link-danger">Quitar</button>
                      </form>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </section>

  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
