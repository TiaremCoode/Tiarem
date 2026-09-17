<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi perfil — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/perfil.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active="perfil"></div>

  <main class="page">

    <section class="profile-head">
      <div class="container profile-head-inner">
        <div class="avatar"><?= htmlspecialchars(Usuario::iniciales($usuario)) ?></div>
        <h1 class="display"><?= htmlspecialchars(Usuario::nombreCompleto($usuario)) ?></h1>
        <p class="profile-id">Usuario #<?= htmlspecialchars($usuario['id_publico']) ?></p>
      </div>
    </section>

    <?php require __DIR__ . '/partials/mensajes.php'; ?>

    <section class="container">
      <div class="section-heading">
        <h2 class="display">Mis torneos</h2>
      </div>

      <?php if (empty($torneos)): ?>
        <div class="card empty-state">
          <p>Todavía no organizaste ni te anotaste a ningún torneo.</p>
          <a href="/torneos/crear" class="btn btn-secondary btn-sm">Crear mi primer torneo</a>
        </div>
      <?php else: ?>
        <div class="tournaments-list">
          <?php foreach ($torneos as $t): [$claseEstado, $textoEstado] = Presentacion::tagEstadoTorneo($t['estado']); ?>
            <a class="card tournament-row" href="/torneos/<?= htmlspecialchars($t['codigo_publico']) ?>">
              <div class="tournament-row-info">
                <span class="tag tag-<?= $claseEstado ?>">
                  <span class="tag-dot"></span><?= htmlspecialchars($textoEstado) ?>
                </span>
                <h3><?= htmlspecialchars($t['nombre']) ?></h3>
                <p class="tournament-meta"><?= ((int) $t['es_organizador']) === 1 ? 'Organizás este torneo' : 'Participás en este torneo' ?> · <?= htmlspecialchars($t['formato_nombre']) ?></p>
              </div>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if (!empty($titulos)): ?>
      <section class="container">
        <div class="section-heading">
          <h2 class="display">Mis títulos</h2>
        </div>
        <div class="tournaments-list">
          <?php foreach ($titulos as $t): ?>
            <a class="card tournament-row" href="/torneos/<?= htmlspecialchars($t['codigo_publico']) ?>">
              <div class="tournament-row-info">
                <span class="tag tag-cerrado"><span class="tag-dot"></span>🏆 Campeón</span>
                <h3><?= htmlspecialchars($t['nombre']) ?></h3>
                <p class="tournament-meta"><?= htmlspecialchars($t['formato_nombre']) ?></p>
              </div>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="container">
      <div class="section-heading">
        <h2 class="display">Datos de la cuenta</h2>
      </div>

      <form class="card profile-form" method="post" action="/perfil">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <div class="field">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" class="input" type="text" value="<?= htmlspecialchars($usuario['nombre']) ?>" maxlength="80">
        </div>
        <div class="field">
          <label for="apellido">Apellido</label>
          <input id="apellido" name="apellido" class="input" type="text" value="<?= htmlspecialchars($usuario['apellido']) ?>" maxlength="80">
        </div>
        <div class="field">
          <label>Correo electrónico</label>
          <input class="input" type="email" value="<?= htmlspecialchars($usuario['email']) ?>" disabled>
          <span class="field-hint">El correo no se puede cambiar desde acá.</span>
        </div>
        <button type="submit" class="btn btn-secondary btn-block">Guardar cambios</button>
      </form>
    </section>

    <?php if ($usuario['rol_codigo'] === Roles::ADMIN_GENERAL): ?>
      <section class="container">
        <div class="section-heading">
          <h2 class="display">Administración</h2>
        </div>
        <div class="organizer-actions-grid">
          <a href="/admin/usuarios" class="btn btn-secondary btn-block">Gestionar usuarios</a>
          <a href="/admin/auditoria" class="btn btn-secondary btn-block">Ver registro de auditoría</a>
        </div>
      </section>
    <?php endif; ?>

    <section class="container">
      <a href="/logout" class="btn btn-secondary btn-block btn-outline-danger logout-btn">Cerrar sesión</a>
    </section>

  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
