<?php
$conn = new mysqli("localhost", "root", "", "gestac");
header('Content-Type: application/json');

if($conn->connect_error){
    echo json_encode(["success"=>false,"message"=>"Error de conexión"]);
    exit();
}

// === CAMBIO DE CONTRASEÑA ===
if(isset($_POST['action']) && $_POST['action'] === 'update_password'){
    $userUpdate = trim($_POST['usuario_update'] ?? '');
    $newpass = trim($_POST['new_password'] ?? '');

    if(empty($userUpdate) || empty($newpass)){
        echo json_encode(["success"=>false,"message"=>"❌ Usuario o nueva contraseña vacíos"]);
        exit();
    }

    // Verificar si usuario existe
    $checkStmt = $conn->prepare("SELECT username FROM users WHERE username=?");
    $checkStmt->bind_param("s", $userUpdate);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if($checkResult->num_rows > 0){
        $updateStmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
        $updateStmt->bind_param("ss", $newpass, $userUpdate);
        if($updateStmt->execute()){
            echo json_encode(["success"=>true,"message"=>"✅ Contraseña actualizada correctamente"]);
        } else {
            echo json_encode(["success"=>false,"message"=>"❌ Error al actualizar contraseña"]);
        }
        $updateStmt->close();
    } else {
        echo json_encode(["success"=>false,"message"=>"❌ Usuario no encontrado"]);
    }
    $checkStmt->close();
    $conn->close();
    exit();
}

// === LOGIN ===
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
