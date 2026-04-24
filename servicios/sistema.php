<?php
require_once __DIR__ . '/../configuracion/base_datos.php';
header('Content-Type: application/json; charset=utf-8');

$tipo = $_GET['tipo'] ?? '';
$q    = $_GET['q']    ?? '';

try {
    $pdo = obtenerConexion();

    // ===================== BÚSQUEDA DINÁMICA =====================
    if ($tipo === 'usuario' && $q !== '') {
        $stmt = $pdo->prepare(
            "SELECT nombre, nomina FROM usuarios
             WHERE nombre LIKE CONCAT('%',?,'%') OR nomina LIKE CONCAT('%',?,'%')
             LIMIT 10"
        );
        $stmt->execute([$q, $q]);
        $resultados = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultados[] = $fila['nombre'] . ' (' . $fila['nomina'] . ')';
        }
        echo json_encode($resultados);
        exit;
    }

    if ($tipo === 'activo' && $q !== '') {
        $stmt = $pdo->prepare(
            "SELECT id_producto, nombre FROM activos
             WHERE id_producto LIKE CONCAT('%',?,'%') OR nombre LIKE CONCAT('%',?,'%')
             LIMIT 10"
        );
        $stmt->execute([$q, $q]);
        $resultados = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultados[] = $fila['id_producto'] . ' - ' . $fila['nombre'];
        }
        echo json_encode($resultados);
        exit;
    }

    // ===================== VER USUARIOS DEL SISTEMA =====================
    if ($tipo === 'verUsuarios') {
        $stmt = $pdo->query("SELECT username, rol FROM users");
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ===================== ESTADÍSTICAS =====================
    if ($tipo === 'stats') {
        $activos  = (int) $pdo->query("SELECT COUNT(*) FROM activos")->fetchColumn();
        $usuarios = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $prestamos = 0;
        $existe = $pdo->query("SHOW TABLES LIKE 'prestamos'")->fetch();
        if ($existe) {
            $prestamos = (int) $pdo->query("SELECT COUNT(*) FROM prestamos")->fetchColumn();
        }
        echo json_encode(['activos' => $activos, 'usuarios' => $usuarios, 'prestamos' => $prestamos]);
        exit;
    }

    // ===================== ESTADO DE PRÉSTAMOS =====================
    if ($tipo === 'prestamos_estado') {
        $datos = [];
        $existe = $pdo->query("SHOW TABLES LIKE 'prestamos'")->fetch();
        if ($existe) {
            $stmt = $pdo->query(
                "SELECT COALESCE(NULLIF(estado,''),'sin estado') AS estado, COUNT(*) AS total
                 FROM prestamos GROUP BY estado ORDER BY total DESC"
            );
            $datos = $stmt->fetchAll();
        }
        echo json_encode($datos);
        exit;
    }

    // ===================== UBICACIONES =====================
    if ($tipo === 'ubicaciones') {
        $stmt = $pdo->query(
            "SELECT COALESCE(NULLIF(TRIM(ubicacion),''),'Sin ubicación') AS ubicacion, COUNT(*) AS total
             FROM usuarios GROUP BY ubicacion ORDER BY total DESC LIMIT 6"
        );
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ===================== CATEGORÍAS =====================
    if ($tipo === 'categorias') {
        $stmt = $pdo->query(
            "SELECT categoria, COUNT(*) AS total FROM activos GROUP BY categoria ORDER BY total DESC"
        );
        $datos = [];
        foreach ($stmt->fetchAll() as $fila) {
            $datos[] = ['categoria' => $fila['categoria'], 'total' => (int) $fila['total']];
        }
        echo json_encode($datos);
        exit;
    }

    // ===================== LOGS =====================
    if ($tipo === 'logs') {
        $stmt = $pdo->query(
            "SELECT fecha, tabla, accion, registro_id, descripcion
             FROM logs ORDER BY fecha DESC LIMIT 100"
        );
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ===================== POST: AGREGAR USUARIO =====================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'], $_POST['password'], $_POST['rol'])) {
        $correo     = $_POST['correo'];
        $contrasena = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $rol        = $_POST['rol'];

        if (empty($correo) || empty($rol)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos']);
            exit;
        }

        $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $chk->execute([$correo]);
        if ((int) $chk->fetchColumn() > 0) {
            echo json_encode(['exito' => false, 'mensaje' => 'Usuario ya existe']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, password, rol) VALUES (?, ?, ?)");
        $stmt->execute([$correo, $contrasena, $rol]);
        echo json_encode(['exito' => true, 'mensaje' => 'Usuario agregado']);
        exit;
    }

    // ===================== POST: CAMBIAR ROL =====================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['nuevoRol'], $_POST['passwordAdmin'])) {
        $usuario      = $_POST['username'];
        $nuevoRol     = $_POST['nuevoRol'];
        $claveAdmin   = $_POST['passwordAdmin'];

        $stmt = $pdo->query("SELECT password FROM users WHERE rol = 'admin'");
        $valido = false;
        foreach ($stmt->fetchAll() as $fila) {
            if (password_verify($claveAdmin, $fila['password'])) {
                $valido = true;
                break;
            }
        }

        if (!$valido) {
            echo json_encode(['exito' => false, 'mensaje' => 'Acceso inválido']);
            exit;
        }

        $upd = $pdo->prepare("UPDATE users SET rol = ? WHERE username = ?");
        $upd->execute([$nuevoRol, $usuario]);
        echo json_encode(['exito' => true]);
        exit;
    }

    // ===================== POST: ASIGNAR ACTIVO =====================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'], $_POST['activo'])) {
        $usuarioStr = $_POST['usuario'];
        $activoStr  = $_POST['activo'];
        $fecha      = $_POST['fecha_asignacion'];

        if (empty($usuarioStr) || empty($activoStr) || empty($fecha)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos']);
            exit;
        }

        if (!preg_match('/\((\d+)\)$/', $usuarioStr, $m)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Usuario inválido']);
            exit;
        }
        $nomina   = $m[1];
        $activoID = explode(' - ', $activoStr)[0];

        $chk = $pdo->prepare("SELECT COUNT(*) FROM activos WHERE id_producto = ?");
        $chk->execute([$activoID]);
        if ((int) $chk->fetchColumn() === 0) {
            echo json_encode(['exito' => false, 'mensaje' => 'Activo no existe']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE usuarios SET activo = ?, fecha = ? WHERE nomina = ?");
        $stmt->execute([$activoID, $fecha, $nomina]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['exito' => true, 'mensaje' => 'Asignación correcta']);
        } else {
            echo json_encode(['exito' => false, 'mensaje' => 'Usuario no encontrado en el sistema']);
        }
        exit;
    }

    echo json_encode([]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error en el servidor']);
}
