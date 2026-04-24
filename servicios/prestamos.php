<?php
require_once __DIR__ . '/../configuracion/base_datos.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = obtenerConexion();

    // Obtener usuarios
    $usuarios = [];
    $stmt = $pdo->query(
        "SELECT nombre FROM usuarios WHERE nombre IS NOT NULL AND nombre <> '' ORDER BY nombre ASC"
    );
    foreach ($stmt->fetchAll() as $fila) {
        $usuarios[] = $fila['nombre'];
    }

    // Obtener periféricos (nombre + número de serie)
    $perifericos = [];
    $stmt = $pdo->query(
        "SELECT `nombre`, `S/N` AS sn FROM `inventario_perifericos`
         WHERE `nombre` IS NOT NULL AND `nombre` <> ''
           AND `S/N` IS NOT NULL AND `S/N` <> ''
         ORDER BY `nombre` ASC, `S/N` ASC"
    );
    foreach ($stmt->fetchAll() as $fila) {
        $perifericos[] = ['nombre' => $fila['nombre'], 'sn' => (string) $fila['sn']];
    }

    echo json_encode(
        ['exito' => true, 'usuarios' => $usuarios, 'perifericos' => $perifericos],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['exito' => false, 'usuarios' => [], 'perifericos' => [], 'error' => $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}
