<?php
session_start();

// si no hay sesion, nos manda a la pagina de login
if (!isset($_SESSION['uid']))
    {
        header('Location: auth/login.php'); // redirijimos a login
        exit; // nos salimos
    }
    // Extraemos datos y los dejamos disponibles en variables
    $usuarioId = (int)$_SESSION['uid'];
    $usuarioNombre = $_SESSION['nombre'] ?? 'Usuario'; // si no tiene nombre el usuario le ponemos uno

    function e($string)
    {
        return htmlspecialchars($string,ENT_QUOTES,'UTF-8');
    }
    
?>