/**
 * detalle.js
 * Dos correcciones puntuales de la página de detalle de torneo, reportadas
 * sobre la carga de resultados:
 *
 * 1) Los botones +/- de cada campo de resultado (reemplazan a las
 *    flechitas nativas del input number, que subían de a 0.01 y quedaban
 *    feas dentro del recuadro).
 * 2) Al tocar "Cargar" en cualquier resultado, la página hacía un POST
 *    normal y volvía a mostrarse desde arriba del todo — molesto cuando
 *    se están cargando varios resultados seguidos. Como la carga sigue
 *    siendo un POST + redirect normal (no se pasó a fetch/AJAX para no
 *    complicar el flujo de cierre de ronda / avance de torneo, que puede
 *    cambiar bastante la página), lo que se hace es guardar la posición
 *    del scroll justo antes de enviar el formulario y restaurarla apenas
 *    carga la página de vuelta.
 */
document.addEventListener('DOMContentLoaded', () => {
  // --- 1) Botones +/- de los campos de resultado ---
  document.querySelectorAll('.score-step').forEach(boton => {
    boton.addEventListener('click', () => {
      const input = document.getElementById(boton.dataset.target);
      if (!input) return;
      const delta = Number(boton.dataset.step) || 0;
      const min = input.min !== '' ? Number(input.min) : 0;
      const max = input.max !== '' ? Number(input.max) : null;
      let valor = input.value === '' ? 0 : Number(input.value);
      valor += delta;
      if (valor < min) valor = min;
      if (max !== null && valor > max) valor = max;
      input.value = String(valor);
    });
  });

  // --- 2) Recordar la posición del scroll entre la carga de un resultado y la vuelta a la página ---
  const SCROLL_KEY = 'sgdm_detalle_scroll';

  document.querySelectorAll('[data-result-form]').forEach(form => {
    form.addEventListener('submit', () => {
      try {
        sessionStorage.setItem(SCROLL_KEY, String(window.scrollY));
      } catch (e) {
        // sessionStorage puede no estar disponible (modo privado, etc.);
        // si falla, simplemente no se restaura el scroll — no es crítico.
      }
    });
  });

  window.addEventListener('load', () => {
    let posicionGuardada = null;
    try {
      posicionGuardada = sessionStorage.getItem(SCROLL_KEY);
      sessionStorage.removeItem(SCROLL_KEY);
    } catch (e) {
      return;
    }
    if (posicionGuardada !== null) {
      window.scrollTo(0, Number(posicionGuardada));
    }
  });
});
