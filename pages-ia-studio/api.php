<?php
/**
 * api.php
 * Endpoint JSON protegido por sesión para consultar eventos del usuario autenticado.
 * Devuelve: { usuario, total, filtros, eventos: [] }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/data/conexion.php';
require_once __DIR__ . '/auth/sesion.php';

// Exigir sesión válida; responde 401 JSON automáticamente si no hay sesión
$usuario = verificarSesion($conn, true);
$usuarioId = (int) $usuario['id'];

$q = trim((string) ($_GET['q'] ?? ''));
$categoriaFiltro = trim((string) ($_GET['categoria'] ?? ''));

$enTitulo = !isset($_GET['en_titulo']) || $_GET['en_titulo'] === '1';
$enCategoria = !isset($_GET['en_categoria']) || $_GET['en_categoria'] === '1';
$enDescripcion = !isset($_GET['en_descripcion']) || $_GET['en_descripcion'] === '1';

// Si todos los switches vienen apagados pero hay término de búsqueda, buscar en título por defecto
if (!$enTitulo && !$enCategoria && !$enDescripcion) {
    $enTitulo = true;
}

$sql = 'SELECT
            e.id,
            e.titulo,
            e.fecha,
            DATE_FORMAT(e.hora, "%H:%i") AS hora,
            e.categoria_id,
            c.nombre AS categoria,
            e.descripcion,
            e.creado_en
        FROM eventos e
        INNER JOIN categoria c ON c.id = e.categoria_id
        WHERE e.usuario_id = ?';

$tipos = 'i';
$parametros = [$usuarioId];

// Filtro opcional por categoría (ID numérico o nombre exacto)
if ($categoriaFiltro !== '') {
    if (ctype_digit($categoriaFiltro)) {
        $sql .= ' AND e.categoria_id = ?';
        $tipos .= 'i';
        $parametros[] = (int) $categoriaFiltro;
    } else {
        $sql .= ' AND LOWER(c.nombre) = LOWER(?)';
        $tipos .= 's';
        $parametros[] = $categoriaFiltro;
    }
}

// Búsqueda textual segura con sentencias preparadas
if ($q !== '') {
    $like = '%' . $q . '%';
    $condicionesBusqueda = [];

    if ($enTitulo) {
        $condicionesBusqueda[] = 'e.titulo LIKE ?';
        $tipos .= 's';
        $parametros[] = $like;
    }
    if ($enCategoria) {
        $condicionesBusqueda[] = 'c.nombre LIKE ?';
        $tipos .= 's';
        $parametros[] = $like;
    }
    if ($enDescripcion) {
        $condicionesBusqueda[] = 'COALESCE(e.descripcion, "") LIKE ?';
        $tipos .= 's';
        $parametros[] = $like;
    }

    if (!empty($condicionesBusqueda)) {
        $sql .= ' AND (' . implode(' OR ', $condicionesBusqueda) . ')';
    }
}

$sql .= ' ORDER BY e.fecha ASC, e.hora ASC, e.id DESC';

$stmt = $conn->prepare($sql);
$stmt->bind_param($tipos, ...$parametros);
$stmt->execute();
$resultado = $stmt->get_result();

$eventos = [];
while ($fila = $resultado->fetch_assoc()) {
    $eventos[] = [
        'id' => (int) $fila['id'],
        'titulo' => $fila['titulo'],
        'fecha' => $fila['fecha'],
        'hora' => $fila['hora'],
        'categoria_id' => (int) $fila['categoria_id'],
        'categoria' => $fila['categoria'],
        'descripcion' => (string) ($fila['descripcion'] ?? ''),
        'creado_en' => $fila['creado_en'],
    ];
}
$stmt->close();

echo json_encode([
    'usuario' => [
        'id' => $usuarioId,
        'nombre' => $usuario['nombre'],
        'correo' => $usuario['correo'],
    ],
    'total' => count($eventos),
    'filtros' => [
        'q' => $q,
        'categoria' => $categoriaFiltro,
        'en_titulo' => $enTitulo,
        'en_categoria' => $enCategoria,
        'en_descripcion' => $enDescripcion,
    ],
    'eventos' => $eventos,
], JSON_UNESCAPED_UNICODE);
