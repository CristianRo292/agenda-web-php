<?php
/**
 * data/actualizar.php
 * Actualización asíncrona (AJAX/Fetch) de eventos con validación de propiedad y CSRF.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../auth/sesion.php';

$usuario = verificarSesion($conn, true, '../auth/login.php');
$usuarioId = (int) $usuario['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Aceptar tanto JSON como application/x-www-form-urlencoded / FormData
$cuerpoCrudo = file_get_contents('php://input');
$datosJson = json_decode($cuerpoCrudo ?: '', true);
$datos = is_array($datosJson) ? $datosJson : $_POST;

$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
$csrfBody = $datos['csrf_token'] ?? null;
$tokenCsrf = is_string($csrfHeader) ? $csrfHeader : (is_string($csrfBody) ? $csrfBody : null);

if (!verificarCsrf($tokenCsrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token de seguridad inválido. Recarga la página.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$eventoId = isset($datos['id']) ? (int) $datos['id'] : 0;
$titulo = trim((string) ($datos['titulo'] ?? ''));
$fecha = trim((string) ($datos['fecha'] ?? ''));
$hora = trim((string) ($datos['hora'] ?? ''));
$categoriaId = isset($datos['categoria_id']) ? (int) $datos['categoria_id'] : 0;
$descripcion = trim((string) ($datos['descripcion'] ?? ''));

if ($eventoId <= 0 || $titulo === '' || $fecha === '' || $hora === '' || $categoriaId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos obligatorios deben completarse.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($titulo, 'UTF-8') > 150) {
    http_response_code(400);
    echo json_encode(['error' => 'El título no puede superar los 150 caracteres.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
    http_response_code(400);
    echo json_encode(['error' => 'El formato de fecha no es válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Normalizar hora HH:MM o HH:MM:SS
if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
    $horaSql = $hora . ':00';
} elseif (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
    $horaSql = $hora;
} else {
    http_response_code(400);
    echo json_encode(['error' => 'El formato de hora no es válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar que la categoría exista
$stmtCat = $conn->prepare('SELECT id, nombre FROM categoria WHERE id = ? LIMIT 1');
$stmtCat->bind_param('i', $categoriaId);
$stmtCat->execute();
$catFila = $stmtCat->get_result()->fetch_assoc();
$stmtCat->close();

if (!$catFila) {
    http_response_code(400);
    echo json_encode(['error' => 'La categoría seleccionada no existe.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar que el evento pertenezca al usuario autenticado antes de actualizar
$stmtCheck = $conn->prepare('SELECT id FROM eventos WHERE id = ? AND usuario_id = ? LIMIT 1');
$stmtCheck->bind_param('ii', $eventoId, $usuarioId);
$stmtCheck->execute();
$existe = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if (!$existe) {
    http_response_code(404);
    echo json_encode(['error' => 'Evento no encontrado o sin permisos para editarlo.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtUpd = $conn->prepare(
    'UPDATE eventos
     SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ?
     WHERE id = ? AND usuario_id = ?
     LIMIT 1'
);
$stmtUpd->bind_param(
    'sssisii',
    $titulo,
    $fecha,
    $horaSql,
    $categoriaId,
    $descripcion,
    $eventoId,
    $usuarioId
);
$stmtUpd->execute();
$stmtUpd->close();

echo json_encode([
    'ok' => true,
    'mensaje' => 'Evento actualizado correctamente.',
    'evento' => [
        'id' => $eventoId,
        'titulo' => $titulo,
        'fecha' => $fecha,
        'hora' => substr($horaSql, 0, 5),
        'categoria_id' => $categoriaId,
        'categoria' => $catFila['nombre'],
        'descripcion' => $descripcion,
    ],
], JSON_UNESCAPED_UNICODE);
