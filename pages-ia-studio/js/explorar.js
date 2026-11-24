/**
 * js/explorar.js
 * Consume api.php vía Fetch, implementa debounce en la búsqueda
 * y renderiza dinámicamente las tarjetas de eventos en el DOM de forma segura.
 */

(function () {
  'use strict';

  function debounce(fn, esperaMs) {
    let temporizador = null;
    return function () {
      const contexto = this;
      const args = arguments;
      window.clearTimeout(temporizador);
      temporizador = window.setTimeout(function () {
        fn.apply(contexto, args);
      }, esperaMs);
    };
  }

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

  function crearTarjetaEvento(evento) {
    const article = document.createElement('article');
    article.className = 'event-card';
    article.setAttribute('data-event-card', String(evento.id));

    const cuerpo = document.createElement('div');

    const top = document.createElement('div');
    top.className = 'event-card-top';

    const info = document.createElement('div');
    const h3 = document.createElement('h3');
    h3.className = 'event-title';
    h3.textContent = evento.titulo;

    const fechaHora = document.createElement('p');
    fechaHora.className = 'event-datetime';
    fechaHora.textContent = evento.fecha + ' · ' + evento.hora;

    info.appendChild(h3);
    info.appendChild(fechaHora);

    const badge = document.createElement('span');
    badge.className = 'category-badge ' + obtenerClaseCategoria(evento.categoria);
    badge.textContent = evento.categoria;

    top.appendChild(info);
    top.appendChild(badge);

    const desc = document.createElement('p');
    desc.className = 'event-description';
    desc.style.marginTop = '0.85rem';
    desc.textContent = evento.descripcion || 'Sin descripción adicional.';

    cuerpo.appendChild(top);
    cuerpo.appendChild(desc);

    article.appendChild(cuerpo);
    return article;
  }

  document.addEventListener('DOMContentLoaded', function () {
    const inputBusqueda = document.getElementById('explore-search');
    const selectCategoria = document.getElementById('explore-category');
    const contenedor = document.getElementById('explore-results');
    const contador = document.getElementById('explore-count');
    const estadoMensaje = document.getElementById('explore-status');

    if (!contenedor || !inputBusqueda || !selectCategoria) return;

    let controladorActivo = null;

    const cargarEventos = async function () {
      const q = inputBusqueda.value.trim();
      const categoria = selectCategoria.value;

      if (controladorActivo) {
        controladorActivo.abort();
      }
      controladorActivo = new AbortController();

      if (estadoMensaje) {
        estadoMensaje.textContent = 'Buscando eventos...';
        estadoMensaje.hidden = false;
      }

      const params = new URLSearchParams();
      if (q !== '') params.set('q', q);
      if (categoria !== '') params.set('categoria', categoria);

      try {
        const respuesta = await fetch('api.php?' + params.toString(), {
          signal: controladorActivo.signal,
          headers: { Accept: 'application/json' },
        });

        if (respuesta.status === 401) {
          window.location.href = 'auth/login.php';
          return;
        }

        if (!respuesta.ok) {
          throw new Error('No se pudieron cargar los eventos.');
        }

        const datos = await respuesta.json();
        contenedor.innerHTML = '';

        if (contador) {
          const total = Number(datos.total || 0);
          contador.textContent =
            total === 1 ? '1 evento encontrado' : total + ' eventos encontrados';
        }

        if (!datos.eventos || datos.eventos.length === 0) {
          if (estadoMensaje) {
            estadoMensaje.textContent =
              'No se encontraron eventos con los criterios seleccionados.';
            estadoMensaje.hidden = false;
          }
          return;
        }

        if (estadoMensaje) {
          estadoMensaje.hidden = true;
        }

        const fragmento = document.createDocumentFragment();
        datos.eventos.forEach(function (ev) {
          fragmento.appendChild(crearTarjetaEvento(ev));
        });
        contenedor.appendChild(fragmento);
      } catch (error) {
        if (error && error.name === 'AbortError') {
          return;
        }
        if (estadoMensaje) {
          estadoMensaje.textContent =
            'Ocurrió un problema al consultar la API. Inténtalo de nuevo.';
          estadoMensaje.hidden = false;
        }
      }
    };

    const cargarConDebounce = debounce(cargarEventos, 300);

    inputBusqueda.addEventListener('input', cargarConDebounce);
    selectCategoria.addEventListener('change', cargarEventos);

    // Carga inicial al abrir explorar.php
    cargarEventos();
  });
})();
