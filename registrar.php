<?php
$titulo = '';
$fecha = '';
$hora = '';
$categoria = '';
$descripcion = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo     = trim($_POST['titulo']     ?? '');
  $fecha      = trim($_POST['fecha']      ?? '');
  $hora       = trim($_POST['hora']       ?? '');
  $categoria  = trim($_POST['categoria']  ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');

  // Normalizamos las variables
  $titulo = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
  $fecha = htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8');
  $hora = htmlspecialchars($hora, ENT_QUOTES, 'UTF-8');
  $categoria = htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8');
  $descripcion = htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8');

  $errores = [];
  $categoriasOK = ['trabajo', 'personal', 'importante', 'otro'];

  if ($titulo === '') {
    $errores['titulo'] = 'El título es obligatorio.';
  } elseif (mb_strlen($titulo) > 120) {
    $errores['titulo'] = 'Máximo 120 caracteres.';
  }

  if ($fecha === '') {
    $errores['fecha'] = 'La fecha es obligatoria.';
  } elseif (!DateTime::createFromFormat('Y-m-d', $fecha)) {
    $errores['fecha'] = 'La fecha no es válida.';
  }

  if (!in_array($categoria, $categoriasOK, true)) {
    $errores['categoria'] = 'Elige una categoría válida.';
  }

  if (empty($errores)) {
    require 'data/conexion.php';

    $sql = "INSERT INTO eventos (titulo, fecha, hora, categoria, descripcion)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssss", $titulo, $fecha, $hora, $categoria, $descripcion);
    $stmt->execute(); 
    $nuevoId = $stmt->insert_id; 
    $stmt->close();
    $mysqli->close();
    
    header('Location: index.php?ok=1');
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Evento - AgendaWeb</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600" rel="stylesheet">
    
    <!-- Enlace al archivo CSS unificado -->
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body class="layout">
    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="index.php" class="logo">Agenda<span>Web</span></a>
            <nav class="nav">
                <a href="index.php" class="nav__link">Mis eventos</a>
                <a href="registrar.php" class="nav__link is-active">Nuevo evento</a>
            </nav>
        </div>
    </header>

    <main class="contenedor-principal">
        <div class="tarjeta-formulario">
            <div class="encabezado-formulario">
                <h1>Registrar Nuevo Evento</h1>
                <p>Ingresa los detalles de tu cita o actividad para mantener tu agenda organizada.</p>
            </div>

            <form action="registrar.php" method="POST" class="formulario-agenda">
                
                <div class="grupo-formulario">
                    <label for="titulo">Título del Evento</label>
                    <input type="text" id="titulo" name="titulo" placeholder="Ej. Reunión de equipo, Examen..." required autocomplete="off" value="<?= htmlspecialchars($titulo) ?>">
                </div>

                <div class="fila-doble">
                    <div class="grupo-formulario">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" required value="<?= htmlspecialchars($fecha) ?>">
                    </div>

                    <div class="grupo-formulario">
                        <label for="hora">Hora</label>
                        <input type="time" id="hora" name="hora" required value="<?= htmlspecialchars($hora) ?>">
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria" required>
                        <option value="" disabled <?= empty($categoria) ? 'selected' : '' ?>>Selecciona una categoría</option>
                        <option value="trabajo" <?= $categoria === 'trabajo' ? 'selected' : '' ?>>Trabajo / Universidad</option>
                        <option value="personal" <?= $categoria === 'personal' ? 'selected' : '' ?>>Personal</option>
                        <option value="importante" <?= $categoria === 'importante' ? 'selected' : '' ?>>Importante</option>
                        <option value="otro" <?= $categoria === 'otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>

                <div class="grupo-formulario">
                    <label for="descripcion">Descripción / Notas</label>
                    <textarea id="descripcion" name="descripcion" rows="4" placeholder="Agrega detalles o recordatorios importantes..." spellcheck="true"><?= htmlspecialchars($descripcion) ?></textarea>
                </div>

                <div class="acciones-formulario">
                    <button type="reset" class="btn-secondary">Limpiar</button>
                    <button type="submit" class="btn-primary">Guardar Evento</button>
                </div>

            </form>
        </div>
    </main>

    <footer class="site-footer">
        <div class="contenedor">
            &copy; 2026 AgendaWeb · Elaborado por <b>Cristian Rodríguez</b>
        </div>
    </footer>
</body>
</html>