<?php
require_once __DIR__ . '/../configuracion/base_datos.php';
require_once __DIR__ . '/../configuracion/session_check.php';
requerirAutenticacion();
header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

try {
    $pdo = obtenerConexion();

    // ===================== LISTAR PRESTAMOS =====================
    if ($metodo === 'GET' && $accion === 'listar') {
        $existe = $pdo->query("SHOW TABLES LIKE 'prestamos'")->fetch();
        $datos = [];
        if ($existe) {
            $stmt = $pdo->query(
                "SELECT id, usuario, equipo, serie, fecha_prestamo, fecha_devolucion, estado
                 FROM prestamos ORDER BY
                     CASE WHEN estado = 'Activo' THEN 0 ELSE 1 END,
                     fecha_prestamo DESC"
            );
            $datos = $stmt->fetchAll();
        }
        echo json_encode(['exito' => true, 'datos' => $datos]);
        exit;
    }

    // ===================== INSERTAR PRESTAMO =====================
    if ($metodo === 'POST' && $accion === 'insertar') {
        $usuario       = trim($_POST['usuario'] ?? '');
        $equipo        = trim($_POST['equipo'] ?? '');
        $serie         = trim($_POST['serie'] ?? '');
        $fechaPrestamo = trim($_POST['fecha_prestamo'] ?? '');
        $fechaDevol    = trim($_POST['fecha_devolucion'] ?? '');

        if (empty($usuario) || empty($serie) || empty($fechaPrestamo) || empty($fechaDevol)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Campos obligatorios faltantes']);
            exit;
        }

        $sql = "INSERT INTO prestamos (usuario, equipo, serie, fecha_prestamo, fecha_devolucion, estado)
                VALUES (?, ?, ?, ?, ?, 'Activo')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario, $equipo, $serie, $fechaPrestamo, $fechaDevol]);

        echo json_encode(['exito' => true, 'mensaje' => 'Prestamo registrado', 'id' => $pdo->lastInsertId()]);
        exit;
    }

    // ===================== DEVOLVER PRESTAMO =====================
    if ($metodo === 'POST' && $accion === 'devolver') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['exito' => false, 'mensaje' => 'ID invalido']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE prestamos SET estado = 'Devuelto' WHERE id = ? AND estado = 'Activo'");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['exito' => true, 'mensaje' => 'Devolucion registrada']);
        } else {
            echo json_encode(['exito' => false, 'mensaje' => 'Prestamo no encontrado o ya devuelto']);
        }
        exit;
    }

    // ===================== CARGA INICIAL (usuarios + perifericos) =====================
    $usuarios = [];
    $stmt = $pdo->query(
        "SELECT nombre FROM usuarios WHERE nombre IS NOT NULL AND nombre <> '' ORDER BY nombre ASC"
    );
    foreach ($stmt->fetchAll() as $fila) {
        $usuarios[] = $fila['nombre'];
    }

    $perifericos = [];
    $stmt = $pdo->query(
        "SELECT nombre, `S/N` AS sn FROM inventario_perifericos
         WHERE nombre IS NOT NULL AND nombre <> ''
           AND `S/N` IS NOT NULL AND `S/N` <> ''
         ORDER BY nombre ASC, `S/N` ASC"
    );
    foreach ($stmt->fetchAll() as $fila) {
        $perifericos[] = ['nombre' => $fila['nombre'], 'sn' => (string) $fila['sn']];
    }

    echo json_encode(
        ['exito' => true, 'usuarios' => $usuarios, 'perifericos' => $perifericos],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['exito' => false, 'usuarios' => [], 'perifericos' => [], 'datos' => [], 'error' => $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}
