<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GESTAC";

// Conexión
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

// Datos POST
$categoria     = $_POST['categoria'] ?? '';
$nombre        = $_POST['nombre'] ?? '';
$id_productos  = $_POST['id_producto'] ?? []; // array de números de serie
$descripcion   = $_POST['descripcion'] ?? '';
$marca         = $_POST['marca'] ?? '';
$procesador    = $_POST['procesador'] ?? '';
$almacenamiento= $_POST['almacenamiento'] ?? '';
$ram           = $_POST['ram'] ?? '';
$lote          = $_POST['lote'] ?? '';

if(empty($categoria) || empty($nombre) || empty($id_productos)){
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

// Preparar statement
$stmt = $conn->prepare("INSERT INTO activos (categoria, nombre, id_producto, descripcion, marca, procesador, almacenamiento, ram, lote) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssss", $categoria, $nombre, $id_producto, $descripcion, $marca, $procesador, $almacenamiento, $ram, $lote);

// Insertar uno por uno
$insertados = 0;
foreach($id_productos as $id_producto){
    if($stmt->execute()){
        $insertados++;
    }
}

if($insertados > 0){
    echo json_encode(["success" => true, "message" => "Se registraron $insertados activos correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "No se pudo registrar ningún activo"]);
}

$stmt->close();
$conn->close();
