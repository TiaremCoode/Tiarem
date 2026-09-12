<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/auth.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active=""></div>

  <main class="page auth-wrap">
    <div class="auth-card card">
      <h1 class="display auth-title">Iniciar sesión</h1>
      <p class="auth-subtitle">Entrá para crear torneos, anotarte y ver tu actividad.</p>

      <?php if ($error): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="/login">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <div class="field">
          <label for="email">Correo electrónico</label>
          <input id="email" name="email" class="input" type="email" required autocomplete="email">
        </div>
        <div class="field">
          <label for="password">Contraseña</label>
          <input id="password" name="password" class="input" type="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>
      </form>

      <p class="auth-footer-link">¿Todavía no tenés cuenta? <a href="/registro">Creá una acá</a></p>
    </div>
  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/../partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
