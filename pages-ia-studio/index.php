<?php
/**
 * index.php
 * Dashboard principal de AgendaWeb con búsqueda avanzada, switches estilo iOS,
 * tarjetas Glassmorphism, modal flotante de edición y menú de usuario.
 */

declare(strict_types=1);

require_once __DIR__ . '/data/conexion.php';
require_once __DIR__ . '/auth/sesion.php';

$usuario = verificarSesion($conn, false, 'bienvenida.php');
$usuarioId = (int) $usuario['id'];
$inicialUsuario = mb_strtoupper(mb_substr($usuario['nombre'], 0, 1, 'UTF-8'), 'UTF-8');

// Cargar catálogo de categorías
$categorias = [];
$resCategorias = $conn->query('SELECT id, nombre FROM categoria ORDER BY id ASC');
while ($cat = $resCategorias->fetch_assoc()) {
    $categorias[] = $cat;
}
$resCategorias->free();

// Parámetros de búsqueda y filtros estilo iOS
$q = trim((string) ($_GET['q'] ?? ''));
$categoriaFiltro = isset($_GET['categoria']) ? (int) $_GET['categoria'] : 0;

$filtrosEnviados = isset($_GET['filtrar']) || isset($_GET['q']) || isset($_GET['categoria']);
$buscarTitulo = $filtrosEnviados ? isset($_GET['en_titulo']) : true;
$buscarCategoria = $filtrosEnviados ? isset($_GET['en_categoria']) : true;
$buscarDescripcion = $filtrosEnviados ? isset($_GET['en_descripcion']) : true;

if ($q !== '' && !$buscarTitulo && !$buscarCategoria && !$buscarDescripcion) {
    $buscarTitulo = true;
}

// Consulta preparada siempre acotada por WHERE e.usuario_id = ?
$sql = 'SELECT
            e.id,
            e.titulo,
            e.fecha,
            DATE_FORMAT(e.hora, "%H:%i") AS hora,
            e.categoria_id,
            c.nombre AS categoria_nombre,
            e.descripcion
        FROM eventos e
        INNER JOIN categoria c ON c.id = e.categoria_id
        WHERE e.usuario_id = ?';

$tipos = 'i';
$parametros = [$usuarioId];

if ($categoriaFiltro > 0) {
    $sql .= ' AND e.categoria_id = ?';
    $tipos .= 'i';
    $parametros[] = $categoriaFiltro;
}

if ($q !== '') {
    $like = '%' . $q . '%';
    $condiciones = [];

    if ($buscarTitulo) {
        $condiciones[] = 'e.titulo LIKE ?';
        $tipos .= 's';
        $parametros[] = $like;
    }
    if ($buscarCategoria) {
        $condiciones[] = 'c.nombre LIKE ?';
        $tipos .= 's';
        $parametros[] = $like;
    }
    if ($buscarDescripcion) {
        $condiciones[] = 'COALESCE(e.descripcion, "") LIKE ?';
        $tipos .= 's';
        $parametros[] = $like;
    }

    if (!empty($condiciones)) {
        $sql .= ' AND (' . implode(' OR ', $condiciones) . ')';
    }
}

$sql .= ' ORDER BY e.fecha ASC, e.hora ASC, e.id DESC';

$stmt = $conn->prepare($sql);
$stmt->bind_param($tipos, ...$parametros);
$stmt->execute();
$resultado = $stmt->get_result();

$eventos = [];
while ($fila = $resultado->fetch_assoc()) {
    $eventos[] = $fila;
}
$stmt->close();

$csrfToken = obtenerCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= escapar($csrfToken) ?>">
  <title>Mis eventos — AgendaWeb</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
  <div class="app-shell">
    <!-- Header Flotante Glassmorphism -->
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
        <a href="index.php" class="nav-link is-active">Mis eventos</a>
        <a href="explorar.php" class="nav-link">Explorar</a>
        <a href="registrar.php" class="nav-link">Nuevo evento</a>
      </nav>

      <div class="user-menu-wrapper">
        <button
          type="button"
          class="user-avatar-btn"
          id="user-menu-btn"
          aria-expanded="false"
          aria-controls="user-dropdown"
          aria-label="Menú de usuario"
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
            <a href="auth/logout.php" class="btn btn-secondary btn-sm btn-block" role="menuitem">
              Cerrar sesión
            </a>
            <form
              method="POST"
              action="data/borrar.php"
              data-confirm-title="Eliminar cuenta"
              data-confirm-message="Esta acción eliminará tu cuenta y todos tus eventos de forma permanente. ¿Deseas continuar?"
            >
              <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
              <input type="hidden" name="accion" value="eliminar_cuenta">
              <button type="submit" class="btn btn-danger btn-sm btn-block" role="menuitem">
                Eliminar cuenta
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <main>
      <div class="page-hero">
        <div>
          <h1 class="page-title">Tus eventos</h1>
          <p class="page-subtitle">Gestiona tu agenda personal con tranquilidad y precisión.</p>
        </div>
        <a href="registrar.php" class="btn btn-primary">
          + Nuevo evento
        </a>
      </div>

      <?php if (isset($_GET['creado'])): ?>
        <div class="alert alert-success" role="status">Evento registrado correctamente.</div>
      <?php elseif (isset($_GET['eliminado'])): ?>
        <div class="alert alert-success" role="status">Evento eliminado de tu agenda.</div>
      <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-error" role="alert">No se pudo completar la operación solicitada.</div>
      <?php endif; ?>

      <!-- Barra de Búsqueda Avanzada con Filtros Desplegables -->
      <form class="glass-panel search-toolbar" method="GET" action="index.php">
        <input type="hidden" name="filtrar" value="1">
        <div class="search-bar-row">
          <div class="search-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              class="form-input"
              type="search"
              name="q"
              value="<?= escapar($q) ?>"
              placeholder="Buscar eventos por título, categoría o descripción..."
              aria-label="Buscar eventos"
            >
          </div>

          <select class="form-select" name="categoria" aria-label="Filtrar por categoría" style="width: auto; min-width: 175px; border-radius: 999px;">
            <option value="0">Todas las categorías</option>
            <?php foreach ($categorias as $cat): ?>
              <option
                value="<?= (int) $cat['id'] ?>"
                <?= $categoriaFiltro === (int) $cat['id'] ? 'selected' : '' ?>
              >
                <?= escapar($cat['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <button
            type="button"
            class="btn btn-secondary"
            id="toggle-filters-btn"
            aria-expanded="false"
            aria-controls="filters-drawer"
          >
            Filtros
          </button>

          <button type="submit" class="btn btn-primary">
            Buscar
          </button>
        </div>

        <div class="filters-drawer" id="filters-drawer">
          <label class="ios-switch-row">
            <span>Buscar en Título</span>
            <span class="ios-switch">
              <input type="checkbox" name="en_titulo" value="1" <?= $buscarTitulo ? 'checked' : '' ?>>
              <span class="ios-switch-slider"></span>
            </span>
          </label>

          <label class="ios-switch-row">
            <span>Buscar en Categoría</span>
            <span class="ios-switch">
              <input type="checkbox" name="en_categoria" value="1" <?= $buscarCategoria ? 'checked' : '' ?>>
              <span class="ios-switch-slider"></span>
            </span>
          </label>

          <label class="ios-switch-row">
            <span>Buscar en Descripción</span>
            <span class="ios-switch">
              <input type="checkbox" name="en_descripcion" value="1" <?= $buscarDescripcion ? 'checked' : '' ?>>
              <span class="ios-switch-slider"></span>
            </span>
          </label>
        </div>
      </form>

      <!-- Listado de Tarjetas o Estado Vacío -->
      <?php if (empty($eventos)): ?>
        <section class="glass-panel empty-state">
          <div class="empty-state-icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="4"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <h3>No hay eventos para mostrar</h3>
          <p>
            <?php if ($q !== '' || $categoriaFiltro > 0): ?>
              Prueba ajustando tu búsqueda o limpiando los filtros activos para ver toda tu agenda.
            <?php else: ?>
              Tu agenda está despejada. Crea tu primer evento para comenzar a organizar tu semana.
            <?php endif; ?>
          </p>
          <a href="registrar.php" class="btn btn-primary">Registrar primer evento</a>
        </section>
      <?php else: ?>
        <section class="events-grid" aria-label="Lista de eventos">
          <?php foreach ($eventos as $ev): ?>
            <article class="event-card" data-event-card="<?= (int) $ev['id'] ?>">
              <div>
                <div class="event-card-top">
                  <div>
                    <h2 class="event-title" data-card-titulo><?= escapar($ev['titulo']) ?></h2>
                    <p class="event-datetime" data-card-datetime>
                      <?= escapar($ev['fecha']) ?> · <?= escapar($ev['hora']) ?>
                    </p>
                  </div>
                  <span
                    class="category-badge <?= escapar(claseBadgeCategoria($ev['categoria_nombre'])) ?>"
                    data-card-categoria
                  >
                    <?= escapar($ev['categoria_nombre']) ?>
                  </span>
                </div>

                <p class="event-description" data-card-descripcion style="margin-top: 0.85rem;">
                  <?= escapar($ev['descripcion'] !== '' && $ev['descripcion'] !== null ? $ev['descripcion'] : 'Sin descripción adicional.') ?>
                </p>
              </div>

              <div class="event-actions">
                <button
                  type="button"
                  class="btn btn-secondary btn-sm"
                  data-edit-event
                  data-id="<?= (int) $ev['id'] ?>"
                  data-titulo="<?= escapar($ev['titulo']) ?>"
                  data-fecha="<?= escapar($ev['fecha']) ?>"
                  data-hora="<?= escapar($ev['hora']) ?>"
                  data-categoria-id="<?= (int) $ev['categoria_id'] ?>"
                  data-descripcion="<?= escapar((string) ($ev['descripcion'] ?? '')) ?>"
                >
                  Editar
                </button>

                <form
                  method="POST"
                  action="data/borrar.php"
                  data-confirm-title="Eliminar evento"
                  data-confirm-message="¿Estás seguro de que deseas eliminar este evento de tu agenda?"
                >
                  <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
                  <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
    </main>
  </div>

  <!-- Modal Flotante de Edición -->
  <div class="modal-backdrop" id="edit-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
    <div class="modal-dialog">
      <div class="modal-header">
        <h2 id="edit-modal-title" style="font-size: 1.35rem;">Editar evento</h2>
        <button type="button" class="modal-close" id="edit-modal-close" aria-label="Cerrar modal">&times;</button>
      </div>

      <div class="alert alert-error" id="edit-modal-error" hidden role="alert"></div>

      <form id="edit-event-form">
        <input type="hidden" id="edit-id" name="id">

        <div class="form-group">
          <label class="form-label" for="edit-titulo">Título</label>
          <input class="form-input" type="text" id="edit-titulo" name="titulo" maxlength="150" required>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label" for="edit-fecha">Fecha</label>
            <input class="form-input" type="date" id="edit-fecha" name="fecha" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-hora">Hora</label>
            <input class="form-input" type="time" id="edit-hora" name="hora" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit-categoria">Categoría</label>
          <select class="form-select" id="edit-categoria" name="categoria_id" required>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>"><?= escapar($cat['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit-descripcion">Descripción</label>
          <textarea class="form-textarea" id="edit-descripcion" name="descripcion" placeholder="Detalles opcionales del evento..."></textarea>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="edit-modal-cancel">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="edit-modal-submit">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de Confirmación Accesible -->
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

  <div class="toast-banner" id="toast-banner" role="status" aria-live="polite"></div>

  <script src="js/menu-usuario.js"></script>
  <script src="js/modal-editar.js"></script>
  <script>
    (function () {
      const btn = document.getElementById('toggle-filters-btn');
      const drawer = document.getElementById('filters-drawer');
      if (!btn || !drawer) return;

      btn.addEventListener('click', function () {
        const abierto = drawer.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      });
    })();
  </script>
</body>
</html>
