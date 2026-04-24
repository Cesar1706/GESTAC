<?php
require_once __DIR__ . '/../configuracion/base_datos.php';

$accion = $_GET['accion'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'];

// ===================== EXPORTAR EXCEL =====================
if ($metodo === 'GET' && $accion === 'exportar_excel') {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=reporte_usuarios_asignados.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $pdo   = obtenerConexion();
    $stmt  = $pdo->query("SELECT nomina, nombre, ubicacion, activo, fecha FROM usuarios");
    $filas = $stmt->fetchAll();

    echo generarExcelHtml('REPORTE DE USUARIOS CON ACTIVOS ASIGNADOS',
        ['Nómina', 'Nombre', 'Ubicación', 'Activo Asignado', 'Fecha'],
        $filas,
        ['nomina', 'nombre', 'ubicacion', 'activo', 'fecha']
    );
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = obtenerConexion();

    // ===================== ACTUALIZAR USUARIO (POST) =====================
    if ($metodo === 'POST') {
        $nomina    = $_POST['nomina']    ?? '';
        $nombre    = $_POST['nombre']    ?? '';
        $ubicacion = $_POST['ubicacion'] ?? '';
        $activo    = $_POST['activo']    ?? '';
        $fecha     = $_POST['fecha']     ?? '';

        if (empty($nomina)) {
            echo json_encode(['exito' => false, 'mensaje' => 'No se proporcionó la nómina del usuario.']);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE usuarios SET nombre = ?, ubicacion = ?, activo = ?, fecha = ? WHERE nomina = ?"
        );
        $stmt->execute([$nombre, $ubicacion, $activo, $fecha, $nomina]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['exito' => true, 'mensaje' => 'Usuario actualizado correctamente']);
        } else {
            echo json_encode(['exito' => false, 'mensaje' => 'No se encontró el usuario con esa nómina']);
        }
        exit;
    }

    // ===================== LISTAR USUARIOS (GET) =====================
    $stmt    = $pdo->query("SELECT nomina, nombre, ubicacion, activo, fecha FROM usuarios");
    $usuarios = $stmt->fetchAll();

    echo json_encode(['exito' => true, 'mensaje' => 'Datos obtenidos correctamente', 'datos' => $usuarios]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error en el servidor']);
}

// ===================== FUNCIÓN AUXILIAR EXCEL =====================
function generarExcelHtml(string $titulo, array $encabezados, array $filas, array $columnas): string {
    $html = '
    <html><head><meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        th { background-color: #2f6b2f; color: white; padding: 8px; border: 1px solid black; text-align: center; }
        td { border: 1px solid black; padding: 6px; }
        tr:nth-child(even) { background-color: #e6ffe6; }
        .titulo { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 10px; }
    </style></head><body>
    <div class="titulo">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</div>
    <table><tr>';

    foreach ($encabezados as $enc) {
        $html .= '<th>' . htmlspecialchars($enc, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    $html .= '</tr>';

    foreach ($filas as $fila) {
        $html .= '<tr>';
        foreach ($columnas as $col) {
            $html .= '<td>' . htmlspecialchars((string) ($fila[$col] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</table></body></html>';
    return $html;
}
