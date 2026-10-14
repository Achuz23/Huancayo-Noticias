<?php
require_once __DIR__ . '/../includes/functions.php';
requiere_login();

$error = '';
$nombre = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    if (isset($_POST['eliminar'])) {
        $id = (int)$_POST['eliminar'];
        $st = db()->prepare('SELECT COUNT(*) FROM noticias WHERE categoria_id = ?');
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) {
            flash('error', 'No se puede eliminar: la categoría tiene noticias asociadas.');
        } else {
            db()->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
            flash('ok', 'Categoría eliminada.');
        }
        header('Location: categorias.php');
        exit;
    }
    $nombre = trim($_POST['nombre'] ?? '');
    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 50) {
        $error = 'El nombre debe tener entre 3 y 50 caracteres.';
    } else {
        $slug = slugify($nombre);
        $st = db()->prepare('SELECT COUNT(*) FROM categorias WHERE slug = ?');
        $st->execute([$slug]);
        if ($st->fetchColumn()) {
            $error = 'Ya existe una categoría con ese nombre.';
        } else {
            db()->prepare('INSERT INTO categorias (nombre, slug) VALUES (?, ?)')->execute([$nombre, $slug]);
            flash('ok', 'Categoría «' . $nombre . '» creada.');
            header('Location: categorias.php');
            exit;
        }
    }
}

$lista = db()->query('SELECT c.id, c.nombre, COUNT(n.id) AS total FROM categorias c
                      LEFT JOIN noticias n ON n.categoria_id = c.id GROUP BY c.id, c.nombre ORDER BY c.nombre')->fetchAll();

$base = '../';
$titulo = 'Categorías';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="index.php">← Volver al panel</a></p>
<h1>Categorías</h1>

<form method="post" class="formulario form-linea">
    <?= csrf_field() ?>
    <div class="campo">
        <label for="nombre">Nueva categoría</label>
        <input type="text" id="nombre" name="nombre" maxlength="50" required value="<?= e($nombre) ?>"
               <?= $error ? 'aria-invalid="true" aria-describedby="err-nombre"' : '' ?>>
        <?php if ($error): ?><p class="error" id="err-nombre" role="alert"><?= e($error) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn">Agregar</button>
</form>

<table class="tabla">
    <caption>Categorías existentes</caption>
    <thead><tr><th scope="col">Nombre</th><th scope="col">Noticias</th><th scope="col">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $c): ?>
        <tr>
            <th scope="row"><?= e($c['nombre']) ?></th>
            <td><?= (int)$c['total'] ?></td>
            <td>
                <form method="post" data-confirmar="¿Eliminar la categoría «<?= e($c['nombre']) ?>»?">
                    <?= csrf_field() ?>
                    <button type="submit" name="eliminar" value="<?= (int)$c['id'] ?>" class="btn btn-peq btn-peligro">Eliminar<span class="visualmente-oculto"> <?= e($c['nombre']) ?></span></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
