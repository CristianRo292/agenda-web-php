<?php
// 1. Verificar que la petición sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// 2. Obtener y validar el ID
$id = $_POST['id'] ?? null;

// Validamos que el ID exista y sea estrictamente un número entero
if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    // Si el ID no es válido, redirigimos con error
    header('Location: ../index.php?deleted=0');
    exit;
}

// 3. Conexión a la base de datos
require 'conexion.php';

// 4. Preparar y ejecutar la consulta de eliminación
$sql = "DELETE FROM eventos WHERE id = ?";
$stmt = $mysqli->prepare($sql);

if ($stmt) {
    // "i" indica que el parámetro es un integer (entero)
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Verificamos si realmente se eliminó alguna fila
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $mysqli->close();
        // Éxito: redirigimos con mensaje de éxito
        header('Location: ../index.php?deleted=1');
        exit;
    } else {
        // El ID no existía en la base de datos
        $stmt->close();
        $mysqli->close();
        header('Location: ../index.php?deleted=0');
        exit;
    }
} else {
    // Error en la preparación de la consulta
    $mysqli->close();
    header('Location: ../index.php?deleted=0');
    exit;
}
?>