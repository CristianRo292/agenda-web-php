/**
 * js/menu-usuario.js
 * Gestiona el dropdown del perfil de usuario en el header flotante
 * y la confirmación accesible para acciones destructivas.
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const botonMenu = document.getElementById('user-menu-btn');
    const dropdown = document.getElementById('user-dropdown');

    if (botonMenu && dropdown) {
      const cerrarMenu = function () {
        dropdown.classList.remove('is-open');
        botonMenu.setAttribute('aria-expanded', 'false');
      };

      const alternarMenu = function (evento) {
        evento.stopPropagation();
        const abierto = dropdown.classList.toggle('is-open');
        botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      };

      botonMenu.addEventListener('click', alternarMenu);

      document.addEventListener('click', function (evento) {
        if (!dropdown.contains(evento.target) && !botonMenu.contains(evento.target)) {
          cerrarMenu();
        }
      });

      document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
          cerrarMenu();
        }
      });
    }

    // Confirmación modal personalizada para eliminar evento o cuenta
    const modalConfirm = document.getElementById('confirm-modal');
    const confirmTitulo = document.getElementById('confirm-modal-title');
    const confirmMensaje = document.getElementById('confirm-modal-message');
    const confirmAceptar = document.getElementById('confirm-modal-accept');
    const confirmCancelar = document.getElementById('confirm-modal-cancel');
    let formularioPendiente = null;

    if (modalConfirm && confirmAceptar && confirmCancelar) {
      const cerrarConfirm = function () {
        modalConfirm.classList.remove('is-open');
        modalConfirm.setAttribute('aria-hidden', 'true');
        formularioPendiente = null;
      };

      document.querySelectorAll('form[data-confirm-message]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
          if (form.dataset.confirmed === 'true') {
            return;
          }
          evento.preventDefault();
          formularioPendiente = form;
          if (confirmTitulo) {
            confirmTitulo.textContent =
              form.getAttribute('data-confirm-title') || 'Confirmar acción';
          }
          if (confirmMensaje) {
            confirmMensaje.textContent =
              form.getAttribute('data-confirm-message') ||
              '¿Deseas continuar con esta acción?';
          }
          modalConfirm.classList.add('is-open');
          modalConfirm.setAttribute('aria-hidden', 'false');
          confirmAceptar.focus();
        });
      });

      confirmAceptar.addEventListener('click', function () {
        if (formularioPendiente) {
          formularioPendiente.dataset.confirmed = 'true';
          formularioPendiente.submit();
        }
      });

      confirmCancelar.addEventListener('click', cerrarConfirm);

      modalConfirm.addEventListener('click', function (evento) {
        if (evento.target === modalConfirm) {
          cerrarConfirm();
        }
      });

      document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && modalConfirm.classList.contains('is-open')) {
          cerrarConfirm();
        }
      });
    }
  });
})();
