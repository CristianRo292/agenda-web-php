// ==========================================
// explorar.js · P11 · Fetch API & DOM Painting
// ==========================================

// 1. Referencias a los "huecos" del HTML
const lista    = document.getElementById('lista');
const estado   = document.getElementById('estado');
const campoQ   = document.getElementById('q');
const campoCat = document.getElementById('categoria');

// 2. Funciones auxiliares de formato
function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    const [a, m, d] = fechaStr.split('-');
    return `${d}/${m}/${a}`;
}

// 3. Crear una tarjeta HTML segura (usando textContent para evitar XSS)
function crearTarjeta(ev) {
    const article = document.createElement('article');
    article.className = 'card'; // Usa tus estilos CSS existentes de index.php

    // Header de la tarjeta (Badge + Fecha)
    const header = document.createElement('div');
    header.className = 'card__header';
    
    const badge = document.createElement('span');
    badge.className = 'card__badge';
    // Asignar clase específica si es personal o importante (igual que en index.php)
    if (ev.categoria?.toLowerCase() === 'personal') badge.classList.add('card__badge--personal');
    if (ev.categoria?.toLowerCase() === 'importante') badge.classList.add('card__badge--importante');
    badge.textContent = ev.categoria || 'Sin categoría';
    
    const time = document.createElement('time');
    time.className = 'card__time';
    time.textContent = formatearFecha(ev.fecha) + (ev.hora ? ' · ' + ev.hora : '');
    
    header.append(badge, time);

    // Título
    const title = document.createElement('h2');
    title.className = 'card__title';
    title.textContent = ev.titulo;

    // Descripción (opcional)
    const desc = document.createElement('p');
    desc.className = 'card__text';
    if (ev.descripcion) {
        desc.textContent = ev.descripcion;
    } else {
        desc.style.display = 'none';
    }

    // Ensamblar
    article.append(header, title, desc);
    return article;
}

// 4. Pintar los datos en el DOM
function pintar(datos) {
    lista.replaceChildren(); // Borra lo anterior
    
    const buscando = datos.filtros.q !== '' || (datos.filtros.categoria && datos.filtros.categoria !== 0);
    
    if (datos.total === 0) {
        estado.textContent = buscando 
            ? 'No se encontraron resultados para tu búsqueda.' 
            : 'Aún no tienes eventos registrados.';
        
        // Opcional: Mostrar estado vacío visual
        lista.innerHTML = `
            <div class="empty-state">
                <div class="empty-state__icon">${buscando ? '🔍' : '📅'}</div>
                <h3 class="empty-state__title">${buscando ? 'Sin resultados' : 'Agenda vacía'}</h3>
            </div>`;
        return;
    }

    // Actualizar contador
    const s = datos.total === 1 ? '' : 's';
    estado.textContent = `${datos.total} evento${s} ${buscando ? 'encontrado' : 'registrado'}${s}`;

    // Generar tarjetas
    datos.eventos.forEach(ev => {
        lista.append(crearTarjeta(ev));
    });
}

// 5. Función principal: PEDIR -> REVISAR -> CONVERTIR -> PINTAR
async function cargarEventos() {
    // Construir parámetros de URL
    const params = new URLSearchParams({
        q: campoQ.value.trim(),
        categoria: campoCat.value
    });
    
    estado.textContent = 'Cargando eventos…';
    lista.innerHTML = ''; // Limpiar mientras carga

    try {
        // PASO 1: PEDIR
        const res = await fetch(`api/api.php?${params.toString()}`);

        // PASO 2: REVISAR
        if (res.status === 401) {
            estado.innerHTML = 'Tu sesión terminó. <a href="auth/login.php">Inicia sesión</a> de nuevo.';
            return;
        }
        if (!res.ok) {
            throw new Error('La API respondió con error ' + res.status);
        }

        // PASO 3: CONVERTIR
        const datos = await res.json();

        // PASO 4: PINTAR
        pintar(datos);

    } catch (error) {
        console.error(error);
        estado.textContent = 'Error de conexión. Intenta recargar la página.';
    }
}

// 6. Event Listeners (Búsqueda en vivo con Debounce)
let espera;
campoQ.addEventListener('input', () => {
    clearTimeout(espera);
    espera = setTimeout(cargarEventos, 300); // Espera 300ms después de dejar de escribir
});

campoCat.addEventListener('change', cargarEventos);

// Evitar recarga al presionar Enter en el buscador
document.getElementById('filtros').addEventListener('submit', e => e.preventDefault());

// Carga inicial
cargarEventos();