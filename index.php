<?php
// 1. Conexión a la base de datos (ajusta la ruta si es diferente)
require 'data/conexion.php';

// 2. Consulta para obtener los eventos ordenados por fecha y hora
$sql = "SELECT id, titulo, fecha, hora, categoria, descripcion FROM eventos ORDER BY fecha ASC, hora ASC";
$result = $mysqli->query($sql);

$eventos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
}
// Opcional: cerrar la conexión si no se usa en el footer o elsewhere
// $mysqli->close(); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Eventos · AgendaWeb</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Enlace al archivo CSS unificado -->
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
    
    <!-- Mensaje de estado (aparece tras guardar) -->
        <!-- Mensajes de estado (Guardar o Eliminar) -->
    <?php if (isset($_GET['ok']) && $_GET['ok'] == 1): ?>
        <div class="alert alert--ok" role="status">
            <span>✅</span> Evento guardado exitosamente.
        </div>
    <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert--ok" role="status">
            <span>🗑️</span> Evento eliminado correctamente.
        </div>
    <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] == 0): ?>
        <div class="alert alert--notOk" role="status">
            <span>⚠️</span> No se pudo eliminar el evento.
        </div>
    <?php elseif (isset($_GET['ok']) && $_GET['ok'] == 0): ?>
        <div class="alert alert--notOk" role="status">
            <span>⚠️</span> No se pudo guardar el evento.
        </div>
    <?php endif; ?>

    <!-- Encabezado de la página -->
    <div class="page__header">
      <div class="page__titles">
        <h1 class="page__title">Mis eventos</h1>
        <!-- El subtítulo ahora es dinámico según la cantidad de eventos -->
        <p class="page__subtitle"><?= count($eventos) ?> eventos registrados en tu agenda</p>
      </div>
      <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </div>

    <!-- 3. Lógica de visualización: ¿Hay eventos o no? -->
    <?php if (!empty($eventos)): ?>
        
        <section class="card-list">
            <?php foreach ($eventos as $evento): ?>
                <?php 
                    // Formatear fecha y hora para la visualización
                    $fecha_obj = new DateTime($evento['fecha']);
                    $fecha_visual = $fecha_obj->format('d/m/Y');
                    $hora_visual = !empty($evento['hora']) ? substr($evento['hora'], 0, 5) : 'Todo el día';
                    $datetime_attr = $evento['fecha'] . 'T' . $evento['hora'];
                    
                    // Determinar clase del badge según la categoría
                    $badge_class = 'card__badge';
                    if ($evento['categoria'] === 'personal') {
                        $badge_class .= ' card__badge--personal';
                    } elseif ($evento['categoria'] === 'importante') {
                        $badge_class .= ' card__badge--importante';
                    }
                    
                    // Capitalizar la primera letra de la categoría
                    $categoria_visual = ucfirst($evento['categoria']);
                ?>

                <article class="card">
                    <div class="card__header">
                        <span class="<?= $badge_class ?>"><?= htmlspecialchars($categoria_visual) ?></span>
                        <time class="card__time" datetime="<?= $datetime_attr ?>"><?= $fecha_visual ?> · <?= $hora_visual ?></time>
                    </div>
                    <h2 class="card__title"><?= htmlspecialchars($evento['titulo']) ?></h2>
                    <p class="card__text"><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>

                    <div class="card__actions">
                        <a href="data/editar.php?id=<?= $evento['id'] ?>" class="btn-secondary btn-sm">Editar</a>
                        <form method="post" action="data/borrar.php" class="form-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento?');">
                            <input type="hidden" name="id" value="<?= $evento['id'] ?>">
                            <button type="submit" class="btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </article>

            <?php endforeach; ?>
        </section>

    <?php else: ?>
        
        <!-- ESTADO VACÍO: Se muestra cuando no hay eventos en la base de datos -->
        <div class="empty-state">
            <div class="empty-state__icon">📅</div>
            <h3 class="empty-state__title">No hay eventos registrados</h3>
            <p class="empty-state__text">Tu agenda está libre por ahora. ¡Aprovecha para organizar tu primer evento!</p>
            <a href="registrar.php" class="btn-primary" style="margin-top: 1.5rem; display: inline-flex;">
                + Agregar primer evento
            </a>
        </div>

    <?php endif; ?>

  </main>

  <footer class="site-footer">
    <div class="contenedor">
      &copy; 2026 AgendaWeb · Elaborado por <b>Cristian Rodríguez</b>
    </div>
  </footer>

</body>
</html>