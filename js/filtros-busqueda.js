// ==========================================
// Menú desplegable de filtros de búsqueda
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    const btnFiltros = document.getElementById('btnFiltros');
    const filtersPanel = document.getElementById('filtersPanel');
    const btnResetFiltros = document.getElementById('btnResetFiltros');
    const formFiltros = document.getElementById('formFiltros');
    const checkboxes = formFiltros.querySelectorAll('input[type="checkbox"]');

    // 1. Abrir/cerrar panel al hacer clic en el botón
    btnFiltros.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = filtersPanel.classList.toggle('is-open');
        btnFiltros.classList.toggle('is-active', isOpen);
        btnFiltros.setAttribute('aria-expanded', isOpen);
        filtersPanel.setAttribute('aria-hidden', !isOpen);
    });

    // 2. Cerrar panel al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!filtersPanel.contains(e.target) && !btnFiltros.contains(e.target)) {
            filtersPanel.classList.remove('is-open');
            btnFiltros.classList.remove('is-active');
            btnFiltros.setAttribute('aria-expanded', 'false');
            filtersPanel.setAttribute('aria-hidden', 'true');
        }
    });

    // 3. Cerrar panel con tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && filtersPanel.classList.contains('is-open')) {
            filtersPanel.classList.remove('is-open');
            btnFiltros.classList.remove('is-active');
            btnFiltros.setAttribute('aria-expanded', 'false');
            filtersPanel.setAttribute('aria-hidden', 'true');
        }
    });

    // 4. Botón "Restablecer": activa todos los filtros y los aplica
    btnResetFiltros.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = true);
        // Envía el formulario automáticamente para aplicar los cambios
        formFiltros.submit();
    });

    // 5. Prevenir envío si ningún filtro está activo
    formFiltros.addEventListener('submit', (e) => {
        const algunoActivo = Array.from(checkboxes).some(cb => cb.checked);
        if (!algunoActivo) {
            e.preventDefault();
            alert('Debes seleccionar al menos un campo donde buscar.');
        }
        // Si hay al menos uno activo, el formulario se envía normalmente
    });
});