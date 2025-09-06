<?php
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gestac";

// Crear conexión
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Recibir datos del formulario
$user = trim($_POST['usuario'] ?? '');
$pass = trim($_POST['password'] ?? '');

// Preparar consulta para validar usuario y contraseña
$stmt = $conn->prepare("SELECT username, rol, password FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "✅ Usuario correcto: " . $row['username'] . "<br>";
    echo "Rol: " . $row['rol'];
} else {
    echo "❌ Usuario o contraseña incorrectos";
}

// Cerrar conexión
$stmt->close();
$conn->close();
?>
