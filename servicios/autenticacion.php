<?php
require_once __DIR__ . '/../configuracion/base_datos.php';
require_once __DIR__ . '/../configuracion/session_check.php';
header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// ===================== CAMBIO DE CONTRASEÑA =====================
if ($accion === 'cambiar_contrasena') {
    $usuarioActualizar = trim($_POST['usuario_update'] ?? '');
    $nuevaContrasena   = trim($_POST['new_password'] ?? '');

    if (empty($usuarioActualizar) || empty($nuevaContrasena)) {
        echo json_encode(['exito' => false, 'mensaje' => '❌ Usuario o nueva contraseña vacíos']);
        exit;
    }

    try {
        $pdo  = obtenerConexion();
        $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
        $stmt->execute([$usuarioActualizar]);

        if ($stmt->fetch()) {
            $contrasenaHash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            if ($upd->execute([$contrasenaHash, $usuarioActualizar])) {
                echo json_encode(['exito' => true, 'mensaje' => '✅ Contraseña actualizada correctamente']);
            } else {
                echo json_encode(['exito' => false, 'mensaje' => '❌ Error al actualizar contraseña']);
            }
        } else {
            echo json_encode(['exito' => false, 'mensaje' => '❌ Usuario no encontrado']);
        }
    } catch (Throwable $e) {
        echo json_encode(['exito' => false, 'mensaje' => 'Error en el servidor']);
    }
    exit;
}

// ===================== LOGOUT =====================
if ($accion === 'logout') {
    $_SESSION = [];
    session_destroy();
    echo json_encode(['exito' => true, 'mensaje' => 'Sesion cerrada']);
    exit;
}

// ===================== VERIFICAR SESION =====================
if ($accion === 'verificar') {
    echo json_encode([
        'exito' => estaAutenticado(),
        'usuario' => $_SESSION['usuario'] ?? null,
        'rol' => $_SESSION['rol'] ?? null
    ]);
    exit;
}

// ===================== LOGIN =====================
$usuario     = trim($_POST['usuario'] ?? '');
$contrasena  = trim($_POST['password'] ?? '');

if (empty($usuario) || empty($contrasena)) {
    echo json_encode(['exito' => false, 'mensaje' => '❌ Usuario o contraseña vacíos']);
    exit;
}

try {
    $pdo  = obtenerConexion();
    $stmt = $pdo->prepare("SELECT username, rol, password FROM users WHERE username = ?");
    $stmt->execute([$usuario]);
    $fila = $stmt->fetch();

    if ($fila && password_verify($contrasena, $fila['password'])) {
        $_SESSION['usuario'] = $fila['username'];
        $_SESSION['rol']     = $fila['rol'];
        echo json_encode(['exito' => true, 'rol' => $fila['rol']]);
    } else {
        echo json_encode(['exito' => false, 'mensaje' => '❌ Usuario o contraseña incorrectos']);
    }
} catch (Throwable $e) {
    echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión']);
}
