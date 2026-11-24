<?php
/**
 * registrar.php
 * Formulario seguro para registrar un nuevo evento personal.
 */

declare(strict_types=1);

require_once __DIR__ . '/data/conexion.php';
require_once __DIR__ . '/auth/sesion.php';

$usuario = verificarSesion($conn, false, 'auth/login.php');
$usuarioId = (int) $usuario['id'];
$inicialUsuario = mb_strtoupper(mb_substr($usuario['nombre'], 0, 1, 'UTF-8'), 'UTF-8');

$categorias = [];
$resCat = $conn->query('SELECT id, nombre FROM categoria ORDER BY id ASC');
while ($fila = $resCat->fetch_assoc()) {
    $categorias[] = $fila;
}
$resCat->free();

$errores = [];
$titulo = '';
$fecha = date('Y-m-d');
$hora = '09:00';
$categoriaId = !empty($categorias) ? (int) $categorias[0]['id'] : 0;
$descripcion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $fecha = trim((string) ($_POST['fecha'] ?? ''));
    $hora = trim((string) ($_POST['hora'] ?? ''));
    $categoriaId = isset($_POST['categoria_id']) ? (int) $_POST['categoria_id'] : 0;
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

    if (!verificarCsrf(is_string($csrf) ? $csrf : null)) {
        $errores[] = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
    }

    if ($titulo === '' || mb_strlen($titulo, 'UTF-8') > 150) {
        $errores[] = 'Ingresa un título válido (máximo 150 caracteres).';
    }

    $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
        $errores[] = 'Selecciona una fecha válida.';
    }

    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
        $errores[] = 'Selecciona una hora válida.';
    }

    if ($categoriaId <= 0) {
        $errores[] = 'Selecciona una categoría válida.';
    } else {
        $stmtCat = $conn->prepare('SELECT id FROM categoria WHERE id = ? LIMIT 1');
        $stmtCat->bind_param('i', $categoriaId);
        $stmtCat->execute();
        $existeCat = $stmtCat->get_result()->fetch_assoc();
        $stmtCat->close();
        if (!$existeCat) {
            $errores[] = 'La categoría seleccionada no es válida.';
        }
    }

    if (empty($errores)) {
        $horaSql = strlen($hora) === 5 ? $hora . ':00' : $hora;
        $stmtInsert = $conn->prepare(
            'INSERT INTO eventos (usuario_id, titulo, fecha, hora, categoria_id, descripcion)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmtInsert->bind_param(
            'isssis',
            $usuarioId,
            $titulo,
            $fecha,
            $horaSql,
            $categoriaId,
            $descripcion
        );
        $stmtInsert->execute();
        $stmtInsert->close();

        header('Location: index.php?creado=1');
        exit;
    }
}

$csrfToken = obtenerCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuevo evento — AgendaWeb</title>
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
        <a href="explorar.php" class="nav-link">Explorar</a>
        <a href="registrar.php" class="nav-link is-active">Nuevo evento</a>
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
      <section class="glass-panel form-card" aria-labelledby="new-event-title">
        <div style="margin-bottom: 1.5rem;">
          <h1 id="new-event-title" class="page-title">Registrar nuevo evento</h1>
          <p class="page-subtitle">Añade un compromiso, recordatorio o actividad a tu agenda personal.</p>
        </div>

        <?php if (!empty($errores)): ?>
          <div class="alert alert-error" role="alert">
            <?php foreach ($errores as $msg): ?>
              <div><?= escapar($msg) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="registrar.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">

          <div class="form-group">
            <label class="form-label" for="titulo">Título del evento</label>
            <input
              class="form-input"
              type="text"
              id="titulo"
              name="titulo"
              value="<?= escapar($titulo) ?>"
              placeholder="Ej. Reunión de planificación trimestral"
              maxlength="150"
              required
            >
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label" for="fecha">Fecha</label>
              <input
                class="form-input"
                type="date"
                id="fecha"
                name="fecha"
                value="<?= escapar($fecha) ?>"
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label" for="hora">Hora</label>
              <input
                class="form-input"
                type="time"
                id="hora"
                name="hora"
                value="<?= escapar($hora) ?>"
                required
              >
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="categoria_id">Categoría</label>
            <select class="form-select" id="categoria_id" name="categoria_id" required>
              <?php foreach ($categorias as $cat): ?>
                <option
                  value="<?= (int) $cat['id'] ?>"
                  <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>
                >
                  <?= escapar($cat['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="descripcion">Descripción</label>
            <textarea
              class="form-textarea"
              id="descripcion"
              name="descripcion"
              placeholder="Notas adicionales, enlaces o agenda de puntos..."
            ><?= escapar($descripcion) ?></textarea>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar evento</button>
          </div>
        </form>
      </section>
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
</body>
</html>
