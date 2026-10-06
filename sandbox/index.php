<?php
require 'data/conexion.php';

$sql = "SELECT id, titulo, fecha, hora, categoria, descripcion FROM eventos ORDER BY fecha ASC, hora ASC";
$result = $mysqli->query($sql);

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
      <a href="index.php" class="logo"><img src="image/logoPaginaWeb.png" alt="Logotipo formal de la pagina web">Agenda<span>Web</span></a>
      <nav class="nav">
        <a href="index.php" class="nav__link is-active">Mis eventos</a>
        <a href="registrar.php" class="nav__link">Nuevo evento</a>
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

    <!-- Encabezado -->
    <div class="page__header">
      <div class="page__titles">
        <h1 class="page__title">Mis eventos</h1>
        <p class="page__subtitle"><?= count($eventos) ?> eventos registrados en tu agenda</p>
      </div>
      <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </div>

    <?php if (!empty($eventos)): ?>
        
        <section class="card-list">
            <?php foreach ($eventos as $evento): ?>
                <?php 
                    $fecha_obj = new DateTime($evento['fecha']);
                    $fecha_visual = $fecha_obj->format('d/m/Y');
                    $hora_visual = !empty($evento['hora']) ? substr($evento['hora'], 0, 5) : 'Todo el día';
                    $datetime_attr = $evento['fecha'] . 'T' . $evento['hora'];
                    
                    $badge_class = 'card__badge';
                    if ($evento['categoria'] === 'personal') {
                        $badge_class .= ' card__badge--personal';
                    } elseif ($evento['categoria'] === 'importante') {
                        $badge_class .= ' card__badge--importante';
                    }
                    
                    $categoria_visual = ucfirst($evento['categoria']);
                ?>

                <!-- ⭐ AÑADIMOS data-attributes con los datos del evento -->
                <article class="card"
                    data-id="<?= $evento['id'] ?>"
                    data-titulo="<?= htmlspecialchars($evento['titulo'], ENT_QUOTES) ?>"
                    data-fecha="<?= htmlspecialchars($evento['fecha']) ?>"
                    data-hora="<?= htmlspecialchars($evento['hora']) ?>"
                    data-categoria="<?= htmlspecialchars($evento['categoria']) ?>"
                    data-descripcion="<?= htmlspecialchars($evento['descripcion'], ENT_QUOTES) ?>">
                    
                    <div class="card__header">
                        <span class="<?= $badge_class ?>"><?= $categoria_visual ?></span>
                        <time class="card__time" datetime="<?= $datetime_attr ?>"><?= $fecha_visual ?> · <?= $hora_visual ?></time>
                    </div>
                    <h2 class="card__title"><?= htmlspecialchars($evento['titulo']) ?></h2>
                    <p class="card__text"><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>

                    <div class="card__actions">
                        <!-- ⭐ Cambiamos href por un botón que abre el modal -->
                        <button type="button" class="btn-secondary btn-sm btn-editar">Editar</button>
                        <form method="post" action="borrar.php" class="form-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento?');">
                            <input type="hidden" name="id" value="<?= $evento['id'] ?>">
                            <button type="submit" class="btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </article>

            <?php endforeach; ?>
        </section>

    <?php else: ?>
        
        <div class="empty-state">
            <div class="empty-state__icon">📅</div>
            <h3 class="empty-state__title">No hay eventos registrados</h3>
            <p class="empty-state__text">Tu agenda está libre por ahora. ¡Aprovecha para organizar tu primer evento!</p>
            <a href="registrar.php" class="btn-primary" style="margin-top: 1.5rem; display: inline-flex;">
                + Agregar primer evento
            </a>
        </div>

    <?php endif; ?>

    <!-- ⭐ MODAL DE EDICIÓN FLOTANTE -->
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
                    <select id="edit_categoria" name="categoria" required>
                        <option value="trabajo">Trabajo / Universidad</option>
                        <option value="personal">Personal</option>
                        <option value="importante">Importante</option>
                        <option value="otro">Otro</option>
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

  <!-- ⭐ JAVASCRIPT para manejar el modal -->
  <script src="js/modal-editar.js"></script>

</body>
</html>