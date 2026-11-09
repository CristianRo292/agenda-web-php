// ==========================================
// Toggle de visibilidad de contraseña (ícono de ojo)
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    // Buscar todos los inputs de tipo password
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        // Evitar agregar el botón dos veces si ya existe
        if (input.parentElement.classList.contains('password-wrapper') && 
            input.parentElement.querySelector('.btn-toggle-password')) {
            return;
        }
        
        // Crear el botón de toggle
        const btnToggle = document.createElement('button');
        btnToggle.type = 'button';
        btnToggle.className = 'btn-toggle-password';
        btnToggle.setAttribute('aria-label', 'Mostrar contraseña');
        btnToggle.setAttribute('tabindex', '-1'); // No enfocar con Tab
        
        // Ícono SVG de ojo abierto (se muestra cuando la contraseña está OCULTA)
        const iconEye = `
            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;
        
        // Ícono SVG de ojo tachado (se muestra cuando la contraseña es VISIBLE)
        const iconEyeOff = `
            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
        `;
        
        btnToggle.innerHTML = iconEye + iconEyeOff;
        
        // Insertar el botón dentro del wrapper (después del input)
        input.parentNode.insertBefore(btnToggle, input.nextSibling);
        
        // Evento de toggle
        btnToggle.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btnToggle.classList.toggle('is-visible', isPassword);
            btnToggle.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
            
            // Mantener el foco en el input después del toggle
            input.focus();
        });
    });
});