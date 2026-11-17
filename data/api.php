<?php
/*api.php . 10 . mis eventos en JSON*/
session_start();

// 1 . lo primero que sale: la etiqueta "esto es un JSON"
header('Content-Type: application/json; charset=utf-8');

// Una sola salida para todas las respuestas: código + JSON + fin
function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. sin sesion no hay datos: 401 = "no se quien eres"
if (!isset($_SESSION['uid']))
    {
        responder(401,['error' => 'Inicia sesion para consultar tus eventos.']);

    }
    $usuarioId = (int) $_SESSION['uid'];

    // esta api solo consulta: cualquier otro metodo se rechaza
    if ($_SERVER['REQUEST_METHOD'] !== 'GET')
        {
            header('Allow:  GET');
            responder(405, ['error' => 'Metodo no permitido. Usa GET']);
        }
    
    require __DIR__.'/conexion.php';

    // 3. nunca select *: elige las columnas que entregas
    $sql = "SELECT e.id, e.titulo, e.fecha, TIME_FORMAT(e.hora, '%H:%i') AS hora, "
    

?>