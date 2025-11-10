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
    
    // --- LÓGICA PARA ACTUALIZAR UN USUARIO ---
    $nomina      = $_POST['nomina'] ?? '';
    $nombre      = $_POST['nombre'] ?? '';
    $ubicacion   = $_POST['ubicacion'] ?? '';
    $activo      = $_POST['activo'] ?? '';
    $fecha       = $_POST['fecha'] ?? '';

    if (empty($nomina)) {
        echo json_encode(["success" => false, "message" => "No se proporcionó la nómina del usuario."]);
        exit;
    }

    $sql = "UPDATE usuarios SET 
                nombre = ?, 
                ubicacion = ?, 
                activo = ?, 
                fecha = ?
            WHERE nomina = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error al preparar la consulta: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("sssss", $nombre, $ubicacion, $activo, $fecha, $nomina);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario: ' . $stmt->error]);
    }

    $stmt->close();

} else {

    // --- LÓGICA PARA LEER TODOS LOS USUARIOS (GET) ---
    $sql = "SELECT nomina, nombre, ubicacion, activo, fecha FROM usuarios";
    $result = $conn->query($sql);

    $usuarios = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        echo json_encode([
            "success" => true,
            "message" => "Datos obtenidos correctamente",
            "data" => $usuarios
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "No se encontraron usuarios",
            "data" => []
        ]);
    }
}

$conn->close();
?>
