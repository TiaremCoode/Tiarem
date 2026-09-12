<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear cuenta — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/auth.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active=""></div>

  <main class="page auth-wrap">
    <div class="auth-card card">
      <h1 class="display auth-title">Creá tu cuenta</h1>
      <p class="auth-subtitle">La necesitás para organizar torneos o anotarte a uno.</p>

      <?php if ($error): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="/registro">
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
          <label for="password">Contraseña</label>
          <input id="password" name="password" class="input" type="password" required minlength="8" autocomplete="new-password">
          <span class="field-hint">Al menos 8 caracteres.</span>
        </div>
        <div class="field">
          <label for="password_confirm">Repetí la contraseña</label>
          <input id="password_confirm" name="password_confirm" class="input" type="password" required minlength="8" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Crear cuenta</button>
      </form>

      <p class="auth-footer-link">¿Ya tenés cuenta? <a href="/login">Iniciá sesión</a></p>
    </div>
  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/../partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
