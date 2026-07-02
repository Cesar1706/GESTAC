<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function estaAutenticado(): bool {
    return !empty($_SESSION['usuario']) && !empty($_SESSION['rol']);
}

function esAdmin(): bool {
    return estaAutenticado() && $_SESSION['rol'] === 'admin';
}

function requerirAutenticacion(): void {
    if (!estaAutenticado()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['exito' => false, 'mensaje' => 'No autenticado']);
        exit;
    }
}

function requerirAdmin(): void {
    requerirAutenticacion();
    if (!esAdmin()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['exito' => false, 'mensaje' => 'Acceso denegado']);
        exit;
    }
}
