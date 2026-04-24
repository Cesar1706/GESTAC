<?php
require_once __DIR__ . '/../configuracion/base_datos.php';

$accion = $_GET['accion'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'];

// ===================== EXPORTAR EXCEL =====================
if ($metodo === 'GET' && $accion === 'exportar_excel') {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=reporte_activos.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $pdo    = obtenerConexion();
    $stmt   = $pdo->query("SELECT id_producto, nombre, categoria, procesador, ram, almacenamiento, lote, marca, descripcion FROM activos");
    $filas  = $stmt->fetchAll();

    echo generarExcelHtml('REPORTE GENERAL DE ACTIVOS',
        ['ID Producto', 'Nombre', 'Categoría', 'Procesador', 'RAM', 'Almacenamiento', 'Lote', 'Marca', 'Descripción'],
        $filas,
        ['id_producto', 'nombre', 'categoria', 'procesador', 'ram', 'almacenamiento', 'lote', 'marca', 'descripcion']
    );
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = obtenerConexion();

    // ===================== IMPORTACIÓN MASIVA (JSON) =====================
    $datosJson = json_decode(file_get_contents('php://input'), true);
    if ($metodo === 'POST' && !empty($datosJson)) {
        $conteoExito = 0;
        $errores     = [];

        $sql  = "INSERT INTO activos (id_producto, nombre, categoria, procesador, ram, lote)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     nombre = VALUES(nombre),
                     categoria = VALUES(categoria),
                     procesador = VALUES(procesador),
                     ram = VALUES(ram),
                     lote = VALUES(lote)";
        $stmt = $pdo->prepare($sql);

        foreach ($datosJson as $fila) {
            $id      = str_replace('#', '', ($fila['#ID producto'] ?? ''));
            $modelo  = $fila['Modelo']        ?? '';
            $cat     = $fila['Categoria']     ?? '';
            $proc    = $fila['Procesador']    ?? '';
            $carac   = $fila['Caracteristica'] ?? '';
            $lote    = $fila['Lote']          ?? '';

            if (!empty($id)) {
                try {
                    $stmt->execute([$id, $modelo, $cat, $proc, $carac, $lote]);
                    $conteoExito++;
                } catch (Throwable $e) {
                    $errores[] = "Error en ID $id: " . $e->getMessage();
                }
            }
        }

        echo json_encode([
            'exito'   => true,
            'mensaje' => "Proceso finalizado. $conteoExito registros procesados.",
            'errores' => $errores,
        ]);
        exit;
    }

    // ===================== ACTUALIZAR ACTIVO (POST FORM) =====================
    if ($metodo === 'POST' && isset($_POST['id_producto'])) {
        $id             = $_POST['id_producto']    ?? '';
        $nombre         = $_POST['nombre']         ?? '';
        $categoria      = $_POST['categoria']      ?? '';
        $lote           = $_POST['lote']           ?? '';
        $descripcion    = $_POST['descripcion']    ?? '';
        $marca          = $_POST['marca']          ?? '';
        $procesador     = $_POST['procesador']     ?? '';
        $almacenamiento = $_POST['almacenamiento'] ?? '';
        $ram            = $_POST['ram']            ?? '';

        if (empty($id)) {
            echo json_encode(['exito' => false, 'mensaje' => 'ID de producto no proporcionado.']);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE activos SET nombre=?, categoria=?, lote=?, descripcion=?, marca=?, procesador=?, almacenamiento=?, ram=?
             WHERE id_producto=?"
        );
        if ($stmt->execute([$nombre, $categoria, $lote, $descripcion, $marca, $procesador, $almacenamiento, $ram, $id])) {
            echo json_encode(['exito' => true, 'mensaje' => 'Activo actualizado correctamente']);
        } else {
            echo json_encode(['exito' => false, 'mensaje' => 'Error al actualizar']);
        }
        exit;
    }

    // ===================== INSERTAR ACTIVO(S) EN LOTE =====================
    if ($metodo === 'POST' && isset($_POST['categoria'])) {
        $categoria      = trim($_POST['categoria']);
        $nombre         = trim($_POST['nombre']);
        $idProductos    = $_POST['id_producto'] ?? [];
        $descripcion    = $_POST['descripcion'] ?? '';
        $marca          = $_POST['marca']       ?? '';
        $procesador     = $_POST['procesador']  ?? '';
        $almacenamiento = $_POST['almacenamiento'] ?? '';
        $ram            = $_POST['ram']         ?? '';
        $lote           = $_POST['lote']        ?? '';

        if (empty($categoria) || empty($nombre) || count($idProductos) === 0) {
            echo json_encode(['exito' => false, 'mensaje' => 'Campos obligatorios vacíos']);
            exit;
        }

        $verificar = $pdo->prepare("SELECT COUNT(*) FROM activos WHERE id_producto = ?");
        foreach ($idProductos as $idp) {
            $verificar->execute([$idp]);
            if ((int) $verificar->fetchColumn() > 0) {
                echo json_encode(['exito' => false, 'mensaje' => "El activo $idp ya existe"]);
                exit;
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO activos (categoria, nombre, id_producto, descripcion, marca, procesador, almacenamiento, ram, lote)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insertados = 0;
        foreach ($idProductos as $idProducto) {
            if ($stmt->execute([$categoria, $nombre, $idProducto, $descripcion, $marca, $procesador, $almacenamiento, $ram, $lote])) {
                $insertados++;
            }
        }

        echo json_encode(['exito' => true, 'mensaje' => "Se registraron $insertados activos"]);
        exit;
    }

    // ===================== LISTAR ACTIVOS (GET) =====================
    $stmt    = $pdo->query("SELECT id_producto, nombre, categoria, procesador, ram, almacenamiento, lote, marca, descripcion FROM activos");
    $activos = $stmt->fetchAll();

    echo json_encode(['exito' => true, 'mensaje' => 'Datos obtenidos correctamente', 'datos' => $activos]);

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
