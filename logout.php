<?php
require_once __DIR__ . '/includes/functions.php';
$_SESSION = [];
session_destroy();
session_start();
flash('ok', 'Cerraste sesión correctamente.');
header('Location: index.php');
exit;
