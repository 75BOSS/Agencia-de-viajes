<?php if (session_status()===PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'ToursEC - Descubre Ecuador' ?></title>
    <meta name="description" content="<?= isset($page_description) ? $page_description : 'Descubre los destinos más increíbles del Ecuador con ToursEC' ?>">
    <!--
      Utilizamos una ruta absoluta para el CSS para asegurar que
      los estilos se carguen correctamente sin importar la profundidad
      de la URL actual. Al comenzar con una barra `/` el navegador
      interpreta la ruta respecto al dominio (e.g. https://tu-dominio.com/assets/css/styles.css).
    -->
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
  <div class="nav-wrap container">
    <!--
      Enlaces del menú apuntan a rutas absolutas (`/`) para evitar
      problemas con rutas relativas al usar includes desde distintos
      directorios. Esto garantiza que siempre se navegue desde la raíz.
    -->
    <a class="brand" href="/">ToursEC</a>
    <nav class="nav-desktop">
      <a href="/">Inicio</a>
      <a href="/destinos.php">Destinos</a>
      <a href="#tours">Tours</a>
      <a href="/reserva.php">Contacto</a>
      <a class="admin-link" href="/admin/login.php">🔒 Admin</a>
    </nav>
    <button id="btnMenu" class="nav-burger" aria-label="Menú">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<div id="overlay" class="overlay"></div>
<aside id="sheet" class="sheet" aria-hidden="true">
  <div class="sheet-handle"></div>
  <a href="/">INICIO</a>
  <a href="/destinos.php">DESTINOS</a>
  <a href="#tours">TOURS</a>
  <a href="/reserva.php">CONTACTO</a>
  <a href="/admin/login.php">ADMIN</a>
  <button id="btnCloseSheet" class="sheet-close">✕</button>
</aside>
