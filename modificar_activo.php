<?php

// --- Configuración y Conexión a la BD ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GESTAC";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Error de conexión"]));
}

/* =====================================================
   IMPORTACIÓN MASIVA (JSON POST)
===================================================== */
$inputData = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($inputData)) {
    header('Content-Type: application/json');
    
    $successCount = 0;
    $errors = [];

    // Preparamos la consulta con ON DUPLICATE KEY UPDATE para que sea robusta
    $sql = "INSERT INTO activos (id_producto, nombre, categoria, procesador, ram, lote) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            nombre = VALUES(nombre), 
            categoria = VALUES(categoria), 
            procesador = VALUES(procesador), 
            ram = VALUES(ram), 
            lote = VALUES(lote)";

    $stmt = $conn->prepare($sql);

    foreach ($inputData as $row) {
        // Limpiamos el símbolo '#' si viene en el Excel
        $id = str_replace('#', '', ($row['#ID producto'] ?? ''));
        $modelo = $row['Modelo'] ?? '';
        $cat = $row['Categoria'] ?? '';
        $proc = $row['Procesador'] ?? '';
        $carac = $row['Caracteristica'] ?? '';
        $lote = $row['Lote'] ?? '';

        if (!empty($id)) {
            $stmt->bind_param("ssssss", $id, $modelo, $cat, $proc, $carac, $lote);
            if ($stmt->execute()) {
                $successCount++;
            } else {
                $errors[] = "Error en ID $id: " . $stmt->error;
            }
        }
    }

    echo json_encode([
        "success" => true, 
        "message" => "Proceso finalizado. $successCount registros procesados.",
        "errors" => $errors
    ]);

    $stmt->close();
    $conn->close();
    exit;
}

/* =====================================================
   EXPORTAR A EXCEL (BONITO)
===================================================== */
if (isset($_GET['export']) && $_GET['export'] == 'excel') {

    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=reporte_activos.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
            th { background-color: #2f6b2f; color: white; padding: 8px; border: 1px solid black; text-align: center; }
            td { border: 1px solid black; padding: 6px; }
            tr:nth-child(even) { background-color: #e6ffe6; }
            .titulo { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class="titulo">REPORTE GENERAL DE ACTIVOS</div>
        <table>
            <tr>
                <th>ID Producto</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Procesador</th>
                <th>RAM</th>
                <th>Almacenamiento</th>
                <th>Lote</th>
                <th>Marca</th>
                <th>Descripción</th>
            </tr>
    ';

    $sql = "SELECT id_producto, nombre, categoria, procesador, ram, almacenamiento, lote, marca, descripcion FROM activos";
    $result = $conn->query($sql);

    while($row = $result->fetch_assoc()){
        echo "<tr>
                <td>{$row['id_producto']}</td>
                <td>{$row['nombre']}</td>
                <td>{$row['categoria']}</td>
                <td>{$row['procesador']}</td>
                <td>{$row['ram']}</td>
                <td>{$row['almacenamiento']}</td>
                <td>{$row['lote']}</td>
                <td>{$row['marca']}</td>
                <td>{$row['descripcion']}</td>
              </tr>";
    }

    echo '</table></body></html>';
    $conn->close();
    exit;
}

/* =====================================================
   RESPUESTA JSON NORMAL (ACTUALIZAR INDIVIDUAL O LISTAR)
===================================================== */
header('Content-Type: application/json');

// --- ACTUALIZAR ACTIVO INDIVIDUAL (POST FORM DATA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto'])) {

    $id_producto    = $_POST['id_producto'] ?? '';
    $nombre         = $_POST['nombre'] ?? '';
    $categoria      = $_POST['categoria'] ?? '';
    $lote           = $_POST['lote'] ?? '';
    $descripcion    = $_POST['descripcion'] ?? '';
    $marca          = $_POST['marca'] ?? '';
    $procesador     = $_POST['procesador'] ?? '';
    $almacenamiento = $_POST['almacenamiento'] ?? '';
    $ram            = $_POST['ram'] ?? '';

    if (empty($id_producto)) {
        echo json_encode(["success" => false, "message" => "ID de producto no proporcionado."]);
        exit;
    }

    $sql = "UPDATE activos SET nombre=?, categoria=?, lote=?, descripcion=?, marca=?, procesador=?, almacenamiento=?, ram=? WHERE id_producto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $nombre, $categoria, $lote, $descripcion, $marca, $procesador, $almacenamiento, $ram, $id_producto);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activo actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// --- OBTENER ACTIVOS (GET) ---
$sql = "SELECT id_producto, nombre, categoria, procesador, ram, almacenamiento, lote, marca, descripcion FROM activos";
$result = $conn->query($sql);

$activos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $activos[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "message" => "Datos obtenidos correctamente",
    "data" => $activos
]);

$conn->close();
?>