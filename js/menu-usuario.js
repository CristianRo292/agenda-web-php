// ==========================================
// Menú desplegable de usuario en el header
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    const triggers = document.querySelectorAll('.user-menu__trigger');
    
    triggers.forEach(trigger => {
        const menu = trigger.closest('.user-menu');
        const panel = menu.querySelector('.user-menu__panel');

        // 1. Abrir/cerrar al hacer clic en el avatar
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = panel.classList.toggle('is-open');
            trigger.classList.toggle('is-active', isOpen);
            trigger.setAttribute('aria-expanded', isOpen);
            panel.setAttribute('aria-hidden', !isOpen);
        });

        // 2. Cerrar al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target)) {
                panel.classList.remove('is-open');
                trigger.classList.remove('is-active');
                trigger.setAttribute('aria-expanded', 'false');
                panel.setAttribute('aria-hidden', 'true');
            }
        });

        // 3. Cerrar con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && panel.classList.contains('is-open')) {
                panel.classList.remove('is-open');
                trigger.classList.remove('is-active');
                trigger.setAttribute('aria-expanded', 'false');
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    });
});

// ==========================================
// Confirmación para eliminar cuenta
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const btnEliminar = document.getElementById('btnEliminarCuenta');
    
    if (btnEliminar) {
        btnEliminar.addEventListener('click', () => {
            const confirmacion = prompt(
                '⚠️ Esta acción es IRREVERSIBLE.\n\n' +
                'Se eliminarán TODOS tus eventos y tu cuenta permanentemente.\n\n' +
                'Escribe "SI" en mayúsculas para confirmar:'
            );
            
            if (confirmacion === 'SI') {
                // Crear formulario dinámico y enviarlo
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'auth/eliminar-cuenta.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'confirmar';
                input.value = 'SI';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});