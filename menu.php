<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GESTAC";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión"]);
    exit;
}

// ===================== BÚSQUEDA DINÁMICA =====================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $tipo = $_GET['tipo'] ?? '';
    $q = $_GET['q'] ?? '';

    $results = [];

    if($tipo === 'usuario' && $q !== '') {
        $stmt = $conn->prepare("SELECT nombre, nomina FROM usuarios 
                                WHERE nombre LIKE CONCAT('%',?,'%') 
                                   OR nomina LIKE CONCAT('%',?,'%') 
                                LIMIT 10");
        $stmt->bind_param("ss",$q,$q);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
            $results[] = $row['nombre']." (".$row['nomina'].")";
        }
        $stmt->close();
    } 
    elseif($tipo === 'activo' && $q !== '') {
        $stmt = $conn->prepare("SELECT id_producto, nombre FROM activos 
                                WHERE id_producto LIKE CONCAT('%',?,'%') 
                                   OR nombre LIKE CONCAT('%',?,'%') 
                                LIMIT 10");
        $stmt->bind_param("ss",$q,$q);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
            $results[] = $row['id_producto']." - ".$row['nombre'];
        }
        $stmt->close();
    } 
    elseif($tipo === 'verUsuarios') {
        $res = $conn->query("SELECT username, rol FROM users");
        while($row = $res->fetch_assoc()){
            $results[] = ["username"=>$row['username'],"rol"=>$row['rol']];
        }
    }
    elseif($tipo === 'stats') {
        $activos   = (int)$conn->query("SELECT COUNT(*) FROM activos")->fetch_row()[0];
        $usuarios  = (int)$conn->query("SELECT COUNT(*) FROM usuarios")->fetch_row()[0];
        $prestamos = 0;
        $chk = $conn->query("SHOW TABLES LIKE 'prestamos'");
        if ($chk && $chk->num_rows > 0)
            $prestamos = (int)$conn->query("SELECT COUNT(*) FROM prestamos")->fetch_row()[0];
        echo json_encode(['activos'=>$activos,'usuarios'=>$usuarios,'prestamos'=>$prestamos]);
        $conn->close(); exit;
    }
    elseif($tipo === 'prestamos_estado') {
        $data = [];
        $chk = $conn->query("SHOW TABLES LIKE 'prestamos'");
        if ($chk && $chk->num_rows > 0) {
            $res = $conn->query("SELECT COALESCE(NULLIF(estado,''),'sin estado') AS estado, COUNT(*) AS total FROM prestamos GROUP BY estado ORDER BY total DESC");
            if ($res) while($row = $res->fetch_assoc()) $data[] = $row;
        }
        echo json_encode($data);
        $conn->close(); exit;
    }
    elseif($tipo === 'ubicaciones') {
        $data = [];
        $res = $conn->query("SELECT COALESCE(NULLIF(TRIM(ubicacion),''),'Sin ubicación') AS ubicacion, COUNT(*) AS total FROM usuarios GROUP BY ubicacion ORDER BY total DESC LIMIT 6");
        if ($res) while($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        $conn->close(); exit;
    }
    elseif($tipo === 'categorias') {
        $res = $conn->query(
            "SELECT categoria, COUNT(*) AS total
             FROM activos
             GROUP BY categoria
             ORDER BY total DESC"
        );
        $data = [];
        while($row = $res->fetch_assoc()){
            $data[] = ["categoria" => $row['categoria'], "total" => (int)$row['total']];
        }
        echo json_encode($data);
        $conn->close();
        exit;
    }
    elseif($tipo === 'logs') {
        $res = $conn->query("
            SELECT 
                fecha,
                tabla,
                accion,
                registro_id,
                descripcion
            FROM logs
            ORDER BY fecha DESC
            LIMIT 100
        ");

        $logs = [];
        while($row = $res->fetch_assoc()){
            $logs[] = $row;
        }

        echo json_encode($logs);
        $conn->close();
        exit;
    }

    echo json_encode($results);
    $conn->close();
    exit;
}


// ===================== INSERTAR ACTIVO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoria'])) {

    $categoria = trim($_POST['categoria']);
    $nombre = trim($_POST['nombre']);
    $id_productos = $_POST['id_producto'] ?? [];
    $descripcion = $_POST['descripcion'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $procesador = $_POST['procesador'] ?? '';
    $almacenamiento = $_POST['almacenamiento'] ?? '';
    $ram = $_POST['ram'] ?? '';
    $lote = $_POST['lote'] ?? '';

    if(empty($categoria) || empty($nombre) || count($id_productos)==0){
        echo json_encode(["success"=>false,"message"=>"Campos obligatorios vacíos"]);
        exit;
    }

    $check = $conn->prepare("SELECT COUNT(*) FROM activos WHERE id_producto=?");

    foreach($id_productos as $idp){
        $check->bind_param("s",$idp);
        $check->execute();
        $check->store_result();
        $check->bind_result($count);
        $check->fetch();

        if($count > 0){
            echo json_encode(["success"=>false,"message"=>"El activo $idp ya existe"]);
            exit;
        }
    }
    $check->close();

    $stmt = $conn->prepare("INSERT INTO activos (categoria,nombre,id_producto,descripcion,marca,procesador,almacenamiento,ram,lote)
                             VALUES (?,?,?,?,?,?,?,?,?)");

    $insertados = 0;
    foreach($id_productos as $id_producto){
        $stmt->bind_param("sssssssss",$categoria,$nombre,$id_producto,$descripcion,$marca,$procesador,$almacenamiento,$ram,$lote);
        if($stmt->execute()) $insertados++;
    }

    echo json_encode(["success"=>true,"message"=>"Se registraron $insertados activos"]);
    $stmt->close();
    $conn->close();
    exit;
}

// ===================== ASIGNAR ACTIVO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'],$_POST['activo'])) {

    $usuario = $_POST['usuario'];
    $activo = $_POST['activo'];
    $fecha = $_POST['fecha_asignacion'];

    if(empty($usuario) || empty($activo) || empty($fecha)){
        echo json_encode(["success"=>false,"message"=>"Datos incompletos"]);
        exit;
    }

    if(!preg_match('/\((\d+)\)$/',$usuario,$m)){
        echo json_encode(["success"=>false,"message"=>"Usuario inválido"]);
        exit;
    }
    $nomina = $m[1];

    $activoID = explode(" - ",$activo)[0];

    $check = $conn->prepare("SELECT COUNT(*) FROM activos WHERE id_producto=?");
    $check->bind_param("s",$activoID);
    $check->execute();
    $check->bind_result($c);
    $check->fetch();
    $check->close();

    if($c==0){
        echo json_encode(["success"=>false,"message"=>"Activo no existe"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE usuarios SET activo=?, fecha=? WHERE nomina=?");
    $stmt->bind_param("sss",$activoID,$fecha,$nomina);

    if($stmt->execute() && $stmt->affected_rows > 0){
        echo json_encode(["success"=>true,"message"=>"Asignación correcta"]);
    } elseif($stmt->affected_rows === 0) {
        echo json_encode(["success"=>false,"message"=>"Usuario no encontrado en el sistema"]);
    } else {
        echo json_encode(["success"=>false,"message"=>"Error al asignar"]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ===================== CAMBIAR ROL =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'],$_POST['nuevoRol'],$_POST['passwordAdmin'])){

    $username=$_POST['username'];
    $nuevoRol=$_POST['nuevoRol'];
    $passAdmin=$_POST['passwordAdmin'];

    $stmt=$conn->prepare("SELECT password FROM users WHERE rol='admin'");
    $stmt->execute();
    $res=$stmt->get_result();

    $valido=false;
    while($r=$res->fetch_assoc()){
        if(password_verify($passAdmin,$r['password'])){
            $valido=true;
            break;
        }
    }

    if(!$valido){
        echo json_encode(["success"=>false,"message"=>"Acceso inválido"]);
        exit;
    }

    $stmt2=$conn->prepare("UPDATE users SET rol=? WHERE username=?");
    $stmt2->bind_param("ss",$nuevoRol,$username);
    $stmt2->execute();

    echo json_encode(["success"=>true]);
    $stmt2->close();
    $conn->close();
    exit;
}

// ===================== AGREGAR USUARIO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'],$_POST['password'],$_POST['rol'])
    && !isset($_POST['usuario'],$_POST['activo'],$_POST['nuevoRol'])){

    $correo=$_POST['correo'];
    $password=password_hash($_POST['password'],PASSWORD_DEFAULT);
    $rol=$_POST['rol'];

    if(empty($correo)||empty($rol)){
        echo json_encode(["success"=>false,"message"=>"Datos incompletos"]);
        exit;
    }

    $chk=$conn->prepare("SELECT COUNT(*) FROM users WHERE username=?");
    $chk->bind_param("s",$correo);
    $chk->execute();
    $chk->bind_result($c);
    $chk->fetch();
    $chk->close();

    if($c>0){
        echo json_encode(["success"=>false,"message"=>"Usuario ya existe"]);
        exit;
    }

    $stmt=$conn->prepare("INSERT INTO users(username,password,rol) VALUES(?,?,?)");
    $stmt->bind_param("sss",$correo,$password,$rol);
    $stmt->execute();

    echo json_encode(["success"=>true,"message"=>"Usuario agregado"]);
    $stmt->close();
    $conn->close();
    exit;
}
?>