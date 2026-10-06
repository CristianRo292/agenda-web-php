<?php
header('Content-Type: application/json');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar datos
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$titulo = trim($_POST['titulo'] ?? '');
$fecha = trim($_POST['fecha'] ?? '');
$hora = trim($_POST['hora'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

// Validaciones
$errores = [];
$categoriasOK = ['trabajo', 'personal', 'importante', 'otro'];

if (!$id) {
    $errores[] = 'ID inválido.';
}
if ($titulo === '' || mb_strlen($titulo) > 120) {
    $errores[] = 'Título inválido (1-120 caracteres).';
}
if ($fecha === '' || !DateTime::createFromFormat('Y-m-d', $fecha)) {
    $errores[] = 'Fecha inválida.';
}
if (!in_array($categoria, $categoriasOK, true)) {
    $errores[] = 'Categoría inválida.';
}

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

// Conexión y actualización
require 'conexion.php';

$sql = "UPDATE eventos SET titulo = ?, fecha = ?, hora = ?, categoria = ?, descripcion = ? WHERE id = ?";
$stmt = $mysqli->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sssssi", $titulo, $fecha, $hora, $categoria, $descripcion, $id);
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