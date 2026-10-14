<?php
require_once __DIR__ . '/../includes/functions.php';
requiere_login();

// Eliminar noticia (solo por POST con token CSRF).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    verificar_csrf();
    $id = (int)$_POST['eliminar'];
    $st = db()->prepare('SELECT imagen FROM noticias WHERE id = ?');
    $st->execute([$id]);
    $img = $st->fetchColumn();
    db()->prepare('DELETE FROM comentarios WHERE noticia_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM noticias WHERE id = ?')->execute([$id]);
    if ($img && is_file(__DIR__ . '/../uploads/' . $img)) {
        unlink(__DIR__ . '/../uploads/' . $img);
    }
    flash('ok', 'La noticia fue eliminada.');
    header('Location: index.php');
    exit;
}

$stats = [
    'publicadas' => db()->query("SELECT COUNT(*) FROM noticias WHERE estado = 'publicada'")->fetchColumn(),
    'borradores' => db()->query("SELECT COUNT(*) FROM noticias WHERE estado = 'borrador'")->fetchColumn(),
    'pendientes' => db()->query("SELECT COUNT(*) FROM comentarios WHERE estado = 'pendiente'")->fetchColumn(),
    'vistas'     => db()->query('SELECT COALESCE(SUM(vistas),0) FROM noticias')->fetchColumn(),
];

$noticias = db()->query(
    'SELECT n.id, n.titulo, n.estado, n.destacada, n.vistas, n.creado_en, c.nombre AS categoria
     FROM noticias n JOIN categorias c ON c.id = n.categoria_id
     ORDER BY n.creado_en DESC'
)->fetchAll();

$base = '../';
$titulo = 'Panel de administración';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-cabecera">
    <h1>Panel de administración</h1>
    <nav aria-label="Secciones del panel" class="admin-menu">
        <a class="btn" href="editar.php">+ Nueva noticia</a>
        <a class="btn btn-sec" href="categorias.php">Categorías</a>
        <a class="btn btn-sec" href="comentarios.php">Comentarios<?= $stats['pendientes'] ? ' (' . (int)$stats['pendientes'] . ')' : '' ?></a>
    </nav>
</div>

<ul class="estadisticas" aria-label="Resumen">
    <li><strong><?= (int)$stats['publicadas'] ?></strong> publicadas</li>
    <li><strong><?= (int)$stats['borradores'] ?></strong> borradores</li>
    <li><strong><?= (int)$stats['pendientes'] ?></strong> comentarios pendientes</li>
    <li><strong><?= (int)$stats['vistas'] ?></strong> lecturas totales</li>
</ul>

<div class="tabla-envoltura" tabindex="0" role="region" aria-label="Lista de noticias">
<table class="tabla">
    <caption>Todas las noticias (<?= count($noticias) ?>)</caption>
    <thead>
        <tr><th scope="col">Título</th><th scope="col">Categoría</th><th scope="col">Estado</th>
            <th scope="col">Lecturas</th><th scope="col">Fecha</th><th scope="col">Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($noticias as $n): ?>
        <tr>
            <th scope="row"><a href="../noticia.php?id=<?= (int)$n['id'] ?>"><?= e($n['titulo']) ?></a>
                <?= $n['destacada'] ? '<span class="etiqueta etiqueta-dest">Destacada</span>' : '' ?></th>
            <td><?= e($n['categoria']) ?></td>
            <td><span class="estado estado-<?= e($n['estado']) ?>"><?= $n['estado'] === 'publicada' ? 'Publicada' : 'Borrador' ?></span></td>
            <td><?= (int)$n['vistas'] ?></td>
            <td><?= e(date('d/m/Y', strtotime($n['creado_en']))) ?></td>
            <td class="acciones">
                <a class="btn btn-peq btn-sec" href="editar.php?id=<?= (int)$n['id'] ?>">Editar<span class="visualmente-oculto"> «<?= e($n['titulo']) ?>»</span></a>
                <form method="post" action="index.php" data-confirmar="¿Eliminar la noticia «<?= e($n['titulo']) ?>»? Esta acción no se puede deshacer.">
                    <?= csrf_field() ?>
                    <button type="submit" name="eliminar" value="<?= (int)$n['id'] ?>" class="btn btn-peq btn-peligro">Eliminar<span class="visualmente-oculto"> «<?= e($n['titulo']) ?>»</span></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$noticias): ?><tr><td colspan="6">Todavía no hay noticias. <a href="editar.php">Crea la primera</a>.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
