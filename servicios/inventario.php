<?php
require_once __DIR__ . '/../configuracion/base_datos.php';
header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'listar';

try {
    $pdo = obtenerConexion();

    // ===================== EXPORTAR EXCEL =====================
    if ($metodo === 'GET' && $accion === 'exportar_excel') {
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=inventario_perifericos.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        $stmt = $pdo->query("SELECT `nombre`, `S/N` AS sn FROM `inventario_perifericos` ORDER BY `nombre`");
        $filas = $stmt->fetchAll();

        echo '
        <html><head><meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; width: 100%; font-family: Arial; }
            th { background-color: #2f6b2f; color: white; padding: 8px; border: 1px solid black; text-align: center; }
            td { border: 1px solid black; padding: 6px; }
            tr:nth-child(even) { background-color: #e6ffe6; }
            .titulo { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 10px; }
        </style></head><body>
        <div class="titulo">REPORTE INVENTARIO DE PERIFÉRICOS</div>
        <table><tr><th>Nombre</th><th>S/N</th></tr>';

        foreach ($filas as $fila) {
            echo '<tr>
                <td>' . htmlspecialchars((string) ($fila['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars((string) ($fila['sn'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    // ===================== LISTAR =====================
    if ($metodo === 'GET' && $accion === 'listar') {
        $stmt = $pdo->query(
            "SELECT `id`, `nombre`, `S/N` AS sn FROM `inventario_perifericos` ORDER BY `nombre` ASC"
        );
        echo json_encode(['exito' => true, 'datos' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===================== INSERTAR =====================
    if ($metodo === 'POST' && $accion === 'insertar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $sn     = trim($_POST['sn']     ?? '');

        if ($nombre === '' || $sn === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan campos obligatorios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO `inventario_perifericos` (`nombre`, `S/N`) VALUES (?, ?)");
        $stmt->execute([$nombre, $sn]);

        echo json_encode(['exito' => true, 'mensaje' => 'Insertado correctamente'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===================== ELIMINAR =====================
    if ($metodo === 'POST' && $accion === 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['exito' => false, 'mensaje' => 'ID inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM `inventario_perifericos` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);

        echo json_encode(['exito' => true, 'mensaje' => 'Eliminado correctamente'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['exito' => false, 'mensaje' => 'Acción no soportada.'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error en el servidor'], JSON_UNESCAPED_UNICODE);
}
