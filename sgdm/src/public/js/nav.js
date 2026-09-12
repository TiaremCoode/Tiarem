/**
 * nav.js
 * Componente de navegación compartido entre vistas — capa de "Vista" del
 * MVC, solo presentación. Ahora las rutas apuntan a las URL reales del
 * backend (en vez de a los .html estáticos del maquetado original) y el
 * bloque de acciones se arma según haya o no una sesión iniciada.
 *
 * Cada vista PHP expone `window.SGDM_USER` (null si es visitante, o
 * { nombre, apellido } si hay sesión) antes de cargar este script — ver
 * app/Views/partials/user-context.php.
 */

const NAV_ITEMS = [
  { id: 'inicio', label: 'Inicio', href: '/', icon: 'home' },
  { id: 'buscar', label: 'Buscar', href: '/torneos', icon: 'search' },
  { id: 'crear', label: 'Crear', href: '/torneos/crear', icon: 'plus' },
  { id: 'perfil', label: 'Perfil', href: '/perfil', icon: 'user' },
];

const ICONS = {
  home: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>',
  search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>',
  plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>',
  user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
};

function renderNav(activeId) {
  const usuario = window.SGDM_USER || null;

  const topbar = `
    <header class="topbar">
      <a class="topbar-brand" href="/">
        <img src="/assets/logo.png" alt="X La Copa">
        <span class="topbar-brand-text">X La Copa</span>
      </a>
      <div class="topbar-actions">
        <a class="topbar-search-btn" href="/torneos" aria-label="Buscar torneos">${ICONS.search}</a>
      </div>
    </header>`;

  const accionesDesktop = usuario
    ? `<a href="/perfil" class="btn btn-secondary btn-sm">Mi perfil</a>
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

  const itemsTabbar = usuario
    ? NAV_ITEMS
    : NAV_ITEMS.map(item => item.id === 'perfil' ? { ...item, href: '/login', label: 'Ingresar' } : item);

  const tabbar = `
    <nav class="tabbar" aria-label="Navegación principal">
      ${itemsTabbar.map(item => `
        <a class="tabbar-item ${item.id === activeId ? 'active' : ''}" href="${item.href}">
          ${ICONS[item.icon]}
          <span>${item.label}</span>
        </a>`).join('')}
    </nav>`;

  return topbar + navbarDesktop + tabbar;
}

document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('nav-mount');
  if (mount) {
    const activeId = mount.dataset.active || '';
    mount.outerHTML = renderNav(activeId);
  }
});
