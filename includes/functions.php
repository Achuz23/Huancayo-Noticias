<?php
/**
 * Funciones auxiliares: seguridad, sesión, formato.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/../config/db.php';

/** Escapa texto para HTML (previene XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Token CSRF de la sesión. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    if (!isset($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(403);
        exit('Solicitud no válida (token CSRF).');
    }
}

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function requiere_login(): void
{
    if (!usuario_actual()) {
        header('Location: ../login.php');
        exit;
    }
}

/** Mensajes flash (se muestran una sola vez). */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function mostrar_flash(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $rol = $f['tipo'] === 'error' ? 'alert' : 'status';
        $html .= '<div class="alerta alerta-' . e($f['tipo']) . '" role="' . $rol . '">' . e($f['mensaje']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/** Genera un slug amigable para URL a partir del título. */
function slugify(string $texto): string
{
    $texto = strtr($texto, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
                            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ñ'=>'n','Ü'=>'u']);
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-') ?: 'noticia';
}

/** Fecha en formato peruano legible. */
function fecha(string $fecha): string
{
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto',
              'septiembre','octubre','noviembre','diciembre'];
    $t = strtotime($fecha);
    return date('j', $t) . ' de ' . $meses[(int)date('n', $t) - 1] . ' de ' . date('Y', $t);
}

/** Minutos estimados de lectura (200 palabras por minuto). */
function tiempo_lectura(string $texto): int
{
    return max(1, (int)ceil(str_word_count(strip_tags($texto)) / 200));
}

/** Resumen corto del contenido. */
function resumen(string $texto, int $largo = 160): string
{
    $texto = trim(preg_replace('/\s+/', ' ', strip_tags($texto)));
    if (mb_strlen($texto) <= $largo) {
        return $texto;
    }
    return rtrim(mb_substr($texto, 0, $largo)) . '…';
}

/** Convierte el texto plano en párrafos HTML seguros. */
function parrafos(string $texto): string
{
    $bloques = preg_split('/\R{2,}/', trim($texto));
    $html = '';
    foreach ($bloques as $b) {
        $html .= '<p>' . nl2br(e($b)) . '</p>';
    }
    return $html;
}

/**
 * Valida y guarda una imagen subida. Devuelve el nombre del archivo o null.
 * Lanza RuntimeException con un mensaje claro si hay error.
 */
function guardar_imagen(array $archivo): ?string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen.');
    }
    if ($archivo['size'] > MAX_IMG_SIZE) {
        throw new RuntimeException('La imagen supera el tamaño máximo de 2 MB.');
    }
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $info = @getimagesize($archivo['tmp_name']);
    $mime = $info['mime'] ?? '';
    if (!isset($permitidos[$mime])) {
        throw new RuntimeException('Formato no permitido. Usa JPG, PNG, WEBP o GIF.');
    }
    $nombre = bin2hex(random_bytes(8)) . '.' . $permitidos[$mime];
    $destino = __DIR__ . '/../uploads/' . $nombre;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }
    return $nombre;
}

function categorias(): array
{
    return db()->query('SELECT id, nombre, slug FROM categorias ORDER BY nombre')->fetchAll();
}
