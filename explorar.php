<?php
require __DIR__ . '/auth/sesion.php'; // Guardia de sesión estándar

// Función auxiliar para escapar HTML (equivalente a e())
// function e(?string $texto): string {
//     return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
// }

// Obtener categorías para el filtro (desde tu tabla 'categoria')
require __DIR__ . '/data/conexion.php';
$categorias = [];
$resCat = $mysqli->query('SELECT id, nombre FROM categoria ORDER BY nombre');
if ($resCat) {
    while ($row = $resCat->fetch_assoc()) {
        $categorias[] = $row;
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar · AgendaWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body class="layout">

    <!-- Header estandarizado con menú de usuario -->
    <header class="site-header">
    <div class="contenedor site-header__inner">
        <a href="dashboard.php" class="logo">
        <img src="image/logoPaginaWeb.png" alt="Logotipo formal de la pagina web">Agenda<span>Web</span>
        </a>
        <nav class="nav">
        <a href="explorar.php" class="nav__link <?= basename($_SERVER['PHP_SELF']) === 'explorar.php' ? 'is-active' : '' ?>">Explorar</a>
        <a href="dashboard.php" class="nav__link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'is-active' : '' ?>">Mis eventos</a>
        <a href="registrar.php" class="nav__link <?= basename($_SERVER['PHP_SELF']) === 'registrar.php' ? 'is-active' : '' ?>">Nuevo evento</a>
        
        <!-- ⭐ MENÚ DE USUARIO ESTANDARIZADO -->
        <div class="user-menu">
            <button type="button" class="user-menu__trigger" aria-label="Menú de usuario" aria-expanded="false">
            <?= mb_strtoupper(mb_substr($usuarioNombre, 0, 1)) ?>
            </button>
            
            <div class="user-menu__panel" aria-hidden="true">
            <!-- Cabecera con datos del usuario -->
            <div class="user-menu__header">
                <div class="user-menu__avatar">
                <?= mb_strtoupper(mb_substr($usuarioNombre, 0, 1)) ?>
                </div>
                <div class="user-menu__info">
                <div class="user-menu__name"><?= e($usuarioNombre) ?></div>
                <div class="user-menu__email"><?= e($usuarioCorreo) ?></div>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="user-menu__actions">
                <a href="index.html" class="user-menu__action">
                <!-- ´pendiente -->
                 <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Inicio</span>
                </a>
                <div class="user-menu__divider"></div>
                <a href="auth/logout.php" class="user-menu__action">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Cerrar sesión</span>
                </a>
                
                <div class="user-menu__divider"></div>
                
                <button type="button" class="user-menu__action user-menu__action--danger" id="btnEliminarCuenta">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
                <span>Eliminar cuenta</span>
                </button>
            </div>
            </div>
        </div>
        </nav>
    </div>
    </header>

    <main class="contenedor main-content">
        <div class="page__header">
            <div class="page__titles">
                <h1 class="page__title">Explorar Eventos</h1>
                <!-- Hueco 1: Estado dinámico -->
                <p class="page__subtitle" id="estado" role="status">Cargando eventos…</p>
            </div>
        </div>
        <!-- Hueco 2: Buscador en vivo -->
       <form class="search-formEx
    " id="filtros" role="search">
            <div class="search-wrapperEx">
                <input type="search" id="q" maxlength="60" placeholder="Buscar por título o descripción…" aria-label="Buscar eventos">
                
                <select id="categoria" aria-label="Filtrar por categoría">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= e($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>



        <!-- Hueco 3: Lista de tarjetas (vacía inicialmente) -->
        <section class="card-list" id="lista" aria-label="Eventos" aria-live="polite"></section>
    </main>

    <footer class="site-footer">
        <div class="contenedor">&copy; 2026 AgendaWeb · Elaborado por <b>Cristian Rodríguez</b></div>
    </footer>

    <!-- Scripts -->
    <script src="js/menu-usuario.js"></script>
    <script src="js/explorar.js"></script> <!-- ⭐ Script nuevo al final -->
</body>
</html>