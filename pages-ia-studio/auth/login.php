<?php
/**
 * auth/login.php
 * Inicio de sesión seguro con password_verify() y regeneración de ID de sesión.
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

$error = '';
$correoIngresado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $correoIngresado = trim((string) ($_POST['correo'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    if (!verificarCsrf(is_string($csrf) ? $csrf : null)) {
        $error = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
    } elseif ($correoIngresado === '' || $clave === '') {
        $error = 'Correo o contraseña incorrectos';
    } else {
        $stmt = $conn->prepare(
            'SELECT id, nombre, correo, clave_hash FROM usuarios WHERE correo = ? LIMIT 1'
        );
        $stmt->bind_param('s', $correoIngresado);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        $stmt->close();

        if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
            // Regenerar ID de sesión para prevenir fijación de sesión
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $usuario['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: ../index.php');
            exit;
        }

        // Mensaje genérico para no revelar si el correo existe
        $error = 'Correo o contraseña incorrectos';
    }
}

$inicialAvatar = $correoIngresado !== ''
    ? mb_strtoupper(mb_substr($correoIngresado, 0, 1, 'UTF-8'), 'UTF-8')
    : 'A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión — AgendaWeb</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/styles.css">
</head>
<body>
  <main class="centered-page">
    <section class="glass-panel auth-card" aria-labelledby="login-title">
      <div class="auth-header">
        <div class="avatar-circle avatar-circle--lg" id="login-avatar-preview" aria-hidden="true">
          <?= escapar($inicialAvatar) ?>
        </div>
        <h1 id="login-title" class="page-title">Bienvenido de nuevo</h1>
        <p>Accede a tu espacio personal en AgendaWeb</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert">
          <?= escapar($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= escapar(obtenerCsrfToken()) ?>">

        <div class="form-group">
          <label class="form-label" for="correo">Correo electrónico</label>
          <input
            class="form-input"
            type="email"
            id="correo"
            name="correo"
            value="<?= escapar($correoIngresado) ?>"
            placeholder="tu@correo.com"
            autocomplete="email"
            required
          >
        </div>

        <div class="form-group">
          <label class="form-label" for="clave">Contraseña</label>
          <div class="password-field">
            <input
              class="form-input"
              type="password"
              id="clave"
              name="clave"
              placeholder="Ingresa tu contraseña"
              autocomplete="current-password"
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

        <button type="submit" class="btn btn-primary btn-block">
          Iniciar sesión
        </button>
      </form>

      <div class="auth-footer">
        <p>¿Aún no tienes cuenta? <a href="registro.php">Crear una cuenta</a></p>
        <p style="margin-top: 0.45rem;"><a href="../bienvenida.php">Volver al inicio</a></p>
      </div>
    </section>
  </main>

  <script src="../js/password-toggle.js"></script>
  <script>
    // Actualizar dinámicamente la inicial del avatar circular según el correo ingresado
    (function () {
      const correoInput = document.getElementById('correo');
      const avatarPreview = document.getElementById('login-avatar-preview');
      if (!correoInput || !avatarPreview) return;

      correoInput.addEventListener('input', function () {
        const valor = correoInput.value.trim();
        avatarPreview.textContent = valor ? valor.charAt(0).toUpperCase() : 'A';
      });
    })();
  </script>
</body>
</html>
