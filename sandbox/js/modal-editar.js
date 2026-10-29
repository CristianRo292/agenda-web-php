// ==========================================
// Modal de Edición de Eventos
// ==========================================

// Espera a que todo el HTML de la página esté completamente cargado antes de ejecutar el script
document.addEventListener('DOMContentLoaded', () => {
    
    // Selecciona los elementos principales del DOM relacionados con el modal y el formulario
    const modal = document.getElementById('modalEditar');         // Contenedor principal del modal
    const btnCerrar = document.getElementById('btnCerrarModal');   // Botón 'X' para cerrar
    const btnCancelar = document.getElementById('btnCancelar');     // Botón 'Cancelar'
    const formEditar = document.getElementById('formEditar');       // El formulario dentro del modal
    const botonesEditar = document.querySelectorAll('.btn-editar'); // Todos los botones de editar de las tarjetas

    // Agrupa los campos de entrada de texto del formulario en un objeto para acceder a ellos fácilmente
    const campos = {
        id: document.getElementById('edit_id'),
        titulo: document.getElementById('edit_titulo'),
        fecha: document.getElementById('edit_fecha'),
        hora: document.getElementById('edit_hora'),
        categoria: document.getElementById('edit_categoria'),
        descripcion: document.getElementById('edit_descripcion')
    };

    // ==========================================
    // 1. ABRIR MODAL Y LLENAR DATOS
    // ==========================================
    // Recorre cada botón de "Editar" encontrado en la página
    botonesEditar.forEach(boton => {
        boton.addEventListener('click', (e) => {
            e.preventDefault(); // Evita el comportamiento por defecto (por si es un enlace <a href="#">)
            
            // Encuentra la tarjeta (.card) contenedora más cercana al botón presionado
            const card = boton.closest('.card');
            
            // Lee los datos almacenados en los atributos data-* de la tarjeta y los asigna a los inputs del formulario
            campos.id.value = card.dataset.id;
            campos.titulo.value = card.dataset.titulo;
            campos.fecha.value = card.dataset.fecha;
            campos.hora.value = card.dataset.hora;
            campos.categoria.value = card.dataset.categoriaId;
            campos.descripcion.value = card.dataset.descripcion;
            
            // Muestra el modal en pantalla llamando a la función auxiliar
            abrirModal();
        });
    });

    // ==========================================
    // 2. CERRAR MODAL (Diferentes métodos)
    // ==========================================
    // Cierra el modal al hacer clic en el botón 'X' o en el botón 'Cancelar'
    btnCerrar.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
    
    // Cierra el modal si el usuario hace clic directamente en el fondo oscuro exterior (backdrop)
    modal.addEventListener('click', (e) => {
        if (e.target === modal) cerrarModal();
    });
    
    // Cierra el modal si se presiona la tecla 'Escape' y el modal se encuentra abierto
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            cerrarModal();
        }
    });

    // ==========================================
    // 3. ENVÍO DE DATOS MEDIANTE FETCH (AJAX)
    // ==========================================
    // Intercepta el evento 'submit' del formulario para enviarlo de forma asíncrona sin recargar la página
    formEditar.addEventListener('submit', async (e) => {
        e.preventDefault(); // Evita la recarga tradicional de la página
        
        // Empaqueta todos los datos actuales del formulario
        const formData = new FormData(formEditar);
        
        // Obtiene el botón de envío y guarda su texto original para restaurarlo después si es necesario
        const botonSubmit = formEditar.querySelector('button[type="submit"]');
        const textoOriginal = botonSubmit.textContent;
        
        // Feedback visual: deshabilita el botón y cambia el texto para indicar que se está procesando
        botonSubmit.disabled = true;
        botonSubmit.textContent = 'Guardando...';
        
        try {
            // Realiza una petición HTTP POST asíncrona enviando los datos al archivo PHP de actualización
            const response = await fetch('../data/actualizar.php', {
                method: 'POST',
                body: formData
            });
            
            // Convierte la respuesta del servidor a un objeto JSON
            const resultado = await response.json();
            
            // Evalúa si la operación fue exitosa según la respuesta del servidor
            if (resultado.success) {
                cerrarModal(); // Cierra el modal
                // Redirige a la página principal agregando un parámetro en la URL para reflejar los cambios
                window.location.href = 'index.php?updated=1';
            } else {
                // Si hay un error lógico controlado, muestra una alerta y restaura el botón
                alert('Error: ' + (resultado.message || 'No se pudo actualizar el evento.'));
                botonSubmit.disabled = false;
                botonSubmit.textContent = textoOriginal;
            }
        } catch (error) {
            // Captura errores de red o excepciones inesperadas durante el fetch
            console.error('Error:', error);
            alert('Error de conexión. Intenta de nuevo.');
            botonSubmit.disabled = false;
            botonSubmit.textContent = textoOriginal;
        }
    });

    // ==========================================
    // 4. FUNCIONES AUXILIARES
    // ==========================================
    
    // Función para mostrar el modal en pantalla
    function abrirModal() {
        modal.classList.add('is-open');            // Añade la clase CSS que hace visible el modal
        modal.setAttribute('aria-hidden', 'false'); // Actualiza el atributo de accesibilidad
        document.body.style.overflow = 'hidden';    // Bloquea el scroll de la página de fondo
        campos.titulo.focus();                      // Coloca el cursor automáticamente en el campo del título
    }

    // Función para ocultar y limpiar el modal
    function cerrarModal() {
        modal.classList.remove('is-open');          // Remueve la clase CSS para ocultar el modal
        modal.setAttribute('aria-hidden', 'true');   // Actualiza el atributo de accesibilidad
        document.body.style.overflow = '';          // Restaura el scroll original de la página
        formEditar.reset();                         // Limpia/resetea los campos del formulario
    }
});
