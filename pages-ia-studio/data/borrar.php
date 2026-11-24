<?php
/**
 * data/borrar.php
 * Eliminación segura de eventos (verificando usuario_id) o de cuenta de usuario.
 */

declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../auth/sesion.php';

$usuario = verificarSesion($conn, false, '../auth/login.php');
$usuarioId = (int) $usuario['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verificarCsrf(is_string($csrf) ? $csrf : null)) {
    header('Location: ../index.php?error=csrf');
    exit;
}

$accion = (string) ($_POST['accion'] ?? 'eliminar_evento');

if ($accion === 'eliminar_cuenta') {
    $stmt = $conn->prepare('DELETE FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $stmt->close();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();

    header('Location: ../bienvenida.php?cuenta_eliminada=1');
    exit;
}

$eventoId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($eventoId <= 0) {
    header('Location: ../index.php?error=id_invalido');
    exit;
}

// Verificación estricta de pertenencia: WHERE id = ? AND usuario_id = ?
$stmt = $conn->prepare('DELETE FROM eventos WHERE id = ? AND usuario_id = ? LIMIT 1');
$stmt->bind_param('ii', $eventoId, $usuarioId);
$stmt->execute();
$eliminado = $stmt->affected_rows > 0;
$stmt->close();

if ($eliminado) {
    header('Location: ../index.php?eliminado=1');
} else {
    header('Location: ../index.php?error=no_encontrado');
}
exit;
