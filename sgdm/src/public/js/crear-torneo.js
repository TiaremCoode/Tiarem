/**
 * crear-torneo.js
 * Controla el asistente de 3 pasos y, en el paso final, envía los datos
 * al backend con fetch() (POST /api/torneos, en JSON) sin recargar la
 * página — así la creación del torneo sigue siendo rápida y no interrumpe
 * al organizador con una pantalla de carga completa.
 */

document.addEventListener('DOMContentLoaded', () => {
  const steps = Array.from(document.querySelectorAll('.form-step[data-step]'));
  const doneStep = document.querySelector('.form-step[data-step="done"]');
  const indicatorItems = Array.from(document.querySelectorAll('[data-step-indicator]'));
  const form = document.getElementById('crear-torneo-form');
  const errorBox = document.getElementById('crear-torneo-error');
  let current = 1;

  function showStep(stepNumber) {
    steps.forEach(step => {
      step.hidden = step.dataset.step !== String(stepNumber);
    });
    indicatorItems.forEach(item => {
      const itemStep = Number(item.dataset.stepIndicator);
      item.classList.toggle('active', itemStep === stepNumber);
      item.classList.toggle('completed', itemStep < stepNumber);
    });
    current = stepNumber;
  }

  function mostrarError(mensaje) {
    if (!errorBox) return;
    errorBox.textContent = mensaje;
    errorBox.hidden = false;
  }

  function ocultarError() {
    if (!errorBox) return;
    errorBox.hidden = true;
  }

  function showDone(codigo, url) {
    form.hidden = true;
    document.getElementById('steps-indicator').hidden = true;
    const link = doneStep.querySelector('[data-torneo-link]');
    if (link) {
      link.href = url;
      link.textContent = `Ver mi torneo (código ${codigo})`;
    }
    doneStep.hidden = false;
  }

  async function crearTorneo() {
    ocultarError();
    const boton = form.querySelector('[data-action="submit"]');
    boton.disabled = true;
    boton.textContent = 'Creando...';

    const formData = new FormData(form);
    const payload = {
      modulo_competencia_id: formData.get('modulo_competencia_id'),
      tipo_torneo_id: formData.get('tipo_torneo_id') || '',
      nombre: formData.get('nombre'),
      max_participantes: formData.get('max_participantes'),
      csrf_token: formData.get('csrf_token'),
    };

    try {
      const res = await fetch('/api/torneos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (!res.ok || !data.ok) {
        const primerError = data.errores ? Object.values(data.errores)[0] : data.error;
        mostrarError(primerError || 'No pudimos crear el torneo. Probá de nuevo.');
        boton.disabled = false;
        boton.textContent = 'Crear torneo';
        return;
      }

      showDone(data.codigo, data.url);
    } catch (err) {
      mostrarError('No pudimos conectar con el servidor. Revisá tu conexión e intentá de nuevo.');
      boton.disabled = false;
      boton.textContent = 'Crear torneo';
    }
  }

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-action]');
    if (!trigger) return;

    const action = trigger.dataset.action;
    if (action === 'next' && current < 3) {
      showStep(current + 1);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (action === 'back' && current > 1) {
      showStep(current - 1);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (action === 'submit') {
      crearTorneo();
    }
  });

  // Resalta visualmente el formato elegido (respaldo del estilo por
  // :checked, por si el navegador no soporta el selector :has()).
  function syncFormatSelection() {
    document.querySelectorAll('.format-option').forEach(label => {
      const radio = label.querySelector('input[type="radio"]');
      label.classList.toggle('selected', !!(radio && radio.checked));
    });
  }

  document.querySelectorAll('.format-option input[type="radio"]').forEach(input => {
    input.addEventListener('change', syncFormatSelection);
  });

  syncFormatSelection();
});
