<?php
/**
 * Cabecera común. Variables esperadas:
 *   $titulo  - título de la página
 *   $base    - ruta relativa a la raíz ('' en raíz, '../' en /admin)
 *   $descripcion (opcional)
 */
$base = $base ?? '';
$titulo = $titulo ?? SITE_NAME;
$descripcion = $descripcion ?? 'Noticias de Huancayo y la región Junín, actualizadas cada día.';
$u = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($descripcion) ?>">
    <meta name="theme-color" content="#0f4c5c">
    <title><?= e($titulo) ?> | <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <script>
        // Aplica preferencias guardadas antes de pintar (evita parpadeo).
        (function () {
            try {
                var t = localStorage.getItem('tema');
                var f = localStorage.getItem('fuente');
                if (t) document.documentElement.setAttribute('data-tema', t);
                if (f) document.documentElement.style.fontSize = f + '%';
            } catch (e) {}
        })();
    </script>
    <script src="<?= $base ?>assets/js/app.js" defer></script>
</head>
<body>
<a class="saltar" href="#principal">Saltar al contenido principal</a>

<header class="cabecera">
    <div class="contenedor cabecera-fila">
        <a class="logo" href="<?= $base ?>index.php" aria-label="<?= e(SITE_NAME) ?>, ir al inicio">
            <span class="logo-marca" aria-hidden="true">HN</span>
            <span class="logo-texto"><?= e(SITE_NAME) ?></span>
        </a>

        <div class="herramientas" role="group" aria-label="Opciones de accesibilidad">
            <button type="button" class="btn-icono" data-fuente="-" aria-label="Reducir tamaño de texto">A−</button>
            <button type="button" class="btn-icono" data-fuente="+" aria-label="Aumentar tamaño de texto">A+</button>
            <button type="button" class="btn-icono" id="btn-tema" aria-pressed="false" aria-label="Activar modo oscuro">
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </button>
        </div>

        <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="menu-principal">
            Menú
        </button>
        <nav id="menu-principal" class="menu" aria-label="Navegación principal">
            <ul>
                <li><a href="<?= $base ?>index.php">Inicio</a></li>
                <?php if ($u): ?>
                    <li><a href="<?= $base ?>admin/index.php">Panel</a></li>
                    <li><a href="<?= $base ?>logout.php">Cerrar sesión (<?= e($u['nombre']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="<?= $base ?>login.php">Administrar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main id="principal" class="contenedor" tabindex="-1">
<?= mostrar_flash() ?>
