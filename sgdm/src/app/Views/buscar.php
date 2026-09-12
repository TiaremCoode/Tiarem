<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buscar torneos — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/buscar.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active="buscar"></div>

  <main class="page">
    <div class="container page-head">
      <h1 class="display">Buscar torneos</h1>
      <p class="page-subhead">Consultá calendarios, resultados y posiciones sin necesidad de crear una cuenta.</p>
    </div>

    <form class="container search-bar-wrap" method="get" action="/torneos">
      <div class="search-bar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="search" name="q" class="search-input" placeholder="Nombre del torneo o código" value="<?= htmlspecialchars($texto) ?>">
      </div>
    </form>

    <div class="container filters-scroll">
      <a href="/torneos" class="filter-chip <?= ($formatoActivo === '' && $estadoActivo === '') ? 'active' : '' ?>">Todos</a>
      <?php foreach ($formatos as $f): ?>
        <a href="/torneos?formato=<?= urlencode($f['codigo']) ?>" class="filter-chip <?= $formatoActivo === $f['codigo'] ? 'active' : '' ?>"><?= htmlspecialchars($f['nombre']) ?></a>
      <?php endforeach; ?>
      <a href="/torneos?estado=en_curso" class="filter-chip <?= $estadoActivo === 'en_curso' ? 'active' : '' ?>">En curso</a>
    </div>

    <section class="container">
      <?php if (empty($torneos)): ?>
        <div class="card empty-state">
          <?php if ($hayFiltrosActivos): ?>
            <p>No encontramos torneos con esos criterios de búsqueda.</p>
            <a href="/torneos" class="btn btn-secondary btn-sm">Ver todos los torneos</a>
          <?php else: ?>
            <p>Todavía no hay torneos creados.</p>
            <a href="/torneos/crear" class="btn btn-secondary btn-sm">Creá el primero</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="results-list">
          <?php foreach ($torneos as $t): [$claseEstado, $textoEstado] = Presentacion::tagEstadoTorneo($t['estado']); ?>
            <a class="card tournament-row" href="/torneos/<?= htmlspecialchars($t['codigo_publico']) ?>">
              <div class="tournament-row-info">
                <span class="tag tag-<?= $claseEstado ?>">
                  <span class="tag-dot"></span><?= htmlspecialchars($textoEstado) ?>
                </span>
                <h3><?= htmlspecialchars($t['nombre']) ?></h3>
                <p class="tournament-meta"><?= htmlspecialchars($t['formato_nombre']) ?> · <?= (int) $t['inscriptos'] ?> participantes · ID #<?= htmlspecialchars($t['codigo_publico']) ?></p>
              </div>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
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
