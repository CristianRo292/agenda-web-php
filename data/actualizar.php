<?php
require __DIR__ . '/auth/sesion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// ⭐ Validar datos
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$titulo = trim($_POST['titulo'] ?? '');
$fecha = trim($_POST['fecha'] ?? '');
$hora = trim($_POST['hora'] ?? '');
$categoria_id = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
$descripcion = trim($_POST['descripcion'] ?? '');

$errores = [];

if (!$id) {
    $errores[] = 'ID inválido.';
}
if ($titulo === '' || mb_strlen($titulo) > 120) {
    $errores[] = 'Título inválido (1-120 caracteres).';
}
if ($fecha === '' || !DateTime::createFromFormat('Y-m-d', $fecha)) {
    $errores[] = 'Fecha inválida.';
}
// ⭐ Validar que categoria_id exista en la BD
if (!$categoria_id) {
    $errores[] = 'Debes seleccionar una categoría válida.';
} else {
    require 'conexion.php';
    $stmtCat = $mysqli->prepare("SELECT id FROM categoria WHERE id = ?");
    $stmtCat->bind_param("i", $categoria_id);
    $stmtCat->execute();
    $stmtCat->store_result();
    if ($stmtCat->num_rows === 0) {
        $errores[] = 'La categoría seleccionada no existe.';
    }
    $stmtCat->close();
}

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

// ⭐ Actualizar usando categoria_id
require 'conexion.php';

// Reemplaza tu consulta UPDATE por esta:
$sql = "UPDATE eventos 
        SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ? 
        WHERE id = ? AND usuario_id = ?";
$stmt = $mysqli->prepare($sql);

if ($stmt) {
    // 'sssisii' = string, string, string, integer, string, integer, integer
    $stmt->bind_param("sssisii", $titulo, $fecha, $hora, $categoria_id, $descripcion, $id, $usuarioId);
    $stmt->execute();
    
    if ($stmt->affected_rows >= 0) { // >= 0 porque puede no haber cambios si es igual
        $stmt->close();
        $mysqli->close();
        echo json_encode(['success' => true]);
    } else {
        $stmt->close();
        $mysqli->close();
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar.']);
    }
} else {
    $mysqli->close();
    echo json_encode(['success' => false, 'message' => 'Error en la consulta.']);
}
?>