<?php
/* api.php · P10 · Mis eventos en JSON */

// 1. Iniciar sesión para verificar identidad
session_start();

// 2. Cabecera JSON: debe ir ANTES de cualquier salida
header('Content-Type: application/json; charset=utf-8');

// Función auxiliar para responder consistentemente
function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Guardia de la API: 401 si no hay sesión
if (!isset($_SESSION['uid'])) {
    responder(401, ['error' => 'Inicia sesión para consultar tus eventos.']);
}

$usuarioId = (int) $_SESSION['uid'];

// 4. Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responder(405, ['error' => 'Método no permitido. Usa GET.']);
}

// 5. Conexión a la base de datos
require __DIR__ . '/../data/conexion.php';

// 6. Capturar y sanitizar filtros de la URL
$q           = mb_substr(trim($_GET['q'] ?? ''), 0, 60); // Búsqueda por texto
$categoriaId = (int) ($_GET['categoria'] ?? 0);          // Filtro por ID de categoría

// Construcción dinámica de la consulta (igual que en tu index.php pero optimizada)
$condiciones = ['e.usuario_id = ?']; // SIEMPRE filtrar por usuario (Autorización)
$tipos       = 'i';
$valores     = [$usuarioId];

if ($q !== '') {
    // Escapar caracteres especiales de LIKE (% y _) para evitar inyección lógica
    $patron = '%' . addcslashes($q, '%_\\') . '%';
    // Buscar en título, descripción y nombre de categoría
    $condiciones[] = '(e.titulo LIKE ? OR e.descripcion LIKE ? OR c.nombre LIKE ?)';
    $tipos      .= 'sss';
    $valores[]   = $patron;
    $valores[]   = $patron;
    $valores[]   = $patron;
}

if ($categoriaId > 0) {
    $condiciones[] = 'e.categoria_id = ?';
    $tipos      .= 'i';
    $valores[]   = $categoriaId;
}

$sql = "SELECT e.id, e.titulo, e.fecha,
               TIME_FORMAT(e.hora, '%H:%i') AS hora,
               c.nombre AS categoria, e.descripcion
          FROM eventos e
          JOIN categoria c ON c.id = e.categoria_id
         WHERE " . implode(' AND ', $condiciones) . "
         ORDER BY e.fecha ASC, e.hora ASC";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    responder(500, ['error' => 'Error en la preparación de la consulta.']);
}

$stmt->bind_param($tipos, ...$valores);
$stmt->execute();
$eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();

// 7. Respuesta exitosa (200 OK)
responder(200, [
    'usuario' => $_SESSION['nombre'] ?? '',
    'total'   => count($eventos),
    'filtros' => [
        'q'         => $q,
        'categoria' => $categoriaId > 0 ? $categoriaId : null
    ],
    'eventos' => $eventos,
]);
?>