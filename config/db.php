<?php
/**
 * Configuración de la conexión a la base de datos (XAMPP por defecto).
 * Usuario "root" sin contraseña es la configuración estándar de XAMPP.
 */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'blog_noticias');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'Huancayo Noticias');
define('PER_PAGE', 6);                 // noticias por página
define('MAX_IMG_SIZE', 2 * 1024 * 1024); // 2 MB

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    // Permite pruebas automatizadas con SQLite sin cambiar el código.
    $dsn = getenv('BLOG_DSN');
    try {
        if ($dsn) {
            $pdo = new PDO($dsn);
        } else {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS
            );
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('<p style="font-family:sans-serif">No se pudo conectar a la base de datos. '
            . 'Verifica que MySQL esté encendido en XAMPP y que hayas importado <code>database.sql</code>.</p>');
    }
    return $pdo;
}
