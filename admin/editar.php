<?php
require_once __DIR__ . '/../includes/functions.php';
requiere_login();

$id = (int)($_GET['id'] ?? 0);
$n = ['titulo' => '', 'contenido' => '', 'categoria_id' => '', 'estado' => 'publicada',
      'destacada' => 0, 'imagen' => null, 'imagen_alt' => ''];

if ($id) {
    $st = db()->prepare('SELECT * FROM noticias WHERE id = ?');
    $st->execute([$id]);
    $n = $st->fetch();
    if (!$n) {
        flash('error', 'La noticia no existe.');
        header('Location: index.php');
        exit;
    }
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $n['titulo']       = trim($_POST['titulo'] ?? '');
    $n['contenido']    = trim($_POST['contenido'] ?? '');
    $n['categoria_id'] = (int)($_POST['categoria_id'] ?? 0);
    $n['estado']       = ($_POST['estado'] ?? '') === 'borrador' ? 'borrador' : 'publicada';
    $n['destacada']    = isset($_POST['destacada']) ? 1 : 0;
    $n['imagen_alt']   = trim($_POST['imagen_alt'] ?? '');

    if (mb_strlen($n['titulo']) < 5 || mb_strlen($n['titulo']) > 150) {
        $errores['titulo'] = 'El título debe tener entre 5 y 150 caracteres.';
    }
    if (mb_strlen($n['contenido']) < 20) {
        $errores['contenido'] = 'El contenido debe tener al menos 20 caracteres.';
    }
    $st = db()->prepare('SELECT COUNT(*) FROM categorias WHERE id = ?');
    $st->execute([$n['categoria_id']]);
    if (!$st->fetchColumn()) {
        $errores['categoria_id'] = 'Selecciona una categoría.';
    }

    $nuevaImagen = null;
    if (!$errores) {
        try {
            $nuevaImagen = guardar_imagen($_FILES['imagen'] ?? []);
        } catch (RuntimeException $ex) {
            $errores['imagen'] = $ex->getMessage();
        }
    }
    $tendraImagen = $nuevaImagen || ($n['imagen'] && !isset($_POST['quitar_imagen']));
    if (!$errores && $tendraImagen && mb_strlen($n['imagen_alt']) < 5) {
        $errores['imagen_alt'] = 'Describe la imagen (texto alternativo) para personas con discapacidad visual.';
        if ($nuevaImagen) {
            @unlink(__DIR__ . '/../uploads/' . $nuevaImagen);
        }
    }

    if (!$errores) {
        $imagenFinal = $n['imagen'];
        if ($nuevaImagen || isset($_POST['quitar_imagen'])) {
            if ($n['imagen'] && is_file(__DIR__ . '/../uploads/' . $n['imagen'])) {
                @unlink(__DIR__ . '/../uploads/' . $n['imagen']);
            }
            $imagenFinal = $nuevaImagen;
        }
        $valores = [$n['titulo'], slugify($n['titulo']), $n['contenido'], $n['categoria_id'],
                    $n['estado'], $n['destacada'], $imagenFinal, $imagenFinal ? $n['imagen_alt'] : ''];
        if ($id) {
            $valores[] = $id;
            db()->prepare('UPDATE noticias SET titulo=?, slug=?, contenido=?, categoria_id=?, estado=?, destacada=?,
                           imagen=?, imagen_alt=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?')->execute($valores);
            flash('ok', 'Noticia actualizada correctamente.');
        } else {
            $valores[] = usuario_actual()['id'];
            db()->prepare('INSERT INTO noticias (titulo, slug, contenido, categoria_id, estado, destacada, imagen, imagen_alt, autor_id)
                           VALUES (?,?,?,?,?,?,?,?,?)')->execute($valores);
            flash('ok', 'Noticia creada correctamente.');
        }
        header('Location: index.php');
        exit;
    }
}

$base = '../';
$titulo = $id ? 'Editar noticia' : 'Nueva noticia';
require __DIR__ . '/../includes/header.php';

function attr_error(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' aria-invalid="true" aria-describedby="err-' . $campo . '"' : '';
}
function msg_error(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? '<p class="error" id="err-' . $campo . '">' . e($errores[$campo]) . '</p>' : '';
}
?>
<p><a href="index.php">← Volver al panel</a></p>
<h1><?= e($titulo) ?></h1>

<?php if ($errores): ?>
    <div class="alerta alerta-error" role="alert" tabindex="-1" id="resumen-errores">
        <p><strong>Revisa <?= count($errores) ?> <?= count($errores) === 1 ? 'campo' : 'campos' ?>:</strong></p>
        <ul><?php foreach ($errores as $campo => $msg): ?><li><a href="#<?= e($campo) ?>"><?= e($msg) ?></a></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="formulario formulario-ancho" novalidate>
    <?= csrf_field() ?>
    <div class="campo">
        <label for="titulo">Título *</label>
        <input type="text" id="titulo" name="titulo" maxlength="150" required value="<?= e($n['titulo']) ?>"<?= attr_error($errores, 'titulo') ?>>
        <?= msg_error($errores, 'titulo') ?>
    </div>

    <div class="fila-campos">
        <div class="campo">
            <label for="categoria_id">Categoría *</label>
            <select id="categoria_id" name="categoria_id" required<?= attr_error($errores, 'categoria_id') ?>>
                <option value="">— Selecciona —</option>
                <?php foreach (categorias() as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)$n['categoria_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= msg_error($errores, 'categoria_id') ?>
        </div>
        <fieldset class="campo">
            <legend>Estado</legend>
            <label class="opcion"><input type="radio" name="estado" value="publicada" <?= $n['estado'] === 'publicada' ? 'checked' : '' ?>> Publicada</label>
            <label class="opcion"><input type="radio" name="estado" value="borrador" <?= $n['estado'] === 'borrador' ? 'checked' : '' ?>> Borrador</label>
        </fieldset>
        <div class="campo">
            <span class="etiqueta-campo">Portada</span>
            <label class="opcion"><input type="checkbox" name="destacada" value="1" <?= $n['destacada'] ? 'checked' : '' ?>> Marcar como destacada</label>
        </div>
    </div>

    <div class="campo">
        <label for="contenido">Contenido *</label>
        <textarea id="contenido" name="contenido" rows="12" required aria-describedby="ayuda-contenido<?= isset($errores['contenido']) ? ' err-contenido' : '' ?>"
            <?= isset($errores['contenido']) ? 'aria-invalid="true"' : '' ?>><?= e($n['contenido']) ?></textarea>
        <p class="ayuda" id="ayuda-contenido">Separa los párrafos con una línea en blanco. <span id="contador-palabras" aria-live="polite"></span></p>
        <?= msg_error($errores, 'contenido') ?>
    </div>

    <div class="campo">
        <label for="imagen">Imagen de portada (opcional)</label>
        <?php if ($n['imagen']): ?>
            <img src="../uploads/<?= e($n['imagen']) ?>" alt="Imagen actual: <?= e($n['imagen_alt']) ?>" class="miniatura" width="240" height="135">
            <label class="opcion"><input type="checkbox" name="quitar_imagen" value="1"> Quitar imagen actual</label>
        <?php endif; ?>
        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="ayuda-imagen"<?= attr_error($errores, 'imagen') ?>>
        <p class="ayuda" id="ayuda-imagen">JPG, PNG, WEBP o GIF. Máximo 2 MB.</p>
        <?= msg_error($errores, 'imagen') ?>
    </div>

    <div class="campo">
        <label for="imagen_alt">Texto alternativo de la imagen</label>
        <input type="text" id="imagen_alt" name="imagen_alt" maxlength="200" value="<?= e($n['imagen_alt']) ?>"
               aria-describedby="ayuda-alt<?= isset($errores['imagen_alt']) ? ' err-imagen_alt' : '' ?>"
               <?= isset($errores['imagen_alt']) ? 'aria-invalid="true"' : '' ?>>
        <p class="ayuda" id="ayuda-alt">Describe lo que muestra la imagen. Lo leen los lectores de pantalla. Obligatorio si hay imagen.</p>
        <?= msg_error($errores, 'imagen_alt') ?>
    </div>

    <div class="botones">
        <button type="submit" class="btn">Guardar noticia</button>
        <a class="btn btn-sec" href="index.php">Cancelar</a>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
