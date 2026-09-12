<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $modo === 'crear' ? 'Crear usuario' : 'Editar usuario' ?> — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/auth.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active=""></div>

  <main class="page auth-wrap">
    <div class="auth-card card">
      <a href="/admin/usuarios" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M15 18l-6-6 6-6"/></svg>
        Volver al listado
      </a>
      <h1 class="display auth-title"><?= $modo === 'crear' ? 'Crear usuario' : 'Editar usuario' ?></h1>
      <p class="auth-subtitle">
        <?= $modo === 'crear'
            ? 'Se crea con la contraseña que definas acá.'
            : htmlspecialchars(Usuario::nombreCompleto($usuario)) . ' · ' . htmlspecialchars($usuario['email']) ?>
      </p>

      <?php if ($error): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($modo === 'crear'): ?>
        <form method="post" action="/admin/usuarios/crear">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
          <div class="field">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" class="input" type="text" required maxlength="80" value="<?= htmlspecialchars($valores['nombre'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="apellido">Apellido</label>
            <input id="apellido" name="apellido" class="input" type="text" required maxlength="80" value="<?= htmlspecialchars($valores['apellido'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" class="input" type="email" required value="<?= htmlspecialchars($valores['email'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="password">Contraseña inicial</label>
            <input id="password" name="password" class="input" type="password" required minlength="8">
            <span class="field-hint">Al menos 8 caracteres. La persona la puede cambiar después.</span>
          </div>
          <div class="field">
            <label for="rol_id">Rol</label>
            <select id="rol_id" name="rol_id" class="select">
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Crear usuario</button>
        </form>

      <?php else: ?>
        <form method="post" action="/admin/usuarios/<?= (int) $usuario['id'] ?>/editar">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
          <div class="field">
            <label for="rol_id">Rol</label>
            <select id="rol_id" name="rol_id" class="select">
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>" <?= (int) $r['id'] === (int) $usuario['rol_id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="estado">Estado de la cuenta</label>
            <select id="estado" name="estado" class="select">
              <option value="activo" <?= $usuario['estado'] === 'activo' ? 'selected' : '' ?>>Activa</option>
              <option value="suspendido" <?= $usuario['estado'] === 'suspendido' ? 'selected' : '' ?>>Suspendida</option>
            </select>
            <span class="field-hint">Una cuenta suspendida no puede iniciar sesión, pero conserva su historial.</span>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button>
        </form>

        <div class="auth-divider">o</div>

        <form method="post" action="/admin/usuarios/<?= (int) $usuario['id'] ?>/eliminar" onsubmit="return confirm('¿Eliminar esta cuenta? Esta acción no se puede deshacer.');">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
          <button type="submit" class="btn btn-secondary btn-block btn-outline-danger">Eliminar cuenta</button>
        </form>
        <p class="field-hint field-hint-center">Solo se puede eliminar si la cuenta todavía no tiene actividad registrada.</p>
      <?php endif; ?>
    </div>
  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/../partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
