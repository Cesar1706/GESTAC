<?php
header('Content-Type: application/json');

// Configuración de la BD
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GESTAC";

// Conexión
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión a la BD: " . $conn->connect_error,
        "data" => []
    ]);
    exit;
}

// Consulta
$sql = "SELECT id_producto, nombre, categoria, procesador, ram, almacenamiento, lote FROM activos";
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

$conn->close();
