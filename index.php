<?php
require __DIR__ . '/auth/sesion.php';
require __DIR__ . '/data/conexion.php';

// ⭐ Capturar término de búsqueda
$busqueda = trim($_GET['busqueda'] ?? '');

// ⭐ Capturar filtros de búsqueda con persistencia
// Si 'filtros_activos' está en GET, significa que el usuario ya personalizó los filtros
// Si no está, usamos los valores por defecto (todos activos)
if (isset($_GET['filtros_activos'])) {
    // Leer el valor REAL del parámetro ("0" o "1"), no solo su existencia
    $f_titulo      = (isset($_GET['f_titulo'])      && $_GET['f_titulo']      == '1') ? 1 : 0;
    $f_categoria   = (isset($_GET['f_categoria'])   && $_GET['f_categoria']   == '1') ? 1 : 0;
    $f_descripcion = (isset($_GET['f_descripcion']) && $_GET['f_descripcion'] == '1') ? 1 : 0;
} else {
    // Primera carga: todos activos por defecto
    $f_titulo      = 1;
    $f_categoria   = 1;
    $f_descripcion = 1;
}
// ⭐ Consulta de categorías (para el modal de edición)
$categorias = [];
$resultCat = $mysqli->query("SELECT id, nombre FROM categoria ORDER BY nombre ASC");
if ($resultCat) {
    while ($row = $resultCat->fetch_assoc()) {
        $categorias[] = $row;
    }
}

// ⭐ Consulta de eventos con búsqueda dinámica según filtros activos
$sql = 'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion, e.categoria_id,
               c.nombre AS categoria_nombre 
          FROM eventos e 
          LEFT JOIN categoria c ON c.id = e.categoria_id 
         WHERE e.usuario_id = ?';

$params = [$usuarioId];
$types = 'i';
$condiciones = [];

if ($busqueda !== '') {
    $searchTerm = '%' . $busqueda . '%';
    
    // Construir condiciones OR solo para los campos seleccionados
    if ($f_titulo)      $condiciones[] = 'e.titulo LIKE ?';
    if ($f_categoria)   $condiciones[] = 'c.nombre LIKE ?';
    if ($f_descripcion) $condiciones[] = 'e.descripcion LIKE ?';
    
    if (!empty($condiciones)) {
        $sql .= ' AND (' . implode(' OR ', $condiciones) . ')';
        
        // Agregar parámetros según los filtros activos
        if ($f_titulo)      { $params[] = $searchTerm; $types .= 's'; }
        if ($f_categoria)   { $params[] = $searchTerm; $types .= 's'; }
        if ($f_descripcion) { $params[] = $searchTerm; $types .= 's'; }
    } else {
        // Si no hay ningún filtro activo, no mostrar nada (o podrías forzar vacío)
        $sql .= ' AND 1 = 0';
    }
}

$sql .= ' ORDER BY e.fecha ASC, e.hora ASC';

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$eventos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Eventos · AgendaWeb</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body class="layout">

  <header class="site-header">
    <div class="contenedor site-header__inner">
        <a href="index.php" class="logo">
        <img src="image/logoPaginaWeb.png" alt="Logotipo formal de la pagina web">Agenda<span>Web</span>
        </a>
        <nav class="nav">
        <a href="index.php" class="nav__link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'is-active' : '' ?>">Mis eventos</a>
        <a href="registrar.php" class="nav__link <?= basename($_SERVER['PHP_SELF']) === 'registrar.php' ? 'is-active' : '' ?>">Nuevo evento</a>
        
        <!-- ⭐ MENÚ DE USUARIO ESTANDARIZADO -->
        <div class="user-menu">
            <button type="button" class="user-menu__trigger" aria-label="Menú de usuario" aria-expanded="false">
            <?= mb_strtoupper(mb_substr($usuarioNombre, 0, 1)) ?>
            </button>
            
            <div class="user-menu__panel" aria-hidden="true">
            <!-- Cabecera con datos del usuario -->
            <div class="user-menu__header">
                <div class="user-menu__avatar">
                <?= mb_strtoupper(mb_substr($usuarioNombre, 0, 1)) ?>
                </div>
                <div class="user-menu__info">
                <div class="user-menu__name"><?= e($usuarioNombre) ?></div>
                <div class="user-menu__email"><?= e($usuarioCorreo) ?></div>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="user-menu__actions">
                <a href="auth/logout.php" class="user-menu__action">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Cerrar sesión</span>
                </a>
                
                <div class="user-menu__divider"></div>
                
                <button type="button" class="user-menu__action user-menu__action--danger" id="btnEliminarCuenta">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
                <span>Eliminar cuenta</span>
                </button>
            </div>
            </div>
        </div>
        </nav>
    </div>
    </header>

  <main class="contenedor main-content">
    
    <!-- Mensajes de estado -->
    <?php if (isset($_GET['ok']) && $_GET['ok'] == 1): ?>
        <div class="alert alert--ok" role="status"><span>✅</span> Evento guardado exitosamente.</div>
    <?php elseif (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert alert--ok" role="status"><span>✅</span> Evento actualizado correctamente.</div>
    <?php elseif (isset($_GET['updated']) && $_GET['updated'] == 0): ?>
        <div class="alert alert--notOk" role="status"><span>⚠️</span> No se pudo actualizar el evento.</div>
    <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert--ok" role="status"><span>🗑️</span> Evento eliminado correctamente.</div>
    <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] == 0): ?>
        <div class="alert alert--notOk" role="status"><span>⚠️</span> No se pudo eliminar el evento.</div>
    <?php elseif (isset($_GET['ok']) && $_GET['ok'] == 0): ?>
        <div class="alert alert--notOk" role="status"><span>⚠️</span> No se pudo guardar el evento.</div>
    <?php endif; ?>

    <!-- Encabezado con Búsqueda, Filtros y Botón -->
    <div class="page__header">
      <div class="page__titles">
        <h1 class="page__title">Mis eventos</h1>
        <p class="page__subtitle">
            <?= $busqueda !== '' ? 'Resultados de la búsqueda' : count($eventos) . ' eventos registrados en tu agenda' ?>
        </p>
      </div>
      
      <div class="page__actions">
        <!-- Barra de búsqueda + Botón de filtros -->
        <div class="search-wrapper">
          <form method="GET" action="index.php" class="search-form">
            <!-- Preservar los filtros actuales al buscar -->
            <input type="hidden" name="f_titulo" value="<?= $f_titulo ?>">
            <input type="hidden" name="f_categoria" value="<?= $f_categoria ?>">
            <input type="hidden" name="f_descripcion" value="<?= $f_descripcion ?>">
            <!-- ⭐ AVISAR A PHP QUE LOS FILTROS ESTÁN PERSONALIZADOS -->
            <input type="hidden" name="filtros_activos" value="1">
            
            <input type="search" name="busqueda" placeholder="Buscar eventos..." value="<?= htmlspecialchars($busqueda) ?>" aria-label="Buscar eventos">
            <button type="submit" class="btn-search" aria-label="Buscar">🔍</button>
            <?php if ($busqueda !== ''): ?>
              <a href="index.php" class="btn-clear" title="Limpiar búsqueda">✕</a>
            <?php endif; ?>
          </form>
          
          <!-- ⭐ Botón de filtros con indicador visual -->
          <button type="button" class="btn-filters" id="btnFiltros" aria-label="Filtros de búsqueda" aria-expanded="false">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
            </svg>
            <?php 
              // Mostrar punto indicador si los filtros no están en su estado por defecto
              $filtrosPersonalizados = ($f_titulo + $f_categoria + $f_descripcion) !== 3;
              if ($filtrosPersonalizados): 
            ?>
              <span class="filter-indicator"></span>
            <?php endif; ?>
          </button>
          
          <!-- ⭐ Panel desplegable de filtros -->
          <div class="filters-panel" id="filtersPanel" aria-hidden="true">
            <div class="filters-panel__header">
              <span>Buscar en:</span>
              <button type="button" class="filters-reset" id="btnResetFiltros">Restablecer</button>
            </div>
            
            <form method="GET" action="index.php" class="filters-form" id="formFiltros">
                <!-- Preservar la búsqueda actual -->
                <input type="hidden" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>">
                <!-- ⭐ Indicador: avisa a PHP que el usuario personalizó los filtros -->
                <input type="hidden" name="filtros_activos" value="1">
              <label class="filter-toggle">
                <input type="checkbox" name="f_titulo" value="1" <?= $f_titulo ? 'checked' : '' ?>>
                <span class="toggle-switch"></span>
                <span class="toggle-label">Título del evento</span>
              </label>
              
              <label class="filter-toggle">
                <input type="checkbox" name="f_categoria" value="1" <?= $f_categoria ? 'checked' : '' ?>>
                <span class="toggle-switch"></span>
                <span class="toggle-label">Categoría</span>
              </label>
              
              <label class="filter-toggle">
                <input type="checkbox" name="f_descripcion" value="1" <?= $f_descripcion ? 'checked' : '' ?>>
                <span class="toggle-switch"></span>
                <span class="toggle-label">Descripción</span>
              </label>
              
              <button type="submit" class="btn-apply-filters">Aplicar filtros</button>
            </form>
          </div>
        </div>
        
        <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
      </div>
    </div>

    <?php if (!empty($eventos)): ?>
        
        <section class="card-list">
            <?php foreach ($eventos as $evento): ?>
                <?php 
                    $fecha_obj = new DateTime($evento['fecha']);
                    $fecha_visual = $fecha_obj->format('d/m/Y');
                    $hora_visual = !empty($evento['hora']) ? substr($evento['hora'], 0, 5) : 'Todo el día';
                    $datetime_attr = $evento['fecha'] . 'T' . ($evento['hora'] ?? '00:00');
                    
                    $badge_class = 'card__badge';
                    $cat_nombre = $evento['categoria_nombre'] ?? 'Sin categoría';
                    $cat_lower = strtolower($cat_nombre);
                    
                    if ($cat_lower === 'personal') {
                        $badge_class .= ' card__badge--personal';
                    } elseif ($cat_lower === 'importante') {
                        $badge_class .= ' card__badge--importante';
                    }
                ?>

                <article class="card"
                    data-id="<?= $evento['id'] ?>"
                    data-titulo="<?= htmlspecialchars($evento['titulo'], ENT_QUOTES) ?>"
                    data-fecha="<?= htmlspecialchars($evento['fecha']) ?>"
                    data-hora="<?= htmlspecialchars($evento['hora'] ?? '') ?>"
                    data-categoria-id="<?= $evento['categoria_id'] ?>"
                    data-descripcion="<?= htmlspecialchars($evento['descripcion'], ENT_QUOTES) ?>">
                    
                    <div class="card__header">
                        <span class="<?= $badge_class ?>"><?= htmlspecialchars(ucfirst($cat_nombre)) ?></span>
                        <time class="card__time" datetime="<?= $datetime_attr ?>"><?= $fecha_visual ?> · <?= $hora_visual ?></time>
                    </div>
                    <h2 class="card__title"><?= htmlspecialchars($evento['titulo']) ?></h2>
                    <p class="card__text"><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>

                    <div class="card__actions">
                        <button type="button" class="btn-secondary btn-sm btn-editar">Editar</button>
                        <form method="post" action="data/borrar.php" class="form-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento?');">
                            <input type="hidden" name="id" value="<?= $evento['id'] ?>">
                            <button type="submit" class="btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </article>

            <?php endforeach; ?>
        </section>

    <?php else: ?>
        
        <div class="empty-state">
            <div class="empty-state__icon"><?= $busqueda !== '' ? '🔍' : '📅' ?></div>
            <h3 class="empty-state__title">
                <?= $busqueda !== '' ? 'No se encontraron resultados' : 'No hay eventos registrados' ?>
            </h3>
            <p class="empty-state__text">
                <?= $busqueda !== '' ? 'Intenta con otros términos o ajusta los filtros.' : 'Tu agenda está libre por ahora. ¡Aprovecha para organizar tu primer evento!' ?>
            </p>
            <?php if ($busqueda !== ''): ?>
                <a href="index.php" class="btn-primary" style="margin-top: 1.5rem; display: inline-flex;">Ver todos los eventos</a>
            <?php else: ?>
                <a href="registrar.php" class="btn-primary" style="margin-top: 1.5rem; display: inline-flex;">+ Agregar primer evento</a>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <!-- MODAL DE EDICIÓN FLOTANTE -->
    <div class="modal-overlay" id="modalEditar" aria-hidden="true" role="dialog">
        <div class="modal">
            <div class="modal__header">
                <h2 class="modal__title">Editar Evento</h2>
                <button type="button" class="modal__close" id="btnCerrarModal" aria-label="Cerrar">&times;</button>
            </div>
            
            <form id="formEditar" class="formulario-agenda">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="grupo-formulario">
                    <label for="edit_titulo">Título del Evento</label>
                    <input type="text" id="edit_titulo" name="titulo" required maxlength="120">
                </div>

                <div class="fila-doble">
                    <div class="grupo-formulario">
                        <label for="edit_fecha">Fecha</label>
                        <input type="date" id="edit_fecha" name="fecha" required>
                    </div>
                    <div class="grupo-formulario">
                        <label for="edit_hora">Hora</label>
                        <input type="time" id="edit_hora" name="hora" required>
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="edit_categoria">Categoría</label>
                    <select id="edit_categoria" name="categoria_id" required>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars(ucfirst($cat['nombre'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grupo-formulario">
                    <label for="edit_descripcion">Descripción / Notas</label>
                    <textarea id="edit_descripcion" name="descripcion" rows="4"></textarea>
                </div>

                <div class="acciones-formulario">
                    <button type="button" class="btn-secondary" id="btnCancelar">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

  </main>

  <footer class="site-footer">
    <div class="contenedor">
      &copy; 2026 AgendaWeb · Elaborado por <b>Cristian Rodríguez</b>
    </div>
  </footer>

  <script src="js/modal-editar.js"></script>
  <script src="js/filtros-busqueda.js"></script>
  <script src="js/menu-usuario.js"></script>

</body>
</html>