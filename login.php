<?php
$conn = new mysqli("localhost", "root", "", "gestac");
header('Content-Type: application/json');

if($conn->connect_error){
    echo json_encode(["success"=>false,"message"=>"Error de conexión"]);
    exit();
}

$user = trim($_POST['usuario'] ?? '');
$pass = trim($_POST['password'] ?? '');

$stmt = $conn->prepare("SELECT username, rol, password FROM users WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    if($pass === $row['password']){ 
        echo json_encode(["success"=>true,"rol"=>$row['rol']]);
    } else {
        echo json_encode(["success"=>false,"message"=>"❌ Usuario o contraseña incorrectos"]);
    }
} else {
    echo json_encode(["success"=>false,"message"=>"❌ Usuario o contraseña incorrectos"]);
}

$stmt->close();
$conn->close();
