<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear torneo — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/crear.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active="crear"></div>

  <main class="page">
    <div class="container page-head">
      <h1 class="display">Crear torneo</h1>
      <p class="page-subhead">Tres pasos: formato, nombre y cantidad de participantes.</p>
    </div>

    <div class="container steps-indicator" id="steps-indicator">
      <div class="step active" data-step-indicator="1">
        <span class="step-num">1</span>
        <span class="step-label">Formato</span>
      </div>
      <div class="step-line"></div>
      <div class="step" data-step-indicator="2">
        <span class="step-num">2</span>
        <span class="step-label">Nombre</span>
      </div>
      <div class="step-line"></div>
      <div class="step" data-step-indicator="3">
        <span class="step-num">3</span>
        <span class="step-label">Participantes</span>
      </div>
    </div>

    <form id="crear-torneo-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">

      <!-- Paso 1: Formato (desde modulos_competencia) -->
      <section class="container form-step" data-step="1">
        <h2 class="display step-title">Elegí el formato</h2>

        <div class="format-options">
          <?php foreach ($formatos as $i => $f): ?>
            <label class="format-option">
              <input type="radio" name="modulo_competencia_id" value="<?= (int) $f['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>>
              <div class="format-option-body">
                <h3><?= htmlspecialchars($f['nombre']) ?></h3>
                <p><?= htmlspecialchars($f['descripcion']) ?></p>
              </div>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="step-actions">
          <button type="button" class="btn btn-primary btn-block" data-action="next">Continuar</button>
        </div>
      </section>

      <!-- Paso 2: Nombre + disciplina opcional (no complica el alta: valor por defecto "General") -->
      <section class="container form-step" data-step="2" hidden>
        <h2 class="display step-title">Nombrá tu torneo</h2>
        <div class="field">
          <label for="nombre-torneo">Nombre del torneo</label>
          <input id="nombre-torneo" name="nombre" class="input" type="text" placeholder="Ej: Copa Otoño 2026" maxlength="120">
          <span class="field-hint">Elegí un nombre corto y fácil de reconocer.</span>
        </div>
        <div class="field">
          <label for="tipo-torneo">Disciplina (opcional)</label>
          <select id="tipo-torneo" name="tipo_torneo_id" class="select">
            <?php foreach ($tipos as $t): ?>
              <option value="<?= (int) $t['id'] ?>" data-modalidad="<?= htmlspecialchars($t['modalidad']) ?>" <?= $t['nombre'] === 'General' ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="field-hint">Si no elegís ninguna, queda como "General".</span>
        </div>

        <div class="step-actions step-actions-split">
          <button type="button" class="btn btn-secondary" data-action="back">Atrás</button>
          <button type="button" class="btn btn-primary" data-action="next">Continuar</button>
        </div>
      </section>

      <!-- Paso 3: Participantes -->
      <section class="container form-step" data-step="3" hidden>
        <h2 class="display step-title" data-step3-title>Cantidad de participantes</h2>
        <div class="field">
          <label for="max-participantes" data-step3-label>Número máximo de participantes</label>
          <input id="max-participantes" name="max_participantes" class="input" type="number" placeholder="Ej: 16" min="2" max="512">
          <span class="field-hint" data-step3-hint>Podés cerrar la inscripción antes de llegar al máximo.</span>
        </div>

        <div id="crear-torneo-error" class="form-error-banner" hidden></div>

        <div class="step-actions step-actions-split">
          <button type="button" class="btn btn-secondary" data-action="back">Atrás</button>
          <button type="button" class="btn btn-primary" data-action="submit">Crear torneo</button>
        </div>
      </section>

    </form>

    <!-- Confirmación real: el torneo ya existe en la base en este punto -->
    <section class="container form-step" data-step="done" hidden>
      <div class="done-card">
        <div class="done-icon">&check;</div>
        <h2 class="display step-title">¡Torneo creado!</h2>
        <p>Ya está guardado y tiene su propio código público. Compartilo para que lo encuentren.</p>
        <a href="/torneos" data-torneo-link class="btn btn-secondary btn-block">Ver mi torneo</a>
      </div>
    </section>

  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
  <script src="/js/crear-torneo.js"></script>
</body>
</html>
