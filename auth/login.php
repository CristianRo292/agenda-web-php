<?php
session_start();
if (isset($_SESSION['uid'])) {
    header('Location: ../index.php');
    exit;
}
require __DIR__ . '/../data/conexion.php';

$correo = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';

    $stmt = $mysqli->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ?');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid']    = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        header('Location: ../index.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - AgendaWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/styles.css">
</head>
<body class="layout">
    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="../index.php" class="logo">
                <img src="../image/logoPaginaWeb.png" alt="Logotipo formal de la pagina web">Agenda<span>Web</span>
            </a>
        </div>
    </header>

    <main class="contenedor-principal">
        <div class="tarjeta-formulario">
            <div class="encabezado-formulario">
                <!-- ✨ NUEVA IMAGEN REDONDA DE PERFIL / LOGO -->
                <img src="../image/imagenLogiFormal.png" alt="Avatar de AgendaWeb" class="login-avatar">
                
                <h1>Iniciar Sesión</h1>
                <p>Bienvenido de nuevo a tu agenda personal.</p>
            </div>

            <?php if (isset($_GET['registrado'])): ?>
                <div class="alert alert--ok"><span>✅</span> Cuenta creada. Ya puedes iniciar sesión.</div>
            <?php endif; ?>
            <?php if (isset($_GET['salio'])): ?>
                <div class="alert alert--ok"><span>👋</span> Cerraste sesión. ¡Hasta pronto!</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert--notOk"><span>⚠️</span> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="formulario-agenda">
                <div class="grupo-formulario">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" required value="<?= htmlspecialchars($correo) ?>">
                </div>

                <div class="grupo-formulario">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" minlength="8" autocomplete="current-password" required>
                </div>

                <div class="acciones-formulario">
                    <!-- <a href="registro.php" class="btn-secondary" style="text-align:center;">Crear cuenta</a> -->
                    <button type="submit" class="btn-primary">Entrar</button>
                </div>
                <p class="txt_center">¿No tienes cuenta? 😶 <a href="registro.php">Crea una aquí</a></p>
            </form>
        </div>
    </main>
</body>
</html>