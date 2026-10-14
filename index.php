<?php
require_once __DIR__ . '/includes/functions.php';

$q   = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');
$pag = max(1, (int)($_GET['pag'] ?? 1));

$where  = ["n.estado = 'publicada'"];
$params = [];
if ($q !== '') {
    $where[] = '(n.titulo LIKE ? OR n.contenido LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$catActual = null;
if ($cat !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $cat;
    $st = db()->prepare('SELECT nombre FROM categorias WHERE slug = ?');
    $st->execute([$cat]);
    $catActual = $st->fetchColumn() ?: null;
}
$sqlWhere = implode(' AND ', $where);

$st = db()->prepare("SELECT COUNT(*) FROM noticias n JOIN categorias c ON c.id = n.categoria_id WHERE $sqlWhere");
$st->execute($params);
$total = (int)$st->fetchColumn();
$paginas = max(1, (int)ceil($total / PER_PAGE));
$pag = min($pag, $paginas);
$offset = ($pag - 1) * PER_PAGE;

$st = db()->prepare(
    "SELECT n.id, n.titulo, n.slug, n.contenido, n.imagen, n.imagen_alt, n.destacada, n.vistas, n.creado_en,
            c.nombre AS categoria, c.slug AS cat_slug, u.nombre AS autor
     FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     JOIN usuarios u ON u.id = n.autor_id
     WHERE $sqlWhere
     ORDER BY n.destacada DESC, n.creado_en DESC
     LIMIT " . PER_PAGE . " OFFSET $offset"
);
$st->execute($params);
$noticias = $st->fetchAll();

$populares = db()->query(
    "SELECT titulo, id, vistas FROM noticias WHERE estado = 'publicada' ORDER BY vistas DESC LIMIT 5"
)->fetchAll();

$titulo = $catActual ? "Noticias de $catActual" : ($q !== '' ? "Resultados para \"$q\"" : 'Últimas noticias');
require __DIR__ . '/includes/header.php';

function url_pag(int $p, string $q, string $cat): string
{
    return 'index.php?' . http_build_query(array_filter(['q' => $q, 'cat' => $cat, 'pag' => $p > 1 ? $p : null]));
}
?>

<section class="buscador" aria-label="Buscar y filtrar noticias">
    <form method="get" action="index.php" role="search" class="form-busqueda">
        <label for="q" class="visualmente-oculto">Buscar noticias</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Buscar noticias…" maxlength="100">
        <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
        <button type="submit" class="btn">Buscar</button>
    </form>
    <nav aria-label="Categorías">
        <ul class="chips">
            <li><a href="index.php" <?= $cat === '' ? 'aria-current="page"' : '' ?>>Todas</a></li>
            <?php foreach (categorias() as $c): ?>
                <li><a href="index.php?cat=<?= e($c['slug']) ?>" <?= $cat === $c['slug'] ? 'aria-current="page"' : '' ?>><?= e($c['nombre']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
</section>

<div class="layout">
    <section aria-labelledby="titulo-lista">
        <h1 id="titulo-lista"><?= e($titulo) ?></h1>
        <p class="contador" aria-live="polite"><?= $total ?> <?= $total === 1 ? 'noticia encontrada' : 'noticias encontradas' ?></p>

        <?php if (!$noticias): ?>
            <div class="vacio">
                <p>No encontramos noticias con esos criterios.</p>
                <a class="btn btn-sec" href="index.php">Ver todas las noticias</a>
            </div>
        <?php else: ?>
            <div class="grilla">
                <?php foreach ($noticias as $i => $n): ?>
                    <article class="tarjeta<?= $n['destacada'] ? ' destacada' : '' ?>">
                        <?php if ($n['imagen']): ?>
                            <img src="uploads/<?= e($n['imagen']) ?>" alt="<?= e($n['imagen_alt']) ?>"
                                 width="640" height="360" <?= $i > 1 ? 'loading="lazy"' : '' ?> decoding="async">
                        <?php else: ?>
                            <div class="sin-imagen" aria-hidden="true"><?= e(mb_substr($n['categoria'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <div class="tarjeta-cuerpo">
                            <p class="meta">
                                <a class="etiqueta" href="index.php?cat=<?= e($n['cat_slug']) ?>"><?= e($n['categoria']) ?></a>
                                <?php if ($n['destacada']): ?><span class="etiqueta etiqueta-dest">Destacada</span><?php endif; ?>
                            </p>
                            <h2><a href="noticia.php?id=<?= (int)$n['id'] ?>"><?= e($n['titulo']) ?></a></h2>
                            <p><?= e(resumen($n['contenido'])) ?></p>
                            <p class="meta-pie">
                                <time datetime="<?= e(date('Y-m-d', strtotime($n['creado_en']))) ?>"><?= fecha($n['creado_en']) ?></time>
                                · <?= tiempo_lectura($n['contenido']) ?> min de lectura
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($paginas > 1): ?>
                <nav class="paginacion" aria-label="Paginación">
                    <?php if ($pag > 1): ?><a href="<?= e(url_pag($pag - 1, $q, $cat)) ?>" rel="prev">← Anterior</a><?php endif; ?>
                    <?php for ($p = 1; $p <= $paginas; $p++): ?>
                        <a href="<?= e(url_pag($p, $q, $cat)) ?>" <?= $p === $pag ? 'aria-current="page"' : '' ?>
                           aria-label="Página <?= $p ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($pag < $paginas): ?><a href="<?= e(url_pag($pag + 1, $q, $cat)) ?>" rel="next">Siguiente →</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <aside class="lateral" aria-labelledby="titulo-populares">
        <h2 id="titulo-populares">Más leídas</h2>
        <ol class="populares">
            <?php foreach ($populares as $p): ?>
                <li><a href="noticia.php?id=<?= (int)$p['id'] ?>"><?= e($p['titulo']) ?></a>
                    <span class="meta-pie"><?= (int)$p['vistas'] ?> lecturas</span></li>
            <?php endforeach; ?>
        </ol>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
