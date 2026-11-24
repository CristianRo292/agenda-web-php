<?php
/**
 * data/conexion.php
 * Conexión segura a MySQL mediante MySQLi con soporte para variables de entorno
 * y manejo estricto de errores en UTF-8.
 */

declare(strict_types=1);

// Activar reporte de excepciones en MySQLi para un control limpio de errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '123456';
$dbName = getenv('DB_NAME') ?: 'agendaweb';

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Registrar el error internamente sin exponer credenciales ni detalles al navegador
    error_log('Error de conexión a MySQL en AgendaWeb: ' . $e->getMessage());

    http_response_code(500);
    if (
        isset($_SERVER['HTTP_ACCEPT']) &&
        str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
    ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'No se pudo conectar con el servicio de datos. Inténtalo más tarde.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error de servicio</title></head><body>';
        echo '<p style="font-family: sans-serif; padding: 2rem;">Error interno de conexión con la base de datos. Verifica la configuración local.</p>';
        echo '</body></html>';
    }
    exit;
}
