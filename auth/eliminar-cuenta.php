<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/../data/conexion.php';

// Solo aceptar POST (seguridad)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Confirmación doble: debe venir el parámetro 'confirmar'
if (!isset($_POST['confirmar']) || $_POST['confirmar'] !== 'SI') {
    header('Location: ../index.php');
    exit;
}

// Eliminar el usuario (CASCADE borrará sus eventos automáticamente)
$stmt = $mysqli->prepare('DELETE FROM usuarios WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $mysqli->close();
        
        // Destruir sesión completamente
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        
        // Redirigir al login con mensaje de cuenta eliminada
        header('Location: login.php?cuenta_eliminada=1');
        exit;
    } else {
        $stmt->close();
        $mysqli->close();
        header('Location: ../index.php?error=No se pudo eliminar la cuenta.');
        exit;
    }
} else {
    $mysqli->close();
    header('Location: ../index.php?error=Error en la consulta.');
    exit;
}
?>