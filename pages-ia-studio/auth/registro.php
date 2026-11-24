<?php
/**
 * auth/registro.php
 * Registro de nuevos usuarios con validación de correo único,
 * contraseña mínima de 8 caracteres y password_hash().
 */

declare(strict_types=1);

require_once __DIR__ . '/../data/conexion.php';
require_once __DIR__ . '/sesion.php';

iniciarSesionSegura();

// Si ya existe sesión activa, redirigir al dashboard
if (!empty($_SESSION['uid'])) {
    header('Location: ../index.php');
    exit;
}

$errores = [];
$nombre = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');
    $confirmarClave = (string) ($_POST['confirmar_clave'] ?? '');

    if (!verificarCsrf(is_string($csrf) ? $csrf : null)) {
        $errores[] = 'La sesión del formulario expiró. Inténtalo nuevamente.';
    }

    if ($nombre === '' || mb_strlen($nombre, 'UTF-8') < 2) {
        $errores[] = 'Ingresa tu nombre (al menos 2 caracteres).';
    }

    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }

    if (mb_strlen($clave, 'UTF-8') < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($clave !== $confirmarClave) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        // Verificar que el correo no esté registrado
        $stmtCheck = $conn->prepare(
            'SELECT id FROM usuarios WHERE correo = ? LIMIT 1'
        );
        $stmtCheck->bind_param('s', $correo);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $errores[] = 'Este correo electrónico ya se encuentra registrado.';
            $stmtCheck->close();
        } else {
            $stmtCheck->close();

            $claveHash = password_hash($clave, PASSWORD_DEFAULT);
            $stmtInsert = $conn->prepare(
                'INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)'
            );
            $stmtInsert->bind_param('sss', $nombre, $correo, $claveHash);
            $stmtInsert->execute();
            $nuevoUsuarioId = (int) $stmtInsert->insert_id;
            $stmtInsert->close();

            // Iniciar sesión inmediatamente con ID regenerado
            session_regenerate_id(true);
            $_SESSION['uid'] = $nuevoUsuarioId;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: ../index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear cuenta — AgendaWeb</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/styles.css">
</head>
<body>
  <main class="centered-page">
    <section class="glass-panel auth-card" aria-labelledby="register-title">
      <div class="auth-header">
        <div class="avatar-circle avatar-circle--lg" id="register-avatar-preview" aria-hidden="true">
          <?= escapar($nombre !== '' ? mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'), 'UTF-8') : 'N') ?>
        </div>
        <h1 id="register-title" class="page-title">Crear cuenta</h1>
        <p>Organiza tus eventos personales con serenidad</p>
      </div>

      <?php if (!empty($errores)): ?>
        <div class="alert alert-error" role="alert">
          <?php foreach ($errores as $msg): ?>
            <div><?= escapar($msg) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="registro.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= escapar(obtenerCsrfToken()) ?>">

        <div class="form-group">
          <label class="form-label" for="nombre">Nombre completo</label>
          <input
            class="form-input"
            type="text"
            id="nombre"
            name="nombre"
            value="<?= escapar($nombre) ?>"
            placeholder="Ej. Sofía Mendoza"
            autocomplete="name"
            required
          >
        </div>

        <div class="form-group">
          <label class="form-label" for="correo">Correo electrónico</label>
          <input
            class="form-input"
            type="email"
            id="correo"
            name="correo"
            value="<?= escapar($correo) ?>"
            placeholder="tu@correo.com"
            autocomplete="email"
            required
          >
        </div>

        <div class="form-group">
          <label class="form-label" for="clave">Contraseña (mínimo 8 caracteres)</label>
          <div class="password-field">
            <input
              class="form-input"
              type="password"
              id="clave"
              name="clave"
              placeholder="Mínimo 8 caracteres"
              minlength="8"
              autocomplete="new-password"
              required
            >
            <button
              type="button"
              class="password-toggle"
              data-password-toggle="clave"
              aria-label="Mostrar contraseña"
              aria-pressed="false"
            ></button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirmar_clave">Confirmar contraseña</label>
          <div class="password-field">
            <input
              class="form-input"
              type="password"
              id="confirmar_clave"
              name="confirmar_clave"
              placeholder="Repite tu contraseña"
              minlength="8"
              autocomplete="new-password"
              required
            >
            <button
              type="button"
              class="password-toggle"
              data-password-toggle="confirmar_clave"
              aria-label="Mostrar contraseña"
              aria-pressed="false"
            ></button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          Registrarme
        </button>
      </form>

      <div class="auth-footer">
        <p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
      </div>
    </section>
  </main>

  <script src="../js/password-toggle.js"></script>
  <script>
    (function () {
      const nombreInput = document.getElementById('nombre');
      const avatarPreview = document.getElementById('register-avatar-preview');
      if (!nombreInput || !avatarPreview) return;

      nombreInput.addEventListener('input', function () {
        const valor = nombreInput.value.trim();
        avatarPreview.textContent = valor ? valor.charAt(0).toUpperCase() : 'N';
      });
    })();
  </script>
</body>
</html>
