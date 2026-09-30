<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaWeb</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600" rel="stylesheet">
    
    <!-- Enlace a tu archivo CSS externo -->
    <link rel="stylesheet" href="styles/styles-generales.css">
</head>
<body class="layout">
  <header class="site-header">
    <div class="contenedor site-header__inner">
      <a href="index.php" class="logo">Agenda<span>Web</span></a>
      <nav class="nav">
        <a href="index.php" class="nav__link is-active">Mis eventos</a>
        <a href="formulario.html" class="nav__link">Nuevo evento</a>
      </nav>
    </div>
  </header>

  <main class="contenedor">
    <!-- pasos 3 a 6 van aquí -->
     <div class="alert alert--ok" role="status">&#9989; Evento guardado.</div>

    <div class="page__header">
    <div>
        <h1 class="page__title">Mis eventos</h1>
        <p class="page__subtitle">3 eventos registrados</p>
    </div>
    <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </div>
    <section class="card-list">

    <!-- ▼ INICIO de UN evento (en P4 se repetirá con foreach) -->
    <article class="card">
        <span class="card__badge">Trabajo</span>
        <h2 class="card__title">Reunión de academia</h2>
        <p class="card__meta">
        <time datetime="2026-09-25T10:30">25/09/2026 · 10:30</time>
        </p>
        <p class="card__text">Revisar calificaciones del 1er parcial.</p>

        <div class="card__actions">
        <a href="editar.php?id=1" class="btn-secondary btn-sm">Editar</a>
        <form method="post" action="borrar.php" class="form-inline">
            <input type="hidden" name="id" value="1">
            <button type="submit" class="btn-danger btn-sm">Borrar</button>
        </form>
        </div>
    </article>
    <!-- ▲ FIN de un evento -->

  </main>

  
  <footer class="site-footer">
    <div class="contenedor"> &copy; AgendaWeb · Cristian Rodriguez Rodriguez · 2026</div>
  </footer>
</body>
</html>