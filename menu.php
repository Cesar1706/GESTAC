<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";  // tu usuario de BD
$password = "";      // tu contraseña de BD
$dbname = "GESTAC";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Revisar conexión
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

// Obtener datos enviados por POST
$categoria     = $_POST['categoria'] ?? '';
$cantidad      = $_POST['cantidad'] ?? '';
$nombre        = $_POST['nombre'] ?? '';
$id_producto   = $_POST['id_producto'] ?? '';
$descripcion   = $_POST['descripcion'] ?? '';
$marca         = $_POST['marca'] ?? '';
$procesador    = $_POST['procesador'] ?? '';
$almacenamiento= $_POST['almacenamiento'] ?? '';
$ram           = $_POST['ram'] ?? '';

// Preparar la inserción
$stmt = $conn->prepare("INSERT INTO activos (categoria, cantidad, nombre, id_producto, descripcion, marca, procesador, almacenamiento, ram) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sisssssss", $categoria, $cantidad, $nombre, $id_producto, $descripcion, $marca, $procesador, $almacenamiento, $ram);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Activo registrado correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al registrar activo: " . $stmt->error]);
}

$stmt->close();
$conn->close();
