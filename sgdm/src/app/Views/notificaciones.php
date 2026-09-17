<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notificaciones — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <!-- detalle.css define .torneo-head/.torneo-title/.torneo-meta, que
       esta vista reutiliza para su propio encabezado: sin esta hoja el
       título "Notificaciones" quedaba sin padding, pegado al borde
       superior de la página. -->
  <link rel="stylesheet" href="/css/detalle.css">
  <link rel="stylesheet" href="/css/notificaciones.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active="notificaciones"></div>

  <main class="page">

    <div class="container torneo-head">
      <h1 class="display torneo-title">Notificaciones</h1>
      <p class="torneo-meta">Invitaciones a torneos, avisos de los organizadores y novedades de los tuyos.</p>
    </div>

    <?php require __DIR__ . '/partials/mensajes.php'; ?>

    <section class="container">
      <?php if (empty($notificaciones)): ?>
        <div class="card empty-state">
          <p>Por ahora no tenés notificaciones.</p>
        </div>
      <?php else: ?>
        <div class="notif-list">
          <?php foreach ($notificaciones as $n):
            $esInvitacionPendiente = $n['tipo'] === 'invitacion' && $n['estado_invitacion'] === 'pendiente';
            $etiquetas = [
                'invitacion'        => 'Invitación',
                'torneo_iniciado'   => 'Torneo iniciado',
                'aviso_organizador' => 'Aviso',
                'union_torneo'      => 'Nuevo participante',
                'torneo_completo'   => 'Torneo completo',
            ];
          ?>
            <div class="card notif-row <?= $esInvitacionPendiente ? 'notif-pendiente' : '' ?>">
              <div class="notif-row-info">
                <span class="tag tag-proximo"><?= htmlspecialchars($etiquetas[$n['tipo']] ?? $n['tipo']) ?></span>
                <p class="notif-mensaje"><?= htmlspecialchars($n['mensaje']) ?></p>
                <p class="notif-fecha">
                  <?= htmlspecialchars(date('d/m/Y H:i', strtotime($n['creado_en']))) ?>
                  <?php if ($n['codigo_publico']): ?>
                    · <a href="/torneos/<?= htmlspecialchars($n['codigo_publico']) ?>">Ver torneo</a>
                  <?php endif; ?>
                </p>
              </div>

              <?php if ($esInvitacionPendiente): ?>
                <div class="notif-acciones">
                  <form method="post" action="/notificaciones/<?= (int) $n['id'] ?>/aceptar">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-primary btn-sm">Aceptar</button>
                  </form>
                  <form method="post" action="/notificaciones/<?= (int) $n['id'] ?>/rechazar">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">Rechazar</button>
                  </form>
                </div>
              <?php elseif ($n['tipo'] === 'invitacion'): ?>
                <span class="tag <?= $n['estado_invitacion'] === 'aceptada' ? 'tag-en-curso' : 'tag-cerrado' ?>">
                  <?= $n['estado_invitacion'] === 'aceptada' ? 'Aceptada' : 'Rechazada' ?>
                </span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
