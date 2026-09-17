/**
 * nav.js
 * Componente de navegación compartido entre vistas — capa de "Vista" del
 * MVC, solo presentación. Las rutas apuntan a las URL reales del
 * backend, y el bloque de acciones (y ahora también las notificaciones)
 * se arma según haya o no una sesión iniciada.
 *
 * Cada vista PHP expone `window.SGDM_USER` (null si es visitante, o
 * { nombre, apellido } si hay sesión) y `window.SGDM_NOTIFICACIONES_NO_LEIDAS`
 * antes de cargar este script — ver app/Views/partials/user-context.php
 * y Controller::view(), que calcula ambas cosas para toda vista.
 */

const NAV_ITEMS_BASE = [
  { id: 'inicio', label: 'Inicio', href: '/', icon: 'home' },
  { id: 'buscar', label: 'Buscar', href: '/torneos', icon: 'search' },
  { id: 'crear', label: 'Crear', href: '/torneos/crear', icon: 'plus' },
  { id: 'notificaciones', label: 'Avisos', href: '/notificaciones', icon: 'bell', soloConSesion: true },
  { id: 'perfil', label: 'Perfil', href: '/perfil', icon: 'user' },
];

const ICONS = {
  home: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>',
  search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>',
  plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>',
  user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  bell: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
  // Corrección: falta el ícono de "Ajustes" (menú de configuración,
  // nuevo) y el de notificaciones en desktop pasa de texto a este mismo
  // ícono de campanita de toda la vida.
  gear: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
};

function badgeHtml(cantidad) {
  if (!cantidad) return '';
  const texto = cantidad > 9 ? '9+' : String(cantidad);
  return `<span class="nav-badge">${texto}</span>`;
}

/**
 * Menú de Ajustes (RF: "crear un menú de ajustes... por ahora, solo la
 * opción de privacidad, pero dejarlo preparado para nuevas opciones").
 * Se arma acá, como parte de la navegación, para que esté disponible
 * desde cualquier página sin repetir el formulario en cada vista — usa
 * window.SGDM_USER.permiteAgregadoDirecto y window.SGDM_CSRF_TOKEN,
 * expuestos por partials/user-context.php.
 *
 * `suffix` evita ids duplicados: la navegación arma el botón + panel
 * una vez para mobile (topbar) y otra para desktop (navbar), y ambos
 * conviven en el DOM al mismo tiempo (el CSS es el que decide cuál se
 * ve según el ancho de pantalla).
 */
function ajustesBoton(suffix) {
  return `<button type="button" class="topbar-icon-btn" data-ajustes-toggle="${suffix}" aria-haspopup="true" aria-expanded="false" aria-label="Ajustes">${ICONS.gear}</button>`;
}

function ajustesPanel(suffix, usuario) {
  const checked = usuario.permiteAgregadoDirecto ? 'checked' : '';
  const inputId = `ajustes-privacidad-${suffix}`;
  return `
    <div class="ajustes-dropdown" data-ajustes-panel="${suffix}" hidden>
      <p class="ajustes-dropdown-title">Ajustes</p>
      <form method="post" action="/perfil/privacidad" class="ajustes-toggle-form" data-ajustes-form>
        <input type="hidden" name="csrf_token" value="${window.SGDM_CSRF_TOKEN || ''}">
        <input type="hidden" name="redirigir_a" value="">
        <label class="toggle-switch-row" for="${inputId}">
          <span class="toggle-switch-text">
            <span class="toggle-switch-title">Privacidad</span>
            <span class="toggle-switch-hint">Cualquier organizador me puede sumar directo a un torneo con mi ID o mi correo.</span>
          </span>
          <span class="toggle-switch">
            <input type="checkbox" id="${inputId}" name="permite_agregado_directo" value="1" ${checked} data-ajustes-autosubmit>
            <span class="toggle-switch-track"><span class="toggle-switch-thumb"></span></span>
          </span>
        </label>
      </form>
    </div>`;
}

function renderNav(activeId) {
  const usuario = window.SGDM_USER || null;
  const noLeidas = window.SGDM_NOTIFICACIONES_NO_LEIDAS || 0;

  const topbar = `
    <header class="topbar">
      <a class="topbar-brand" href="/">
        <img src="/assets/logo.png" alt="X La Copa">
        <span class="topbar-brand-text">X La Copa</span>
      </a>
      <div class="topbar-actions">
        ${usuario ? `<a class="topbar-icon-btn nav-badge-wrap" href="/notificaciones" aria-label="Notificaciones">${ICONS.bell}${badgeHtml(noLeidas)}</a>` : ''}
        ${usuario ? `<div class="ajustes-wrap">${ajustesBoton('mobile')}${ajustesPanel('mobile', usuario)}</div>` : ''}
        <a class="topbar-search-btn" href="/torneos" aria-label="Buscar torneos">${ICONS.search}</a>
      </div>
    </header>`;

  const accionesDesktop = usuario
    ? `<a href="/notificaciones" class="topbar-icon-btn nav-badge-wrap" aria-label="Notificaciones">${ICONS.bell}${badgeHtml(noLeidas)}</a>
       <div class="ajustes-wrap">${ajustesBoton('desktop')}${ajustesPanel('desktop', usuario)}</div>
       <a href="/perfil" class="btn btn-secondary btn-sm">Mi perfil</a>
       <a href="/torneos/crear" class="btn btn-primary btn-sm">Crear torneo</a>
       <a href="/logout" class="btn btn-secondary btn-sm">Cerrar sesión</a>`
    : `<a href="/login" class="btn btn-secondary btn-sm">Iniciar sesión</a>
       <a href="/registro" class="btn btn-primary btn-sm">Crear cuenta</a>`;

  const navbarDesktop = `
    <header class="navbar-desktop">
      <a class="navbar-brand" href="/">
        <img src="/assets/logo.png" alt="X La Copa">
        <span class="navbar-brand-text">X La Copa</span>
      </a>
      <nav class="navbar-links">
        <a href="/" class="${activeId === 'inicio' ? 'active' : ''}">Inicio</a>
        <a href="/torneos" class="${activeId === 'buscar' ? 'active' : ''}">Buscar torneos</a>
        <a href="/torneos/crear" class="${activeId === 'crear' ? 'active' : ''}">Crear torneo</a>
      </nav>
      <div class="navbar-actions">
        ${accionesDesktop}
      </div>
    </header>`;

  const itemsTabbar = (usuario ? NAV_ITEMS_BASE : NAV_ITEMS_BASE.filter(item => !item.soloConSesion))
    .map(item => item.id === 'perfil' && !usuario ? { ...item, href: '/login', label: 'Ingresar' } : item);

  const tabbar = `
    <nav class="tabbar" aria-label="Navegación principal">
      ${itemsTabbar.map(item => `
        <a class="tabbar-item ${item.id === activeId ? 'active' : ''} ${item.id === 'notificaciones' ? 'nav-badge-wrap' : ''}" href="${item.href}">
          ${ICONS[item.icon]}${item.id === 'notificaciones' ? badgeHtml(noLeidas) : ''}
          <span>${item.label}</span>
        </a>`).join('')}
    </nav>`;

  return topbar + navbarDesktop + tabbar;
}

/**
 * Cablea la apertura/cierre del menú de Ajustes y el auto-guardado del
 * interruptor de privacidad. Se llama una vez montada la navegación.
 */
function wireAjustes() {
  const botones = Array.from(document.querySelectorAll('[data-ajustes-toggle]'));

  function cerrarTodos(exceptoSuffix) {
    botones.forEach(btn => {
      const suffix = btn.dataset.ajustesToggle;
      if (suffix === exceptoSuffix) return;
      const panel = document.querySelector(`[data-ajustes-panel="${suffix}"]`);
      if (panel) panel.hidden = true;
      btn.setAttribute('aria-expanded', 'false');
    });
  }

  botones.forEach(btn => {
    const suffix = btn.dataset.ajustesToggle;
    const panel = document.querySelector(`[data-ajustes-panel="${suffix}"]`);
    if (!panel) return;
    btn.addEventListener('click', (ev) => {
      ev.stopPropagation();
      const abierto = !panel.hidden;
      cerrarTodos(null);
      panel.hidden = abierto;
      btn.setAttribute('aria-expanded', String(!abierto));
    });
    panel.addEventListener('click', (ev) => ev.stopPropagation());
  });

  document.addEventListener('click', () => cerrarTodos(null));

  document.querySelectorAll('[data-ajustes-autosubmit]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
      const form = checkbox.closest('[data-ajustes-form]');
      if (!form) return;
      const redirigirA = form.querySelector('input[name="redirigir_a"]');
      if (redirigirA) redirigirA.value = window.location.pathname + window.location.search;
      form.submit();
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('nav-mount');
  if (mount) {
    const activeId = mount.dataset.active || '';
    mount.outerHTML = renderNav(activeId);
    wireAjustes();
  }
});
