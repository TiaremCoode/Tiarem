<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>X La Copa — Gestión de torneos</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/home.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active="inicio"></div>

  <main class="page">

    <!-- Hero -->
    <section class="hero">
      <div class="container hero-inner">
        <p class="eyebrow">Sistema de gestión de torneos</p>
        <h1 class="display hero-title">Organizá tu<br>torneo en<br><span class="hero-title-accent">3 pasos</span></h1>
        <p class="hero-copy">Elegí el formato, sumá participantes y dejá que el sistema arme el calendario por vos.</p>
        <div class="hero-actions">
          <a href="/torneos/crear" class="btn btn-primary btn-block">Crear torneo</a>
          <a href="/torneos" class="btn btn-secondary btn-block">Buscar un torneo</a>
        </div>
      </div>
    </section>

    <div class="cut-divider container"></div>

    <!-- Formatos disponibles (leídos de la base: modulos_competencia) -->
    <section class="container">
      <div class="section-heading">
        <h2 class="display">Formatos</h2>
      </div>
      <div class="formats-grid">
        <?php foreach ($formatos as $formato): ?>
          <article class="card format-card">
            <span class="tag"><?= htmlspecialchars($formato['nombre']) ?></span>
            <p class="format-card-desc"><?= htmlspecialchars($formato['descripcion']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Torneos activos (reales, sin datos de prueba) -->
    <section class="container">
      <div class="section-heading">
        <h2 class="display">Torneos activos</h2>
        <a href="/torneos" class="section-link">Ver todos</a>
      </div>

      <?php if (empty($torneosActivos)): ?>
        <div class="card empty-state">
          <p>Todavía no hay torneos creados.</p>
          <a href="/torneos/crear" class="btn btn-secondary btn-sm">Creá el primero</a>
        </div>
      <?php else: ?>
        <div class="tournaments-list">
          <?php foreach ($torneosActivos as $t): [$claseEstado, $textoEstado] = Presentacion::tagEstadoTorneo($t['estado']); ?>
            <a class="card tournament-row" href="/torneos/<?= htmlspecialchars($t['codigo_publico']) ?>">
              <div class="tournament-row-info">
                <span class="tag tag-<?= $claseEstado ?>">
                  <span class="tag-dot"></span><?= htmlspecialchars($textoEstado) ?>
                </span>
                <h3><?= htmlspecialchars($t['nombre']) ?></h3>
                <p class="tournament-meta">
                  <?= htmlspecialchars($t['formato_nombre']) ?> ·
                  <?php if (($t['modalidad'] ?? 'individual') === 'equipo'): ?>
                    <?= (int) $t['equipos_inscriptos'] ?>/<?= (int) $t['max_participantes'] ?> equipos
                  <?php else: ?>
                    <?= (int) $t['inscriptos'] ?>/<?= (int) $t['max_participantes'] ?> participantes
                  <?php endif; ?>
                </p>
              </div>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Orientación a nuevos usuarios (RNF) -->
    <section class="container">
      <div class="onboarding-card">
        <h3>¿Primera vez en X La Copa?</h3>
        <p>Creá un torneo, sumá participantes y el sistema arma el calendario según el formato que elijas.</p>
        <a href="/torneos/crear" class="btn btn-secondary btn-sm">Empezar</a>
      </div>
    </section>

  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
