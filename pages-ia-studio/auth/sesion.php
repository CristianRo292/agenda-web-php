<?php
/**
 * auth/sesion.php
 * Guardia de sesión y utilidades de seguridad (CSRF, escape XSS, cookies seguras).
 */

declare(strict_types=1);

/**
 * Inicia la sesión PHP con parámetros de cookie seguros si aún no está activa.
 */
function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $esHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        );

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $esHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Devuelve el token CSRF de la sesión actual.
 */
function obtenerCsrfToken(): string
{
    iniciarSesionSegura();
    return (string) $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF enviado en formularios o cabeceras Fetch.
 */
function verificarCsrf(?string $tokenRecibido): bool
{
    iniciarSesionSegura();
    if (!is_string($tokenRecibido) || $tokenRecibido === '') {
        return false;
    }
    return hash_equals((string) $_SESSION['csrf_token'], $tokenRecibido);
}

/**
 * Escapa cadenas de texto para prevenir XSS en todo HTML generado por PHP.
 */
function escapar(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Devuelve la clase CSS del badge de color según la categoría.
 */
function claseBadgeCategoria(string $categoriaNombre): string
{
    $normalizada = mb_strtolower(trim($categoriaNombre), 'UTF-8');
    return match ($normalizada) {
        'trabajo' => 'category-badge--trabajo',
        'personal' => 'category-badge--personal',
        'importante' => 'category-badge--importante',
        'salud' => 'category-badge--salud',
        'estudio' => 'category-badge--estudio',
        default => 'category-badge--default',
    };
}

/**
 * Verifica la sesión activa (uid), carga los datos frescos del usuario desde MySQL
 * y protege las rutas privadas (HTML o API JSON).
 */
function verificarSesion(
    mysqli $conn,
    bool $esApi = false,
    string $rutaLogin = 'auth/login.php'
): array {
    iniciarSesionSegura();

    $uid = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;

    if ($uid <= 0) {
        responderNoAutorizado($esApi, $rutaLogin);
    }

    $stmt = $conn->prepare(
        'SELECT id, nombre, correo, creado_en FROM usuarios WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        // La cuenta ya no existe; limpiar sesión huérfana
        $_SESSION = [];
        session_destroy();
        responderNoAutorizado($esApi, $rutaLogin);
    }

    return $usuario;
}

/**
 * Emite respuesta 401 JSON para endpoints API o redirige al login para vistas web.
 */
function responderNoAutorizado(bool $esApi, string $rutaLogin): never
{
    if ($esApi) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'No autorizado. Debes iniciar sesión para acceder a este recurso.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ' . $rutaLogin);
    exit;
}
