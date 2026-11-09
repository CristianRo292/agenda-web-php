<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: /auth/login.php');
    exit;
}

// Quién está usando la app
$usuarioId     = (int) $_SESSION['uid'];
$usuarioNombre = $_SESSION['nombre'] ?? 'Usuario';
$usuarioCorreo = $_SESSION['correo'] ?? ''; // ⭐ NUEVO

// Si el correo no está en sesión, cargarlo desde la BD (solo la primera vez)
if ($usuarioCorreo === '') {
    require __DIR__ . '/../data/conexion.php';
    $stmt = $mysqli->prepare('SELECT correo FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $usuarioCorreo = $row['correo'];
        $_SESSION['correo'] = $usuarioCorreo; // Guardarlo en sesión para próximas cargas
    }
}

function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>