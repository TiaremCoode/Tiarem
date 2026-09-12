<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuarios — Administración — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/buscar.css">
  <link rel="stylesheet" href="/css/detalle.css">
  <link rel="stylesheet" href="/css/auth.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active=""></div>

  <main class="page">
    <div class="container page-head">
      <h1 class="display">Usuarios</h1>
      <p class="page-subhead">Crear, editar el rol o suspender cuentas del sistema.</p>
    </div>

    <?php require __DIR__ . '/../partials/mensajes.php'; ?>

    <form class="container search-bar-wrap" method="get" action="/admin/usuarios">
      <div class="search-bar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="search" name="q" class="search-input" placeholder="Nombre, apellido, correo o ID público" value="<?= htmlspecialchars($texto) ?>">
      </div>
    </form>

    <section class="container">
      <div class="section-heading">
        <h2 class="display">Cuentas</h2>
        <a href="/admin/usuarios/crear" class="section-link">+ Crear usuario</a>
      </div>

      <?php if (empty($usuarios)): ?>
        <div class="card empty-state"><p>No encontramos usuarios con esos criterios.</p></div>
      <?php else: ?>
        <div class="table-scroll card">
          <table class="standings-table">
            <thead>
              <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($usuarios as $u): ?>
                <tr>
                  <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                  <td><?= htmlspecialchars($u['email']) ?></td>
                  <td><?= htmlspecialchars($u['rol_nombre']) ?></td>
                  <td>
                    <span class="tag <?= $u['estado'] === 'activo' ? 'tag-en-curso' : 'tag-cerrado' ?>">
                      <?= htmlspecialchars(ucfirst($u['estado'])) ?>
                    </span>
                  </td>
                  <td><a href="/admin/usuarios/<?= (int) $u['id'] ?>/editar" class="btn btn-secondary btn-sm">Editar</a></td>
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
