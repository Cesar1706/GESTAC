<?php

// --- Configuración y Conexión a la BD ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GESTAC";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(["success" => false, "message" => "Error de conexión"]));
}

/* =====================================================
   EXPORTAR A EXCEL (BONITO)
===================================================== */
if (isset($_GET['export']) && $_GET['export'] == 'excel') {

    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=reporte_usuarios_asignados.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
                font-family: Arial, sans-serif;
            }
            th {
                background-color: #2f6b2f;
                color: white;
                padding: 8px;
                border: 1px solid black;
                text-align: center;
            }
            td {
                border: 1px solid black;
                padding: 6px;
            }
            tr:nth-child(even) {
                background-color: #e6ffe6;
            }
            .titulo {
                font-size: 18px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <div class="titulo">REPORTE DE USUARIOS CON ACTIVOS ASIGNADOS</div>
        <table>
            <tr>
                <th>Nómina</th>
                <th>Nombre</th>
                <th>Ubicación</th>
                <th>Activo Asignado</th>
                <th>Fecha</th>
            </tr>
    ';

    $sql = "SELECT nomina, nombre, ubicacion, activo, fecha FROM usuarios";
    $result = $conn->query($sql);

    while($row = $result->fetch_assoc()){
        echo "<tr>
                <td>{$row['nomina']}</td>
                <td>{$row['nombre']}</td>
                <td>{$row['ubicacion']}</td>
                <td>{$row['activo']}</td>
                <td>{$row['fecha']}</td>
              </tr>";
    }

    echo '
        </table>
    </body>
    </html>';

    $conn->close();
    exit;
}

/* =====================================================
   RESPUESTA JSON NORMAL
===================================================== */
header('Content-Type: application/json');

// --- ACTUALIZAR USUARIO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nomina    = $_POST['nomina'] ?? '';
    $nombre    = $_POST['nombre'] ?? '';
    $ubicacion = $_POST['ubicacion'] ?? '';
    $activo    = $_POST['activo'] ?? '';
    $fecha     = $_POST['fecha'] ?? '';

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
    $stmt->bind_param("sssss", $nombre, $ubicacion, $activo, $fecha, $nomina);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    } elseif ($stmt->affected_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No se encontró el usuario con esa nómina']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

/* =====================================================
   OBTENER USUARIOS (GET NORMAL)
===================================================== */

$sql = "SELECT nomina, nombre, ubicacion, activo, fecha FROM usuarios";
$result = $conn->query($sql);

$usuarios = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "message" => "Datos obtenidos correctamente",
    "data" => $usuarios
]);

$conn->close();
?>