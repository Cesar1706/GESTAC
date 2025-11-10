<?php
header('Content-Type: application/json');

// --- Configuración y Conexión a la BD ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GESTAC";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

// Verificamos el método de la petición: POST para actualizar, GET para leer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- LÓGICA PARA ACTUALIZAR UN ACTIVO ---

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

    $sql = "UPDATE activos SET 
                nombre = ?, 
                categoria = ?, 
                lote = ?, 
                descripcion = ?,
                marca = ?,
                procesador = ?,
                almacenamiento = ?,
                ram = ?
            WHERE 
                id_producto = ?";

    // Preparamos la consulta (línea errónea eliminada)
    $stmt = $conn->prepare($sql);
    
    // Vinculamos parámetros con el tipo de dato correcto
    $stmt->bind_param(
        "sssssssss", 
        $nombre, 
        $categoria, 
        $lote, 
        $descripcion, 
        $marca, 
        $procesador, 
        $almacenamiento, 
        $ram, 
        $id_producto
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activo actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el activo: ' . $stmt->error]);
    }

    $stmt->close();

} else {

    // --- LÓGICA PARA LEER TODOS LOS ACTIVOS (GET) ---

    $sql = "SELECT id_producto, nombre, categoria, procesador, ram, almacenamiento, lote, marca, descripcion FROM activos";
    $result = $conn->query($sql);

    $activos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $activos[] = $row;
        }
        echo json_encode([
            "success" => true,
            "message" => "Datos obtenidos correctamente",
            "data" => $activos
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "No se encontraron activos",
            "data" => []
        ]);
    }
}

$conn->close();
?>