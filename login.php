<?php
require_once __DIR__ . '/includes/functions.php';

if (usuario_actual()) {
    header('Location: admin/index.php');
    exit;
}

$error = '';
$usuario = '';
$_SESSION['intentos'] = $_SESSION['intentos'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if ($_SESSION['intentos'] >= 5 && time() - ($_SESSION['ultimo_intento'] ?? 0) < 60) {
        $error = 'Demasiados intentos fallidos. Espera un minuto e inténtalo de nuevo.';
    } elseif ($usuario === '' || $clave === '') {
        $error = 'Ingresa tu usuario y contraseña.';
    } else {
        $st = db()->prepare('SELECT id, nombre, usuario, clave, rol FROM usuarios WHERE usuario = ?');
        $st->execute([$usuario]);
        $u = $st->fetch();
        if ($u && password_verify($clave, $u['clave'])) {
            session_regenerate_id(true);
            unset($u['clave']);
            $_SESSION['usuario'] = $u;
            $_SESSION['intentos'] = 0;
            flash('ok', 'Bienvenido, ' . $u['nombre'] . '.');
            header('Location: admin/index.php');
            exit;
        }
        $_SESSION['intentos']++;
        $_SESSION['ultimo_intento'] = time();
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<div class="caja-login">
    <h1>Iniciar sesión</h1>
    <p>Acceso para redactores y administradores del blog.</p>
    <?php if ($error): ?><div class="alerta alerta-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="login.php" class="formulario">
        <?= csrf_field() ?>
        <div class="campo">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required autocomplete="username" value="<?= e($usuario) ?>" autofocus>
        </div>
        <div class="campo">
            <label for="clave">Contraseña</label>
            <div class="campo-clave">
                <input type="password" id="clave" name="clave" required autocomplete="current-password">
                <button type="button" class="btn-icono" id="ver-clave" aria-pressed="false" aria-controls="clave">Mostrar</button>
            </div>
        </div>
        <button type="submit" class="btn btn-bloque">Ingresar</button>
    </form>
    <p class="ayuda">Usuario de prueba: <strong>admin</strong> / contraseña: <strong>admin123</strong></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
