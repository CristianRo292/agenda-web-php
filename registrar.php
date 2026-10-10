<?php
require 'data/conexion.php';

// ⭐ Cargar categorías dinámicamente desde la BD
$categorias = [];
$resultCat = $mysqli->query("SELECT id, nombre FROM categoria ORDER BY nombre ASC");
if ($resultCat) {
    while ($row = $resultCat->fetch_assoc()) {
        $categorias[] = $row;
    }
}

$titulo = '';
$fecha = '';
$hora = '';
$categoria_id = '';
$descripcion = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo       = trim($_POST['titulo']       ?? '');
    $fecha        = trim($_POST['fecha']        ?? '');
    $hora         = trim($_POST['hora']         ?? '');
    $categoria_id = trim($_POST['categoria_id'] ?? '');
    $descripcion  = trim($_POST['descripcion']  ?? '');

    // Validaciones
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

    // ⭐ Validar que categoria_id sea un entero válido y exista en la BD
    $categoria_id_int = filter_var($categoria_id, FILTER_VALIDATE_INT);
    if (!$categoria_id_int) {
        $errores['categoria'] = 'Debes seleccionar una categoría válida.';
    } else {
        $stmtCat = $mysqli->prepare("SELECT id FROM categoria WHERE id = ?");
        $stmtCat->bind_param("i", $categoria_id_int);
        $stmtCat->execute();
        $stmtCat->store_result();
        if ($stmtCat->num_rows === 0) {
            $errores['categoria'] = 'La categoría seleccionada no existe.';
        }
        $stmtCat->close();
    }

    if (empty($errores)) {
        $sql = "INSERT INTO eventos (titulo, fecha, hora, categoria_id, descripcion)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssis", $titulo, $fecha, $hora, $categoria_id_int, $descripcion);
        
        if ($stmt->execute()) {
            $stmt->close();
            $mysqli->close();
            header('Location: index.php?ok=1');
            exit;
        } else {
            $errores['general'] = 'Error al guardar en la base de datos.';
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Evento - AgendaWeb</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600" rel="stylesheet">
    
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

            <?php if (!empty($errores['general'])): ?>
                <div class="alert alert--notOk" role="status">
                    <span>⚠️</span> <?= htmlspecialchars($errores['general']) ?>
                </div>
            <?php endif; ?>

            <form action="registrar.php" method="POST" class="formulario-agenda">
                
                <div class="grupo-formulario">
                    <label for="titulo">Título del Evento</label>
                    <input type="text" id="titulo" name="titulo" placeholder="Ej. Reunión de equipo, Examen..." 
                           required autocomplete="off" maxlength="120"
                           value="<?= htmlspecialchars($titulo) ?>">
                    <?php if (!empty($errores['titulo'])): ?>
                        <small style="color: var(--color-peligro);"><?= htmlspecialchars($errores['titulo']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="fila-doble">
                    <div class="grupo-formulario">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" required value="<?= htmlspecialchars($fecha) ?>">
                        <?php if (!empty($errores['fecha'])): ?>
                            <small style="color: var(--color-peligro);"><?= htmlspecialchars($errores['fecha']) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="grupo-formulario">
                        <label for="hora">Hora</label>
                        <input type="time" id="hora" name="hora" required value="<?= htmlspecialchars($hora) ?>">
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria_id" required>
                        <option value="" disabled <?= empty($categoria_id) ? 'selected' : '' ?>>Selecciona una categoría</option>
                        <!-- ⭐ Opciones generadas dinámicamente desde la BD -->
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($cat['nombre'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errores['categoria'])): ?>
                        <small style="color: var(--color-peligro);"><?= htmlspecialchars($errores['categoria']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="grupo-formulario">
                    <label for="descripcion">Descripción / Notas</label>
                    <textarea id="descripcion" name="descripcion" rows="4" 
                              placeholder="Agrega detalles o recordatorios importantes..." 
                              spellcheck="true"><?= htmlspecialchars($descripcion) ?></textarea>
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