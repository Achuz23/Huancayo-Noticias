<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare(
    "SELECT n.*, c.nombre AS categoria, c.slug AS cat_slug, u.nombre AS autor
     FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     JOIN usuarios u ON u.id = n.autor_id
     WHERE n.id = ? AND (n.estado = 'publicada' OR ? = 1)"
);
$st->execute([$id, usuario_actual() ? 1 : 0]);
$n = $st->fetch();

if (!$n) {
    http_response_code(404);
    $titulo = 'Noticia no encontrada';
    require __DIR__ . '/includes/header.php';
    echo '<div class="vacio"><h1>Noticia no encontrada</h1><p>La noticia que buscas no existe o fue retirada.</p>'
       . '<a class="btn" href="index.php">Volver al inicio</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Comentario nuevo (queda pendiente de moderación).
$errores = [];
$datos = ['nombre' => '', 'comentario' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $datos['nombre'] = trim($_POST['nombre'] ?? '');
    $datos['comentario'] = trim($_POST['comentario'] ?? '');
    // Campo trampa anti-spam: los humanos no lo ven ni lo llenan.
    if (!empty($_POST['web'])) {
        header("Location: noticia.php?id=$id");
        exit;
    }
    if (mb_strlen($datos['nombre']) < 2 || mb_strlen($datos['nombre']) > 60) {
        $errores['nombre'] = 'Escribe tu nombre (entre 2 y 60 caracteres).';
    }
    if (mb_strlen($datos['comentario']) < 3 || mb_strlen($datos['comentario']) > 1000) {
        $errores['comentario'] = 'El comentario debe tener entre 3 y 1000 caracteres.';
    }
    if (!$errores) {
        $ins = db()->prepare("INSERT INTO comentarios (noticia_id, nombre, comentario, estado) VALUES (?, ?, ?, 'pendiente')");
        $ins->execute([$id, $datos['nombre'], $datos['comentario']]);
        flash('ok', 'Gracias. Tu comentario se publicará cuando sea aprobado por un moderador.');
        header("Location: noticia.php?id=$id#comentarios");
        exit;
    }
} else {
    // Contador de lecturas (una vez por sesión por noticia).
    if (empty($_SESSION['vistas'][$id])) {
        db()->prepare('UPDATE noticias SET vistas = vistas + 1 WHERE id = ?')->execute([$id]);
        $_SESSION['vistas'][$id] = true;
        $n['vistas']++;
    }
}

$st = db()->prepare("SELECT nombre, comentario, creado_en FROM comentarios WHERE noticia_id = ? AND estado = 'aprobado' ORDER BY creado_en");
$st->execute([$id]);
$comentarios = $st->fetchAll();

$st = db()->prepare("SELECT id, titulo FROM noticias WHERE categoria_id = ? AND id <> ? AND estado = 'publicada' ORDER BY creado_en DESC LIMIT 3");
$st->execute([$n['categoria_id'], $id]);
$relacionadas = $st->fetchAll();

$titulo = $n['titulo'];
$descripcion = resumen($n['contenido'], 150);
require __DIR__ . '/includes/header.php';
?>

<nav aria-label="Ruta de navegación" class="migas">
    <ol>
        <li><a href="index.php">Inicio</a></li>
        <li><a href="index.php?cat=<?= e($n['cat_slug']) ?>"><?= e($n['categoria']) ?></a></li>
        <li aria-current="page"><?= e(resumen($n['titulo'], 40)) ?></li>
    </ol>
</nav>

<article class="articulo">
    <header>
        <p class="meta"><a class="etiqueta" href="index.php?cat=<?= e($n['cat_slug']) ?>"><?= e($n['categoria']) ?></a>
            <?php if ($n['estado'] !== 'publicada'): ?><span class="etiqueta etiqueta-borrador">Borrador</span><?php endif; ?></p>
        <h1><?= e($n['titulo']) ?></h1>
        <p class="meta-pie">
            Por <strong><?= e($n['autor']) ?></strong> ·
            <time datetime="<?= e(date('Y-m-d', strtotime($n['creado_en']))) ?>"><?= fecha($n['creado_en']) ?></time> ·
            <?= tiempo_lectura($n['contenido']) ?> min de lectura · <?= (int)$n['vistas'] ?> lecturas
        </p>
    </header>

    <?php if ($n['imagen']): ?>
        <figure>
            <img src="uploads/<?= e($n['imagen']) ?>" alt="<?= e($n['imagen_alt']) ?>" width="1200" height="675">
            <?php if ($n['imagen_alt']): ?><figcaption><?= e($n['imagen_alt']) ?></figcaption><?php endif; ?>
        </figure>
    <?php endif; ?>

    <div class="articulo-texto">
        <?= parrafos($n['contenido']) ?>
    </div>

    <div class="compartir">
        <button type="button" class="btn btn-sec" id="btn-leer" aria-pressed="false">🔊 Escuchar noticia</button>
        <button type="button" class="btn btn-sec" id="btn-copiar">Copiar enlace</button>
        <span id="estado-compartir" role="status" class="meta-pie"></span>
    </div>
</article>

<?php if ($relacionadas): ?>
<section class="relacionadas" aria-labelledby="titulo-rel">
    <h2 id="titulo-rel">Noticias relacionadas</h2>
    <ul>
        <?php foreach ($relacionadas as $r): ?>
            <li><a href="noticia.php?id=<?= (int)$r['id'] ?>"><?= e($r['titulo']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section id="comentarios" class="comentarios" aria-labelledby="titulo-com">
    <h2 id="titulo-com">Comentarios (<?= count($comentarios) ?>)</h2>
    <?php foreach ($comentarios as $c): ?>
        <div class="comentario">
            <p class="meta-pie"><strong><?= e($c['nombre']) ?></strong> · <?= fecha($c['creado_en']) ?></p>
            <p><?= nl2br(e($c['comentario'])) ?></p>
        </div>
    <?php endforeach; ?>
    <?php if (!$comentarios): ?><p>Aún no hay comentarios. ¡Sé el primero en opinar!</p><?php endif; ?>

    <form method="post" action="noticia.php?id=<?= $id ?>#comentarios" class="formulario" novalidate>
        <h3>Deja tu comentario</h3>
        <?= csrf_field() ?>
        <div class="trampa" aria-hidden="true">
            <label for="web">No llenar este campo</label>
            <input type="text" id="web" name="web" tabindex="-1" autocomplete="off">
        </div>
        <div class="campo">
            <label for="nombre">Nombre <span aria-hidden="true">*</span></label>
            <input type="text" id="nombre" name="nombre" required maxlength="60" autocomplete="name"
                   value="<?= e($datos['nombre']) ?>"
                   <?= isset($errores['nombre']) ? 'aria-invalid="true" aria-describedby="err-nombre"' : '' ?>>
            <?php if (isset($errores['nombre'])): ?><p class="error" id="err-nombre"><?= e($errores['nombre']) ?></p><?php endif; ?>
        </div>
        <div class="campo">
            <label for="comentario">Comentario <span aria-hidden="true">*</span></label>
            <textarea id="comentario" name="comentario" rows="4" required maxlength="1000"
                      aria-describedby="ayuda-com<?= isset($errores['comentario']) ? ' err-com' : '' ?>"
                      <?= isset($errores['comentario']) ? 'aria-invalid="true"' : '' ?>><?= e($datos['comentario']) ?></textarea>
            <p class="ayuda" id="ayuda-com">Máximo 1000 caracteres. Se publicará luego de ser revisado.</p>
            <?php if (isset($errores['comentario'])): ?><p class="error" id="err-com"><?= e($errores['comentario']) ?></p><?php endif; ?>
        </div>
        <button type="submit" class="btn">Enviar comentario</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
