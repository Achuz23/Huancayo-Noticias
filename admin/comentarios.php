<?php
require_once __DIR__ . '/../includes/functions.php';
requiere_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'aprobar') {
        db()->prepare("UPDATE comentarios SET estado = 'aprobado' WHERE id = ?")->execute([$id]);
        flash('ok', 'Comentario aprobado y publicado.');
    } elseif ($accion === 'eliminar') {
        db()->prepare('DELETE FROM comentarios WHERE id = ?')->execute([$id]);
        flash('ok', 'Comentario eliminado.');
    }
    header('Location: comentarios.php');
    exit;
}

$lista = db()->query(
    "SELECT co.id, co.nombre, co.comentario, co.estado, co.creado_en, n.titulo, n.id AS noticia_id
     FROM comentarios co JOIN noticias n ON n.id = co.noticia_id
     ORDER BY co.estado DESC, co.creado_en DESC"
)->fetchAll();

$base = '../';
$titulo = 'Moderar comentarios';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="index.php">← Volver al panel</a></p>
<h1>Moderar comentarios</h1>

<?php if (!$lista): ?><p>No hay comentarios todavía.</p><?php endif; ?>
<?php foreach ($lista as $c): ?>
    <div class="comentario comentario-<?= e($c['estado']) ?>">
        <p class="meta-pie">
            <span class="estado estado-<?= e($c['estado']) ?>"><?= $c['estado'] === 'aprobado' ? 'Aprobado' : 'Pendiente' ?></span>
            <strong><?= e($c['nombre']) ?></strong> en
            <a href="../noticia.php?id=<?= (int)$c['noticia_id'] ?>"><?= e($c['titulo']) ?></a>
            · <?= e(date('d/m/Y H:i', strtotime($c['creado_en']))) ?>
        </p>
        <p><?= nl2br(e($c['comentario'])) ?></p>
        <form method="post" class="acciones">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <?php if ($c['estado'] === 'pendiente'): ?>
                <button type="submit" name="accion" value="aprobar" class="btn btn-peq">Aprobar</button>
            <?php endif; ?>
            <button type="submit" name="accion" value="eliminar" class="btn btn-peq btn-peligro">Eliminar</button>
        </form>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
