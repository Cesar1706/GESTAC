<?php
// inventario_perifericos.php
header('Content-Type: application/json; charset=utf-8');

// CORS opcional
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Errores (desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dbHost = 'localhost';
$dbName = 'GESTAC';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos',
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {

    // ================= EXPORTAR EXCEL =================
    if ($method === 'GET' && $action === 'export_excel') {

        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=inventario_perifericos.xls");
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
                    font-family: Arial;
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
            <div class="titulo">REPORTE INVENTARIO DE PERIFÉRICOS</div>
            <table>
                <tr>
                    <th>Nombre</th>
                    <th>S/N</th>
                </tr>
        ';

        $sql = "SELECT `nombre`, `S/N` AS sn FROM `inventario_perifericos` ORDER BY `nombre`";
        $stmt = $pdo->query($sql);

        while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['nombre']}</td>
                    <td>{$row['sn']}</td>
                  </tr>";
        }

        echo '
            </table>
        </body>
        </html>';

        exit;
    }

    // ================= LISTAR =================
    if ($method === 'GET' && $action === 'list') {
        $sql = "SELECT `id`, `nombre`, `S/N` AS sn
                FROM `inventario_perifericos`
                ORDER BY `nombre` ASC";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ================= INSERTAR =================
    if ($method === 'POST' && $action === 'insert') {
        $nombre = trim($_POST['nombre'] ?? '');
        $sn     = trim($_POST['sn'] ?? '');

        if ($nombre === '' || $sn === '') {
            echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "INSERT INTO `inventario_perifericos` (`nombre`, `S/N`) VALUES (:nombre, :sn)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':sn' => $sn]);

        echo json_encode(['success' => true, 'message' => 'Insertado correctamente'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ================= ELIMINAR =================
    if ($method === 'POST' && $action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "DELETE FROM `inventario_perifericos` WHERE `id` = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Eliminado correctamente'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no soportada.'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor',
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}