<?php
/**
 * auth/logout.php
 * Cierra la sesión actual de forma segura y limpia la cookie de sesión.
 */

declare(strict_types=1);

require_once __DIR__ . '/sesion.php';

iniciarSesionSegura();

// Vaciar arreglo de sesión
$_SESSION = [];

// Invalidar cookie de sesión en el navegador si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ../bienvenida.php');
exit;
