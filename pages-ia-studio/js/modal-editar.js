/**
 * js/modal-editar.js
 * Controla el modal flotante de edición, lo rellena desde data-attributes
 * y envía la actualización asíncrona a data/actualizar.php mediante Fetch.
 */

(function () {
  'use strict';

  function obtenerClaseCategoria(nombre) {
    const normalizada = (nombre || '').trim().toLowerCase();
    switch (normalizada) {
      case 'trabajo':
        return 'category-badge--trabajo';
      case 'personal':
        return 'category-badge--personal';
      case 'importante':
        return 'category-badge--importante';
      case 'salud':
        return 'category-badge--salud';
      case 'estudio':
        return 'category-badge--estudio';
      default:
        return 'category-badge--default';
    }
  }

  function mostrarToast(mensaje) {
    const toast = document.getElementById('toast-banner');
    if (!toast) return;
    toast.textContent = mensaje;
    toast.classList.add('is-visible');
    window.clearTimeout(mostrarToast._timer);
    mostrarToast._timer = window.setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 3200);
  }

  document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('edit-modal');
    const form = document.getElementById('edit-event-form');
    if (!modal || !form) return;

    const btnCerrar = document.getElementById('edit-modal-close');
    const btnCancelar = document.getElementById('edit-modal-cancel');
    const alertaError = document.getElementById('edit-modal-error');
    const btnGuardar = document.getElementById('edit-modal-submit');

    const inputId = document.getElementById('edit-id');
    const inputTitulo = document.getElementById('edit-titulo');
    const inputFecha = document.getElementById('edit-fecha');
    const inputHora = document.getElementById('edit-hora');
    const inputCategoria = document.getElementById('edit-categoria');
    const inputDescripcion = document.getElementById('edit-descripcion');

    const abrirModal = function (botonEditar) {
      if (alertaError) {
        alertaError.hidden = true;
        alertaError.textContent = '';
      }

      inputId.value = botonEditar.dataset.id || '';
      inputTitulo.value = botonEditar.dataset.titulo || '';
      inputFecha.value = botonEditar.dataset.fecha || '';
      inputHora.value = botonEditar.dataset.hora || '';
      inputCategoria.value = botonEditar.dataset.categoriaId || '';
      inputDescripcion.value = botonEditar.dataset.descripcion || '';

      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      inputTitulo.focus();
    };

    const cerrarModal = function () {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
    };

    document.addEventListener('click', function (evento) {
      const botonEditar = evento.target.closest('[data-edit-event]');
      if (botonEditar) {
        abrirModal(botonEditar);
      }
    });

    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);

    modal.addEventListener('click', function (evento) {
      if (evento.target === modal) {
        cerrarModal();
      }
    });

    document.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape' && modal.classList.contains('is-open')) {
        cerrarModal();
      }
    });

    form.addEventListener('submit', async function (evento) {
      evento.preventDefault();

      if (alertaError) {
        alertaError.hidden = true;
        alertaError.textContent = '';
      }

      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

      const payload = {
        id: Number(inputId.value),
        titulo: inputTitulo.value.trim(),
        fecha: inputFecha.value,
        hora: inputHora.value,
        categoria_id: Number(inputCategoria.value),
        descripcion: inputDescripcion.value.trim(),
        csrf_token: csrfToken,
      };

      if (!payload.titulo || !payload.fecha || !payload.hora || !payload.categoria_id) {
        if (alertaError) {
          alertaError.textContent = 'Completa todos los campos obligatorios.';
          alertaError.hidden = false;
        }
        return;
      }

      const textoOriginal = btnGuardar ? btnGuardar.textContent : 'Guardar cambios';
      if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';
      }

      try {
        const respuesta = await fetch('data/actualizar.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken || '',
          },
          body: JSON.stringify(payload),
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.ok) {
          throw new Error(resultado.error || 'No se pudo actualizar el evento.');
        }

        const ev = resultado.evento;
        const tarjeta = document.querySelector('[data-event-card="' + ev.id + '"]');
        if (tarjeta) {
          const elTitulo = tarjeta.querySelector('[data-card-titulo]');
          const elFechaHora = tarjeta.querySelector('[data-card-datetime]');
          const elBadge = tarjeta.querySelector('[data-card-categoria]');
          const elDesc = tarjeta.querySelector('[data-card-descripcion]');
          const btnEdit = tarjeta.querySelector('[data-edit-event]');

          if (elTitulo) elTitulo.textContent = ev.titulo;
          if (elFechaHora) elFechaHora.textContent = ev.fecha + ' · ' + ev.hora;
          if (elBadge) {
            elBadge.textContent = ev.categoria;
            elBadge.className =
              'category-badge ' + obtenerClaseCategoria(ev.categoria);
          }
          if (elDesc) {
            elDesc.textContent = ev.descripcion || 'Sin descripción adicional.';
          }

          if (btnEdit) {
            btnEdit.dataset.titulo = ev.titulo;
            btnEdit.dataset.fecha = ev.fecha;
            btnEdit.dataset.hora = ev.hora;
            btnEdit.dataset.categoriaId = String(ev.categoria_id);
            btnEdit.dataset.descripcion = ev.descripcion || '';
          }
        }

        cerrarModal();
        mostrarToast('Evento actualizado correctamente');
      } catch (err) {
        if (alertaError) {
          alertaError.textContent =
            err instanceof Error ? err.message : 'Ocurrió un error al guardar.';
          alertaError.hidden = false;
        }
      } finally {
        if (btnGuardar) {
          btnGuardar.disabled = false;
          btnGuardar.textContent = textoOriginal;
        }
      }
    });
  });
})();
