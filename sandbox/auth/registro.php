<?php

session_start();
if (isset($_SESSION['uid']))
    {
        header('Location:index.php');
        exit;
    }
    require __DIR__.'/../data/conexion.php';

    $nombre = $correo = '';
    $errores = [];

    if($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            $nombre = trim($_POST['nombre']??'');
            $correo = mb_strtolower(trim($_POST['correo']??''));
            $clave = $_POST['clave'] ?? '';
            $clave2 = $_POST['clave2'] ?? '';

            if ($nombre === '' || mb_strlen($nombre) > 80) // validamos que el nombre tenga algo y sea menor a 80 caracteres
            {
                $errores['nombre'] = 'Escribe tu nombre (maximo 80 caracteres).';
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) // validamos correo usando una fucion propia de php
            {
                $errores['correo'] = 'Escribe un correo valido.';
            }
            if (mb_strlen($clave) < 8)
            {
                $errores['clave'] = 'La contraseña debe tener almenos 8 caracteres';
            }
            else if ($clave !== $clave2)
            {
                $errores['clave2'] = 'Las contraseñas no coinciden .';
            }
            if (empty($errores)) 
            {
                $hash = password_hash($clave, PASSWORD_DEFAULT);
                try 
                {
                    $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
                    $stmt->bind_param('sss', $nombre, $correo, $hash);
                    $stmt->execute();
                    header('Location: login.php?registrado=1');
                    exit;
                } 
                catch (mysqli_sql_exception $ex) 
                {
                    if ($ex->getCode() === 1062) 
                    {
                        $errores['correo'] = 'Ese correo ya tiene una cuenta.';
                    } 
                    else 
                    {
                        $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
                    }
                }
            }
        }           

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - AgendaWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/styles.css">
</head>
<body class="layout">
    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="login.php" class="logo">Agenda<span>Web</span></a>
        </div>
    </header>

    <main class="contenedor-principal">
        <div class="tarjeta-formulario">
            <div class="encabezado-formulario">
                <h1>Crear Cuenta</h1>
                <p>Regístrate para comenzar a organizar tu agenda de forma segura.</p>
            </div>

            <?php if (!empty($errores['general'])): ?>
                <div class="alert alert--notOk"><span>⚠️</span> <?= htmlspecialchars($errores['general']) ?></div>
            <?php endif; ?>

            <form action="registro.php" method="POST" class="formulario-agenda">
                <div class="grupo-formulario">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="80" required value="<?= htmlspecialchars($nombre) ?>">
                    <?php if (!empty($errores['nombre'])): ?><small style="color: var(--color-peligro);"><?= $errores['nombre'] ?></small><?php endif; ?>
                </div>

                <div class="grupo-formulario">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" required value="<?= htmlspecialchars($correo) ?>">
                    <?php if (!empty($errores['correo'])): ?><small style="color: var(--color-peligro);"><?= $errores['correo'] ?></small><?php endif; ?>
                </div>

                <div class="grupo-formulario">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" minlength="8" autocomplete="new-password" required>
                    <?php if (!empty($errores['clave'])): ?><small style="color: var(--color-peligro);"><?= $errores['clave'] ?></small><?php endif; ?>
                </div>

                <div class="grupo-formulario">
                    <label for="clave2">Repetir contraseña</label>
                    <input type="password" id="clave2" name="clave2" minlength="8" autocomplete="new-password" required>
                    <?php if (!empty($errores['clave2'])): ?><small style="color: var(--color-peligro);"><?= $errores['clave2'] ?></small><?php endif; ?>
                </div>

                <div class="acciones-formulario">
                    <a href="login.php" class="btn-secondary" style="text-align:center;">Ya tengo cuenta</a>
                    <button type="submit" class="btn-primary">Crear cuenta</button>
                </div>
            </form>
        </div>
    </main>
    </body>
</html>