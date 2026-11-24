<?php
/**
 * bienvenida.php
 * Landing page inicial minimalista de AgendaWeb.
 * Redirige automáticamente a index.php si existe una sesión activa.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth/sesion.php';

iniciarSesionSegura();

if (!empty($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}

$cuentaEliminada = isset($_GET['cuenta_eliminada']) && $_GET['cuenta_eliminada'] === '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgendaWeb — Tu tiempo, en calma</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
  <main class="centered-page">
    <section class="glass-panel landing-card" aria-labelledby="welcome-title">
      <div class="landing-logo" aria-hidden="true">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="4"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
          <path d="m9 16 2 2 4-4"></path>
        </svg>
      </div>

      <h1 id="welcome-title" class="landing-title">AgendaWeb</h1>
      <p class="landing-subtitle">
        Organiza tus compromisos diarios con claridad y serenidad. Un refugio personal para planificar tu tiempo sin distracciones.
      </p>

      <?php if ($cuentaEliminada): ?>
        <div class="alert alert-success" role="status">
          Tu cuenta y tus eventos fueron eliminados correctamente.
        </div>
      <?php endif; ?>

      <div style="display: flex; flex-direction: column; gap: 0.75rem; align-items: center;">
        <a href="auth/login.php" class="btn btn-primary" style="min-width: 210px;">
          Comenzar
        </a>
        <a href="auth/registro.php" style="font-size: 0.92rem; font-weight: 500;">
          Crear una cuenta nueva
        </a>
      </div>
    </section>
  </main>
</body>
</html>
