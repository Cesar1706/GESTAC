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

// ===================== BÚSQUEDA DINÁMICA =====================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tipo = $_GET['tipo'] ?? ''; // 'usuario', 'activo' o 'verUsuarios'
    $q = $_GET['q'] ?? '';
    $q = $conn->real_escape_string($q);

    $results = [];

    if($tipo === 'usuario' && $q !== '') {
        $sql = "SELECT nombre, nomina FROM usuarios WHERE nombre LIKE '%$q%' OR nomina LIKE '%$q%' LIMIT 10";
        $res = $conn->query($sql);
        while($row = $res->fetch_assoc()){
            $results[] = $row['nombre'] . " (" . $row['nomina'] . ")";
        }
    } elseif($tipo === 'activo' && $q !== '') {
        $sql = "SELECT id_producto, nombre FROM activos WHERE id_producto LIKE '%$q%' OR nombre LIKE '%$q%' LIMIT 10";
        $res = $conn->query($sql);
        while($row = $res->fetch_assoc()){
            $results[] = $row['id_producto'] . " - " . $row['nombre'];
        }
    } elseif($tipo === 'verUsuarios') {
        $sql = "SELECT username, rol FROM users";
        $res = $conn->query($sql);
        while($row = $res->fetch_assoc()){
            $results[] = ["username" => $row['username'], "rol" => $row['rol']];
        }
    }

    echo json_encode($results);
    $conn->close();
    exit;
}

// ===================== INSERCIÓN DE NUEVO ACTIVO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoria'])) {
    $categoria     = $_POST['categoria'] ?? '';
    $nombre        = $_POST['nombre'] ?? '';
    $id_productos  = $_POST['id_producto'] ?? [];
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

    $stmt = $conn->prepare("INSERT INTO activos (categoria, nombre, id_producto, descripcion, marca, procesador, almacenamiento, ram, lote) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $insertados = 0;
    foreach($id_productos as $id_producto){
        $stmt->bind_param("sssssssss", $categoria, $nombre, $id_producto, $descripcion, $marca, $procesador, $almacenamiento, $ram, $lote);
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
    exit;
}

// ===================== ASIGNAR ACTIVO A USUARIO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && isset($_POST['activo'])) {
    $usuario = $_POST['usuario'] ?? '';
    $activo  = $_POST['activo'] ?? '';
    $fecha   = $_POST['fecha_asignacion'] ?? '';

    if(empty($usuario) || empty($activo) || empty($fecha)){
        echo json_encode(["success" => false, "message" => "Datos incompletos"]);
        exit;
    }

    if(preg_match('/\((\d+)\)$/', $usuario, $matches)){
        $nomina = $matches[1];
    } else {
        echo json_encode(["success" => false, "message" => "Formato de usuario incorrecto"]);
        exit;
    }

    $activoID = explode(" - ", $activo)[0];
    $sqlUpdate = "UPDATE usuarios SET activo = ?, fecha = ? WHERE nomina = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("sss", $activoID, $fecha, $nomina);

    if($stmt->execute()){
        echo json_encode(["success" => true, "message" => "Activo asignado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al asignar el activo"]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ===================== CAMBIAR ROL DE USUARIO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['nuevoRol'], $_POST['passwordAdmin'])){
    $username = $_POST['username'];
    $nuevoRol = $_POST['nuevoRol'];
    $passAdmin = $_POST['passwordAdmin'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE rol='admin' AND password=?");
    $stmt->bind_param("s",$passAdmin);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if($row['count']>0){
        $stmt2 = $conn->prepare("UPDATE users SET rol=? WHERE username=?");
        $stmt2->bind_param("ss",$nuevoRol,$username);
        if($stmt2->execute()){
            echo json_encode(["success"=>true]);
        } else {
            echo json_encode(["success"=>false,"message"=>"Error al actualizar"]);
        }
        $stmt2->close();
    } else {
        echo json_encode(["success"=>false,"message"=>"Acceso inválido"]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ===================== AGREGAR NUEVO USUARIO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'], $_POST['password'], $_POST['rol']) 
    && !isset($_POST['nuevoRol'], $_POST['passwordAdmin'], $_POST['usuario'], $_POST['activo'])) {

    $correo   = $_POST['correo'];      // Se guarda en username
    $password = $_POST['password'];
    $rol      = $_POST['rol'];

    if(empty($correo) || empty($password) || empty($rol)){
        echo json_encode(["success"=>false,"message"=>"Datos incompletos"]);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO users (username, password, rol) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $correo, $password, $rol);

    if($stmt->execute()){
        echo json_encode(["success"=>true,"message"=>"Usuario agregado correctamente"]);
    } else {
        echo json_encode(["success"=>false,"message"=>"Error al agregar usuario"]);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
