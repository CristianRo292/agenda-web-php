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
      <a href="index.php" class="logo">Agenda<span>Web</span></a>
      <nav class="nav">
        <a href="index.php" class="nav__link is-active">Mis eventos</a>
        <a href="registrar.php" class="nav__link">Nuevo evento</a>
      </nav>
    </div>
  </header>

  <main class="contenedor main-content">
    
    <!-- Mensaje de estado (aparece tras guardar) -->
    <?php
    if (isset($_GET['ok']) && $_GET['ok'] == 1) {
        echo "<div class=\"alert alert--ok\" role=\"status\">\n <span>✅</span> Evento guardado exitosamente.\n </div>\n";
    }
    ?>

    <!-- Encabezado de la página -->
    <div class="page__header">
      <div class="page__titles">
        <h1 class="page__title">Mis eventos</h1>
        <p class="page__subtitle">3 eventos registrados en tu agenda</p>
      </div>
      <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </div>

    <!-- Lista de tarjetas de eventos -->
    <section class="card-list">

      <!-- EVENTO 1 -->
      <article class="card">
        <div class="card__header">
          <span class="card__badge">Trabajo</span>
          <time class="card__time" datetime="2026-09-25T10:30">25/09/2026 · 10:30</time>
        </div>
        <h2 class="card__title">Reunión de academia</h2>
        <p class="card__text">Revisar calificaciones del 1er parcial y planear actividades del siguiente mes.</p>

        <div class="card__actions">
          <a href="editar.php?id=1" class="btn-secondary btn-sm">Editar</a>
          <form method="post" action="borrar.php" class="form-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento?');">
            <input type="hidden" name="id" value="1">
            <button type="submit" class="btn-danger btn-sm">Eliminar</button>
          </form>
        </div>
      </article>

      <!-- EVENTO 2 -->
      <article class="card">
        <div class="card__header">
          <span class="card__badge card__badge--personal">Personal</span>
          <time class="card__time" datetime="2026-09-28T16:00">28/09/2026 · 16:00</time>
        </div>
        <h2 class="card__title">Cita con el dentista</h2>
        <p class="card__text">Limpieza dental semestral en la Clínica Sonríe. Llevar credencial.</p>

        <div class="card__actions">
          <a href="editar.php?id=2" class="btn-secondary btn-sm">Editar</a>
          <form method="post" action="borrar.php" class="form-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento?');">
            <input type="hidden" name="id" value="2">
            <button type="submit" class="btn-danger btn-sm">Eliminar</button>
          </form>
        </div>
      </article>

      <!-- EVENTO 3 -->
      <article class="card">
        <div class="card__header">
          <span class="card__badge card__badge--importante">Importante</span>
          <time class="card__time" datetime="2026-10-02T09:00">02/10/2026 · 09:00</time>
        </div>
        <h2 class="card__title">Entrega de proyecto final</h2>
        <p class="card__text">Subir el repositorio de GitHub y presentar la demo de la aplicación web.</p>

        <div class="card__actions">
          <a href="editar.php?id=3" class="btn-secondary btn-sm">Editar</a>
          <form method="post" action="borrar.php" class="form-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento?');">
            <input type="hidden" name="id" value="3">
            <button type="submit" class="btn-danger btn-sm">Eliminar</button>
          </form>
        </div>
      </article>

    </section>
  </main>

  <footer class="site-footer">
    <div class="contenedor">
      &copy; 2026 AgendaWeb · Elaborado por <b>Cristian Rodríguez</b>
    </div>
  </footer>

</body>
</html>