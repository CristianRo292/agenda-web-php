
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>
<body>
    <?php
// indexado: cada valor tiene una POSICIÓN (empieza en 0)
$categorias = ['trabajo','personal','estudio','ocio'];
echo $categorias[0] . '<br>'; // mostramos el elemento 1 de la lista
echo count($categorias) . '<br>';

// Asociativo: cada valor tiene una CLAVE con nombre
$evento = ['titulo' => 'Examen de Cálculo','fecha' => '2026-10-08'];
echo $evento['titulo'];
?>
</body>
</html>