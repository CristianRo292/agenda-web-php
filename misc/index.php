<?php

function e (?string $texto_a_sanitizar): string // funcion que retorna un string
{
    return htmlspecialchars($texto_a_sanitizar ?? '', ENT_QUOTES, 'UTF-8');
}
// '2026-10-08'  →  '08/10/2026'
function formatearFecha(string $fecha): string
{
    return date('d/m/Y', strtotime($fecha));
}

// 'ocio'  →  'Ocio / Deporte'
function nombreCategoria(string $clave): string
{
    $nombres = [
        'importante'  => 'Importante',
        'trabajo' => 'Trabajo',
        'personal'  => 'Personal',
        'otro'     => 'Otro',
    ];
    return $nombres[$clave] ?? $clave;   // si la clave no existe, devuelve la original
}

require '../data/conexion.php';

$resultado = $mysqli->query(
    'SELECT id, titulo, fecha, hora, categoria, descripcion
       FROM eventos
      ORDER BY fecha, hora'
);
$eventos = $resultado->fetch_all(MYSQLI_ASSOC);   // ← un arreglo de arreglos asociativos
$mysqli->close();

// $eventos = [
//     ['id' => 1, 'titulo' => 'Reunion de acedemia', 'fecha' => '2026-10-08', 'hora' => '10:30:00',
//     'categoria' => 'trabajo', 'descripcion' => 'Revisar calificaciones de 1er parcial.'],

//     ['id' => 2, 'titulo' => 'Gimnasio', 'fecha' => '2026-10-09', 'hora' => '07:00:00',
//     'categoria' => 'ocio', 'descripcion' => null],

//     ['id' => 3, 'titulo' => 'Entregar P4 de Aplicaciones Web', 'fecha' => '2026-10-12', 'hora' => null,
//     'categoria' => 'estudio', 'descripcion' => 'Subir el repositorio y publicar en DomCloud'],
//     [],
// ];

?>

<!-- // comprobaremos que es lo que tien el arreglo -->
<?php 
    echo '<pre>'; print_r($eventos);echo '</pre>';
    echo $eventos[1]['titulo'];

    function mostrarEvento(array $ev): string
    {
        $html  = '<article class="card">';
        $html .= '<span class="card__badge">' . e(nombreCategoria($ev['categoria'])) . '</span>';
        $html .= '<h2 class="card__title">' . e($ev['titulo']) . '</h2>';

        $cuando = formatearFecha($ev['fecha']);
        if ($ev['hora']) {                          // la hora es opcional
            $cuando .= ' · ' . substr($ev['hora'], 0, 5);  // '10:30:00' → '10:30'
        }
        $html .= '<p class="card__meta"><time datetime="' . e($ev['fecha']) . '">' . e($cuando) . '</time></p>';

        if ($ev['descripcion']) {                   // la descripción también
            $html .= '<p class="card__text">' . e($ev['descripcion']) . '</p>';
        }

        $id = (int) $ev['id'];                         // el id SIEMPRE como número
        $html .= '<div class="card__actions">'
            . '<a href="editar.php?id=' . $id . '" class="btn-secondary btn-sm">Editar</a>'
            . '<form method="post" action="borrar.php" class="form-inline">'
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<button type="submit" class="btn-danger btn-sm">Borrar</button>'
            . '</form></div>';

        return $html . '</article>';
    }
?>
    

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>index Prueba</title>
    <!-- <link rel="stylesheet" href="../styles/styles.css"> -->
     <!-- estilos prefabircados para ahorrar tiempo -->
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css"> -->
     <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->
</head>
<body>
    <section class="card-list" aria-label="Lista de eventos">
    <?php foreach ($eventos as $ev): ?>
        <?= mostrarEvento($ev) ?>
    <?php endforeach; ?>

</section>
<h1>paso 6</h1>
<!-- El aviso, solo si registrar.php nos mandó ?ok=1 -->
<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert--ok" role="status">Evento guardado correctamente.</div>
<?php endif; ?>

<!-- El contador ya no está escrito a mano -->
<p class="page__subtitle"><?= count($eventos) ?> eventos registrados</p>

<!-- Lista O estado vacío, nunca los dos -->
<?php if (empty($eventos)): ?>
    <div class="empty-state"> ... </div>
<?php else: ?>
    <section class="card-list"> ... el foreach del Paso 5 ... </section>
<?php endif; ?>

<h1><a href="../prueba_arreglos.php">Prueba de Arreglos</a></h1>
<p>En este apartado podras encotrar los los resultador de la prueba de arreglos</p>
</body>
</html>