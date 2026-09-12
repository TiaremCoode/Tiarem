/**
 * footer.js
 * Pie de página compartido entre vistas — capa de "Vista" (V del MVC).
 * Solo presentación, sin lógica de negocio.
 */

function renderFooter() {
  const year = 2026;
  return `
    <footer class="site-footer">
      <div class="container site-footer-inner">
        <p class="site-footer-copy">&copy; ${year} Tiarem</p>
        <nav class="site-footer-links" aria-label="Enlaces legales">
          <a href="#">Política de privacidad</a>
          <a href="#">Términos de uso</a>
        </nav>
        <p class="site-footer-origin">Hecho en Uruguay</p>
      </div>
    </footer>`;
}

document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('footer-mount');
  if (mount) {
    mount.outerHTML = renderFooter();
  }
});
