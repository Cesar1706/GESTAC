<?php
// prestamos.php — TODO EN UNO: conexión + consultas + render

/* ======================= CONEXIÓN A BD ======================= */
$DB_HOST = 'localhost';
$DB_NAME = 'GESTAC';
$DB_USER = 'root';
$DB_PASS = ''; // cambia según tu entorno

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );
} catch (Throwable $e) {
  http_response_code(500);
  die("<pre style='color:#c00;font:14px/1.4 monospace'>Error de conexión: ".htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')."</pre>");
}

/* ======================= CONSULTAS ======================= */
try {
  // Usuarios
  $usuarios = [];
  $stmt = $pdo->query("SELECT nombre FROM usuarios WHERE nombre IS NOT NULL AND nombre<>'' ORDER BY nombre ASC");
  foreach ($stmt as $r) { $usuarios[] = $r['nombre']; }

  // Periféricos: columna `S/N` -> alias sn
  $perifs = [];
  $stmt = $pdo->query("
    SELECT nombre, `S/N` AS sn
    FROM inventario_perifericos
    WHERE nombre IS NOT NULL AND nombre <> ''
      AND `S/N` IS NOT NULL AND `S/N` <> ''
    ORDER BY nombre ASC, sn ASC
  ");
  foreach ($stmt as $r) {
    $perifs[] = ['nombre' => $r['nombre'], 'sn' => (string)$r['sn']];
  }
} catch (Throwable $e) {
  http_response_code(500);
  die("<pre style='color:#c00;font:14px/1.4 monospace'>Error de consulta: ".htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')."</pre>");
}

/* ======================= HELPERS ======================= */
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Mapa serie -> equipo para JS
$serieToEquipo = [];
foreach ($perifs as $p) { $serieToEquipo[$p['sn']] = $p['nombre']; }
?>