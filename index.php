<?php
require_once __DIR__ . '/configuracion/session_check.php';

if (!estaAutenticado()) {
    header('Location: HTML/login.html');
    exit;
}

$destino = esAdmin() ? 'HTML/menu_admin.html' : 'HTML/menu_user.html';
header("Location: $destino");
exit;
