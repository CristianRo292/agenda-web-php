<?php
/**
 * explorar.php
 * Vista SPA ligera que inicia vacía y consume api.php vía Fetch (js/explorar.js)
 * con búsqueda en tiempo real y debounce.
 */

declare(strict_types=1);

require_once __DIR__ . '/data/conexion.php';
require_once __DIR__ . '/auth/sesion.php';

$usuario = verificarSesion($conn, false, 'auth/login.php');
$inicialUsuario = mb_strtoupper(mb_substr($usuario['nombre'], 0, 1, 'UTF-8'), 'UTF-8');

$categorias = [];
$resCat = $conn->query('SELECT id, nombre FROM categoria ORDER BY id ASC');
while ($fila = $resCat->fetch_assoc()) {
    $categorias[] = $fila;
}
$resCat->free();

$csrfToken = obtenerCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Explorar eventos — AgendaWeb</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
  <div class="app-shell">
    <header class="floating-header">
      <a href="index.php" class="brand-link">
        <span class="brand-mark" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="4"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </span>
        <span>AgendaWeb</span>
      </a>

      <nav class="nav-links" aria-label="Navegación principal">
        <a href="index.php" class="nav-link">Mis eventos</a>
        <a href="explorar.php" class="nav-link is-active">Explorar</a>
        <a href="registrar.php" class="nav-link">Nuevo evento</a>
      </nav>

      <div class="user-menu-wrapper">
        <button
          type="button"
          class="user-avatar-btn"
          id="user-menu-btn"
          aria-expanded="false"
          aria-controls="user-dropdown"
        >
          <span class="avatar-circle" aria-hidden="true"><?= escapar($inicialUsuario) ?></span>
          <span><?= escapar($usuario['nombre']) ?></span>
        </button>

        <div class="user-dropdown" id="user-dropdown" role="menu">
          <div class="user-dropdown-info">
            <div class="user-dropdown-name"><?= escapar($usuario['nombre']) ?></div>
            <div class="user-dropdown-email"><?= escapar($usuario['correo']) ?></div>
          </div>
          <div class="user-dropdown-actions">
            <a href="auth/logout.php" class="btn btn-secondary btn-sm btn-block" role="menuitem">Cerrar sesión</a>
            <form
              method="POST"
              action="data/borrar.php"
              data-confirm-title="Eliminar cuenta"
              data-confirm-message="Esta acción eliminará tu cuenta y todos tus eventos permanentemente."
            >
              <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
              <input type="hidden" name="accion" value="eliminar_cuenta">
              <button type="submit" class="btn btn-danger btn-sm btn-block" role="menuitem">Eliminar cuenta</button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <main>
      <div class="page-hero">
        <div>
          <h1 class="page-title">Explorar en tiempo real</h1>
          <p class="page-subtitle">Consulta tus eventos dinámicamente mediante la API JSON sin recargar la página.</p>
        </div>
        <a href="registrar.php" class="btn btn-primary">+ Nuevo evento</a>
      </div>

      <section class="glass-panel search-toolbar" aria-label="Filtros dinámicos">
        <div class="search-bar-row">
          <div class="search-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              class="form-input"
              type="search"
              id="explore-search"
              placeholder="Escribe para filtrar instantáneamente..."
              aria-label="Buscar en tiempo real"
            >
          </div>

          <select class="form-select" id="explore-category" aria-label="Filtrar por categoría" style="width: auto; min-width: 190px; border-radius: 999px;">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>"><?= escapar($cat['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </section>

      <div class="explore-meta">
        <span id="explore-count">Cargando...</span>
      </div>

      <div id="explore-status" class="glass-panel empty-state" hidden></div>

      <!-- Contenedor vacío renderizado dinámicamente desde js/explorar.js -->
      <section class="events-grid" id="explore-results" aria-live="polite"></section>
    </main>
  </div>

  <div class="modal-backdrop" id="confirm-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
    <div class="modal-dialog" style="max-width: 420px;">
      <div class="modal-header">
        <h2 id="confirm-modal-title" style="font-size: 1.2rem;">Confirmar acción</h2>
      </div>
      <p id="confirm-modal-message" style="color: var(--text-secondary); font-size: 0.95rem;"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="confirm-modal-cancel">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirm-modal-accept">Confirmar</button>
      </div>
    </div>
  </div>

  <script src="js/menu-usuario.js"></script>
  <script src="js/explorar.js"></script>
</body>
</html>
