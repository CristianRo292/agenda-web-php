<?php
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

$sql = "UPDATE eventos SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ? WHERE id = ?";
$stmt = $mysqli->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sssisi", $titulo, $fecha, $hora, $categoria_id, $descripcion, $id);
    $stmt->execute();
    
    if ($stmt->errno === 0) {
        $stmt->close();
        $mysqli->close();
        echo json_encode(['success' => true]);
    } else {
        $error_msg = $stmt->error;
        $stmt->close();
        $mysqli->close();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $error_msg]);
    }
} else {
    $mysqli->close();
    echo json_encode(['success' => false, 'message' => 'Error en la consulta.']);
}
?>