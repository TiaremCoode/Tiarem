<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($torneo['nombre']) ?> — X La Copa</title>
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/nav.css">
  <link rel="stylesheet" href="/css/detalle.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>
<body>

  <div id="nav-mount" data-active=""></div>

  <main class="page">

    <div class="container torneo-head">
      <a href="/torneos" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        Volver
      </a>
      <?php [$claseEstado, $textoEstado] = Presentacion::tagEstadoTorneo($torneo['estado']); ?>
      <span class="tag tag-<?= $claseEstado ?>">
        <span class="tag-dot"></span><?= htmlspecialchars($textoEstado) ?>
      </span>
      <h1 class="display torneo-title"><?= htmlspecialchars($torneo['nombre']) ?></h1>
      <p class="torneo-meta">
        ID #<?= htmlspecialchars($torneo['codigo_publico']) ?> ·
        <?= htmlspecialchars($torneo['formato_nombre']) ?> ·
        <?= htmlspecialchars($torneo['tipo_nombre']) ?> ·
        Organiza <?= htmlspecialchars($torneo['organizador_nombre'] . ' ' . $torneo['organizador_apellido']) ?>
      </p>
    </div>

    <?php require __DIR__ . '/partials/mensajes.php'; ?>

    <?php if ($debeAceptarReglas): ?>
      <!-- RF: quien participa tiene que leer y aceptar las reglas antes de poder ver el resto del torneo -->
      <section class="container">
        <div class="card reglas-gate">
          <h2 class="display">Antes de continuar, leé las reglas de este torneo</h2>
          <p class="reglas-gate-texto"><?= nl2br(htmlspecialchars($torneo['reglas'])) ?></p>
          <form method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/aceptar-reglas">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-primary btn-block">Acepto las reglas</button>
          </form>
        </div>
      </section>
    <?php else: ?>

    <?php if ($historial): ?>
      <!-- Resultado final del torneo (RF: historial y estadísticas al finalizar) -->
      <section class="container">
        <div class="card champion-banner">
          <p class="champion-label">🏆 Campeón</p>
          <p class="champion-name">
            <?php if ($historial['campeon_nombre']): ?>
              <?= htmlspecialchars($historial['campeon_nombre'] . ' ' . $historial['campeon_apellido']) ?>
              <?php if ($historial['campeon_equipo']): ?>
                <span class="champion-team">(<?= htmlspecialchars($historial['campeon_equipo']) ?>)</span>
              <?php endif; ?>
            <?php else: ?>
              Sin determinar
            <?php endif; ?>
          </p>
        </div>
        <div class="stats-grid">
          <div class="card stat-card">
            <p class="stat-label">Mejor jugador</p>
            <p class="stat-value"><?= $historial['mejor_jugador_nombre'] ? htmlspecialchars($historial['mejor_jugador_nombre'] . ' ' . $historial['mejor_jugador_apellido']) : '—' ?></p>
          </div>
          <div class="card stat-card">
            <p class="stat-label">Mayor puntaje</p>
            <p class="stat-value"><?= $historial['mayor_puntaje_nombre'] ? htmlspecialchars($historial['mayor_puntaje_nombre'] . ' ' . $historial['mayor_puntaje_apellido']) : '—' ?></p>
          </div>
          <div class="card stat-card">
            <p class="stat-label">Invicto</p>
            <p class="stat-value"><?= $historial['invicto_nombre'] ? htmlspecialchars($historial['invicto_nombre'] . ' ' . $historial['invicto_apellido']) : 'Nadie' ?></p>
          </div>
          <div class="card stat-card">
            <p class="stat-label">Menos derrotas</p>
            <p class="stat-value"><?= $historial['menos_derrotas_nombre'] ? htmlspecialchars($historial['menos_derrotas_nombre'] . ' ' . $historial['menos_derrotas_apellido']) : '—' ?></p>
          </div>
          <div class="card stat-card">
            <p class="stat-label">Duración</p>
            <p class="stat-value"><?= htmlspecialchars(Presentacion::duracion($historial['fecha_inicio'], $historial['fecha_fin'])) ?></p>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($puedeGestionar && $torneo['estado'] === 'inscripcion'): ?>
      <section class="container">
        <div class="card iniciar-torneo-card">
          <?php $unidadCompetidores = Competencia::esDeEquipo($torneo) ? 'equipos' : 'participantes'; ?>
          <?php if ($cantidadCompetidores >= 2): ?>
            <p>Ya se puede iniciar la competencia: se va a armar el calendario de enfrentamientos con <?= (int) $cantidadCompetidores ?> <?= $unidadCompetidores ?> anotados.</p>
            <form method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/iniciar"
                  onsubmit="return confirm('¿Iniciar el torneo? Ya no se van a poder anotar más participantes.');">
              <?= Csrf::field() ?>
              <button type="submit" class="btn btn-primary btn-block">Iniciar torneo</button>
            </form>
          <?php else: ?>
            <p>Hacen falta al menos 2 <?= $unidadCompetidores ?> anotados para iniciar el torneo (hay <?= (int) $cantidadCompetidores ?>).</p>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- Calendario de enfrentamientos -->
    <section class="container tab-panel">
      <div class="section-heading">
        <h2 class="display">Calendario de enfrentamientos</h2>
      </div>

      <?php if (empty($rondasConEnfrentamientos)): ?>
        <div class="card empty-state">
          <p>
            <?= $torneo['estado'] === 'inscripcion'
                ? 'Todavía se está armando la inscripción. El calendario aparece acá en cuanto el organizador inicie el torneo.'
                : 'Este torneo todavía no tiene enfrentamientos cargados.' ?>
          </p>
        </div>
      <?php else: ?>
        <?php foreach ($rondasConEnfrentamientos as $bloque): $ronda = $bloque['ronda']; ?>
          <div class="round-block">
            <div class="section-heading round-heading">
              <h3><?= htmlspecialchars(Presentacion::nombreRonda($torneo['formato_codigo'], (int) $ronda['numero'], $totalRondas)) ?></h3>
              <?php [$claseRonda, $textoRonda] = Presentacion::tagEstadoRonda($ronda['estado']); ?>
              <span class="tag tag-<?= $claseRonda ?>"><?= htmlspecialchars($textoRonda) ?></span>
            </div>
            <div class="matches-list">
              <?php foreach ($bloque['enfrentamientos'] as $e): ?>
                <?php
                  // Corrección post-revisión: en una disciplina de equipo el
                  // cruce se muestra por equipo (p1_equipo/p2_equipo, ver
                  // Enfrentamiento::deLaRonda()), no por la persona que quedó
                  // como representante de ese equipo en la fila.
                  $p1Display = $e['p1_equipo'] ?? ($e['p1_nombre'] . ' ' . $e['p1_apellido']);
                  $p2Display = $e['participante2_id']
                      ? ($e['p2_equipo'] ?? ($e['p2_nombre'] . ' ' . $e['p2_apellido']))
                      : null;
                  $setsParaGanar = (int) ($torneo['sets_para_ganar'] ?? 0);
                ?>
                <div class="card match-row">
                  <div class="match-players">
                    <span class="player-name"><?= htmlspecialchars($p1Display) ?></span>
                    <span class="match-vs">vs</span>
                    <span class="player-name"><?= $p2Display ? htmlspecialchars($p2Display) : 'Libre' ?></span>
                  </div>

                  <?php if ($e['estado'] === 'jugado'): ?>
                    <span class="tag tag-cerrado"><?= htmlspecialchars(Presentacion::resultadoTexto($e, $torneo['formato_resultado'])) ?></span>
                  <?php elseif ($e['estado'] === 'walkover'): ?>
                    <span class="tag tag-en-curso">Pase directo</span>
                  <?php elseif ($puedeGestionar && $ronda['estado'] === 'abierta'): ?>
                    <?php $accionResultado = "/torneos/" . htmlspecialchars($torneo['codigo_publico']) . "/enfrentamientos/" . (int) $e['id'] . "/resultado"; ?>
                    <?php if ($torneo['formato_resultado'] === 'decision'): ?>
                      <form method="post" action="<?= $accionResultado ?>" class="result-form result-form-decision" data-result-form>
                        <?= Csrf::field() ?>
                        <select name="ganador" class="select" required aria-label="Quién ganó">
                          <option value="">¿Quién ganó?</option>
                          <option value="p1"><?= htmlspecialchars($p1Display) ?></option>
                          <option value="p2"><?= htmlspecialchars($p2Display) ?></option>
                          <?php if ($permiteEmpate): ?>
                            <option value="empate">Empate</option>
                          <?php endif; ?>
                        </select>
                        <select name="motivo" class="select" aria-label="Motivo">
                          <option value="normal">Normal</option>
                          <option value="tiempo">Por tiempo</option>
                          <option value="abandono">Abandono</option>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Cargar</button>
                      </form>
                    <?php else: ?>
                      <?php
                        // Corrección: se sacó el texto "Puntaje" (en fútbol
                        // no se juega a puntos, se juega a goles) y de paso
                        // se sacó cualquier palabra fija en los dos formatos
                        // -queda más limpio- dejando solo el aria-label para
                        // lectores de pantalla. El paso pasa a ser de a 1
                        // (antes eran centésimos) y, en formato "sets", el
                        // input no deja cargar más sets de los que esa
                        // disciplina llega a jugar (tipos_torneo.sets_para_ganar).
                        $unidadSets = $torneo['formato_resultado'] === 'sets' ? 'sets ganados por' : 'goles de';
                      ?>
                      <form method="post" action="<?= $accionResultado ?>" class="result-form" data-result-form>
                        <?= Csrf::field() ?>
                        <div class="score-field">
                          <button type="button" class="score-step" data-step="-1" data-target="puntaje1-<?= (int) $e['id'] ?>" aria-label="Restar un <?= $torneo['formato_resultado'] === 'sets' ? 'set' : 'gol' ?> a <?= htmlspecialchars($p1Display) ?>" tabindex="-1">−</button>
                          <input type="number" step="1" min="0" <?= $setsParaGanar > 0 ? 'max="' . $setsParaGanar . '"' : '' ?>
                                 name="puntaje1" id="puntaje1-<?= (int) $e['id'] ?>" class="input input-score" required
                                 aria-label="<?= htmlspecialchars($unidadSets) ?> <?= htmlspecialchars($p1Display) ?>">
                          <button type="button" class="score-step" data-step="1" data-target="puntaje1-<?= (int) $e['id'] ?>" aria-label="Sumar un <?= $torneo['formato_resultado'] === 'sets' ? 'set' : 'gol' ?> a <?= htmlspecialchars($p1Display) ?>" tabindex="-1">+</button>
                        </div>
                        <span class="result-form-sep">–</span>
                        <div class="score-field">
                          <button type="button" class="score-step" data-step="-1" data-target="puntaje2-<?= (int) $e['id'] ?>" aria-label="Restar un <?= $torneo['formato_resultado'] === 'sets' ? 'set' : 'gol' ?> a <?= htmlspecialchars($p2Display) ?>" tabindex="-1">−</button>
                          <input type="number" step="1" min="0" <?= $setsParaGanar > 0 ? 'max="' . $setsParaGanar . '"' : '' ?>
                                 name="puntaje2" id="puntaje2-<?= (int) $e['id'] ?>" class="input input-score" required
                                 aria-label="<?= htmlspecialchars($unidadSets) ?> <?= htmlspecialchars($p2Display) ?>">
                          <button type="button" class="score-step" data-step="1" data-target="puntaje2-<?= (int) $e['id'] ?>" aria-label="Sumar un <?= $torneo['formato_resultado'] === 'sets' ? 'set' : 'gol' ?> a <?= htmlspecialchars($p2Display) ?>" tabindex="-1">+</button>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">Cargar</button>
                      </form>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="tag tag-proximo">Pendiente</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- Tabla de posiciones: solo tiene sentido en liga y suizo -->
    <?php if ($torneo['formato_codigo'] !== 'eliminacion_directa'): ?>
      <section class="container">
        <div class="section-heading">
          <h2 class="display">Tabla de posiciones</h2>
        </div>

        <?php if (empty($posiciones)): ?>
          <div class="card empty-state">
            <p>Todavía no hay posiciones para mostrar.</p>
          </div>
        <?php else: ?>
          <?php
            // Corrección post-revisión: los puntos venían con decimales
            // porque tabla_posiciones.puntos es DECIMAL (para no cerrarle
            // la puerta a una disciplina que sí puntúe fraccionado, como
            // el ajedrez), aunque acá siempre se cargan enteros — se
            // muestran con Presentacion::numero(), que ya recorta los
            // ".00" y solo deja decimales si de verdad los hay. También se
            // agregan las columnas de "a favor / en contra / diferencia"
            // (goles en fútbol, sets en pádel/vóley) que pedía el reporte,
            // salvo en formato "decision" (ajedrez), donde no hay nada de
            // eso para contar más allá de puntos/PG/PE/PP.
            $esEquipoTabla   = Competencia::esDeEquipo($torneo);
            $mostrarMarcador = $torneo['formato_resultado'] !== 'decision';
            $prefijoMarcador = $torneo['formato_resultado'] === 'sets' ? 'S' : 'G';
          ?>
          <div class="table-scroll card">
            <table class="standings-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th><?= $esEquipoTabla ? 'Equipo' : 'Participante' ?></th>
                  <th>Pts</th>
                  <th>PJ</th>
                  <th>PG</th>
                  <th>PE</th>
                  <th>PP</th>
                  <?php if ($mostrarMarcador): ?>
                    <th><?= $prefijoMarcador ?>F</th>
                    <th><?= $prefijoMarcador ?>C</th>
                    <th>Dif.</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($posiciones as $i => $p): ?>
                  <?php
                    $partidosJugados = (int) $p['victorias'] + (int) $p['empates'] + (int) $p['derrotas'];
                    $diferencia = (float) $p['a_favor'] - (float) $p['en_contra'];
                  ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($p['equipo_nombre'] ?? ($p['nombre'] . ' ' . $p['apellido'])) ?></td>
                    <td><?= Presentacion::numero($p['puntos']) ?></td>
                    <td><?= $partidosJugados ?></td>
                    <td><?= (int) $p['victorias'] ?></td>
                    <td><?= (int) $p['empates'] ?></td>
                    <td><?= (int) $p['derrotas'] ?></td>
                    <?php if ($mostrarMarcador): ?>
                      <td><?= Presentacion::numero($p['a_favor']) ?></td>
                      <td><?= Presentacion::numero($p['en_contra']) ?></td>
                      <td><?= ($diferencia > 0 ? '+' : '') . Presentacion::numero($diferencia) ?></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    <?php else: ?>
      <section class="container">
        <div class="card empty-state">
          <p>En eliminación directa no hay tabla de posiciones: el campeón es quien queda en pie después de la final.</p>
        </div>
      </section>
    <?php endif; ?>

    <!-- Reglas: siempre accesibles para cualquiera (RF) -->
    <section class="container">
      <div class="section-heading">
        <h2 class="display">Reglas</h2>
      </div>
      <?php if (!empty($torneo['reglas'])): ?>
        <div class="card reglas-card"><p><?= nl2br(htmlspecialchars($torneo['reglas'])) ?></p></div>
      <?php else: ?>
        <div class="card empty-state"><p>El organizador todavía no escribió las reglas de este torneo.</p></div>
      <?php endif; ?>

      <?php if ($puedeGestionar): ?>
        <form method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/reglas" class="card reglas-form">
          <?= Csrf::field() ?>
          <div class="field">
            <label for="reglas">Redactar o actualizar las reglas</label>
            <textarea id="reglas" name="reglas" class="input" rows="5" maxlength="4000"><?= htmlspecialchars($torneo['reglas'] ?? '') ?></textarea>
            <span class="field-hint">A quien se anote y todavía no las haya aceptado, se las vamos a mostrar antes de dejarlo ver el resto del torneo.</span>
          </div>
          <button type="submit" class="btn btn-secondary btn-block">Guardar reglas</button>
        </form>
      <?php endif; ?>
    </section>

    <!-- Avisos del organizador (RF: cartelera + dispara notificaciones a los participantes) -->
    <section class="container">
      <div class="section-heading">
        <h2 class="display">Avisos</h2>
      </div>
      <?php if (empty($avisos)): ?>
        <div class="card empty-state"><p>Todavía no hay avisos publicados.</p></div>
      <?php else: ?>
        <div class="avisos-list">
          <?php foreach ($avisos as $a): ?>
            <div class="card aviso-row">
              <p class="aviso-mensaje"><?= nl2br(htmlspecialchars($a['mensaje'])) ?></p>
              <p class="aviso-fecha"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['creado_en']))) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($puedeGestionar): ?>
        <form method="post" action="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/avisos" class="card">
          <?= Csrf::field() ?>
          <div class="field">
            <label for="mensaje">Publicar un aviso nuevo</label>
            <textarea id="mensaje" name="mensaje" class="input" rows="3" maxlength="500" required></textarea>
          </div>
          <button type="submit" class="btn btn-secondary btn-block">Publicar aviso</button>
        </form>
      <?php endif; ?>
    </section>

    <?php if ($puedeGestionar): ?>
      <section class="container organizer-actions">
        <div class="section-heading">
          <h2 class="display">Panel del organizador</h2>
        </div>
        <div class="organizer-actions-grid">
          <a href="/torneos/<?= htmlspecialchars($torneo['codigo_publico']) ?>/participantes" class="btn btn-primary btn-block">Gestionar participantes</a>
        </div>
      </section>
    <?php endif; ?>

    <?php endif; // fin del if ($debeAceptarReglas) ?>

  </main>

  <div id="footer-mount"></div>

  <?php require __DIR__ . '/partials/user-context.php'; ?>
  <script src="/js/nav.js"></script>
  <script src="/js/detalle.js"></script>
  <script src="/js/footer.js"></script>
</body>
</html>
