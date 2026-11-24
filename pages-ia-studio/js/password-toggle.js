/**
 * js/password-toggle.js
 * Controla el botón de "ojo" para mostrar u ocultar contraseñas de forma accesible.
 */

(function () {
  'use strict';

  const ICONO_OJO_ABIERTO =
    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>' +
    '<circle cx="12" cy="12" r="3"></circle>' +
    '</svg>';

  const ICONO_OJO_CERRADO =
    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>' +
    '<path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>' +
    '<path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>' +
    '<line x1="2" y1="2" x2="22" y2="22"></line>' +
    '</svg>';

  document.addEventListener('DOMContentLoaded', function () {
    const botones = document.querySelectorAll('[data-password-toggle]');

    botones.forEach(function (boton) {
      const targetId = boton.getAttribute('data-password-toggle');
      const input = targetId ? document.getElementById(targetId) : null;
      if (!input) return;

      // Inicializar icono en estado oculto
      boton.innerHTML = ICONO_OJO_ABIERTO;

      boton.addEventListener('click', function () {
        const estaOculto = input.type === 'password';
        input.type = estaOculto ? 'text' : 'password';
        boton.setAttribute('aria-pressed', estaOculto ? 'true' : 'false');
        boton.setAttribute(
          'aria-label',
          estaOculto ? 'Ocultar contraseña' : 'Mostrar contraseña'
        );
        boton.innerHTML = estaOculto ? ICONO_OJO_CERRADO : ICONO_OJO_ABIERTO;
      });
    });
  });
})();
