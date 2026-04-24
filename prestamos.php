<?php
// data_prestamos.php — Conecta a GESTAC y emite JS con datos para el HTML (autodetección)
// Accede a este archivo directo en el navegador para verificar: http://localhost/tu-ruta/data_prestamos.php

header('Content-Type: application/javascript; charset=utf-8');

$DB_HOST = 'localhost';
$DB_NAME = 'GESTAC';
$DB_USER = 'root';
$DB_PASS = ''; // cambia si tu root tiene contraseña

function js_echo($code){ echo $code, "\n"; }

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
  js_echo("console.error('DB connect error:', ".json_encode($e->getMessage()).");");
  js_echo("window.PREST_DATA = { usuarios: [], perifericos: [], error: ".json_encode($e->getMessage())." };");
  exit;
}

/* ---------- utilidades ---------- */
function table_exists(PDO $pdo, $table){
  try {
    $stmt = $pdo->query("SHOW TABLES LIKE ".$pdo->quote($table));
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) { return false; }
}
function column_exists(PDO $pdo, $table, $column){
  try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
    $stmt->execute([':t'=>$table, ':c'=>$column]);
    return $stmt->fetchColumn() > 0;
  } catch (Throwable $e) { return false; }
}

/* ---------- detectar tablas ---------- */
$tblUsersCandidates = ['usuarios', 'user', 'users'];
$tblInvCandidates   = ['inventario_perifericos', 'inventario_periferico', 'perifericos', 'inventario'];

$tblUsers = null;
foreach ($tblUsersCandidates as $t) { if (table_exists($pdo, $t)) { $tblUsers = $t; break; } }
$tblInv   = null;
foreach ($tblInvCandidates as $t) { if (table_exists($pdo, $t)) { $tblInv = $t; break; } }

/* ---------- consultar usuarios.nombre ---------- */
$usuarios = [];
$warn = [];

if ($tblUsers) {
  // detectar columna de nombre
  $nameCols = ['nombre','name','usuario'];
  $nameCol = null;
  foreach ($nameCols as $c) { if (column_exists($pdo, $tblUsers, $c)) { $nameCol = $c; break; } }
  if ($nameCol === null) {
    $warn[] = "No se encontró columna de nombre en tabla $tblUsers (probé: ".implode(',',$nameCols).")";
  } else {
    try {
      $sql = "SELECT `$nameCol` AS nombre FROM `$tblUsers` WHERE `$nameCol` IS NOT NULL AND `$nameCol`<>'' ORDER BY `$nameCol` ASC";
      $stmt = $pdo->query($sql);
      foreach ($stmt as $r) { $usuarios[] = $r['nombre']; }
    } catch (Throwable $e) {
      $warn[] = "Error consultando usuarios: ".$e->getMessage();
    }
  }
} else {
  $warn[] = "No se encontró ninguna tabla de usuarios (probé: ".implode(', ',$tblUsersCandidates).")";
}

/* ---------- consultar inventario: nombre + serie ---------- */
$perifericos = [];
if ($tblInv) {
  // detectar columnas
  $nameCols  = ['nombre','equipo','descripcion','nombre_equipo'];
  $serieCols = ['S/N','S_N','serie','serial','no_serie','sn'];
  $nameCol = null; $serieCol = null;

  foreach ($nameCols as $c)  { if (column_exists($pdo, $tblInv, $c))  { $nameCol = $c; break; } }
  foreach ($serieCols as $c) { if (column_exists($pdo, $tblInv, $c)) { $serieCol = $c; break; } }

  if ($nameCol === null)  { $warn[] = "No se encontró columna de nombre en $tblInv (probé: ".implode(',',$nameCols).")"; }
  if ($serieCol === null) { $warn[] = "No se encontró columna de serie en $tblInv (probé: ".implode(',',$serieCols).")"; }

  if ($nameCol && $serieCol) {
    try {
      $sql = "SELECT `$nameCol` AS nombre, `$serieCol` AS sn
              FROM `$tblInv`
              WHERE `$nameCol` IS NOT NULL AND `$nameCol`<>'' AND `$serieCol` IS NOT NULL AND `$serieCol`<>'' 
              ORDER BY `$nameCol` ASC, `$serieCol` ASC";
      $stmt = $pdo->query($sql);
      foreach ($stmt as $r) {
        $perifericos[] = ['nombre'=>$r['nombre'], 'sn'=>(string)$r['sn']];
      }
    } catch (Throwable $e) {
      $warn[] = "Error consultando inventario: ".$e->getMessage();
    }
  }
} else {
  $warn[] = "No se encontró ninguna tabla de inventario (probé: ".implode(', ',$tblInvCandidates).")";
}

/* ---------- salida ---------- */
js_echo("window.PREST_DATA = ".json_encode(['usuarios'=>$usuarios,'perifericos'=>$perifericos], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).";");
js_echo("console.log('PREST_DATA counts',{usuarios: ".count($usuarios).", perifericos: ".count($perifericos)."});");
if (!empty($warn)) {
  js_echo("console.warn('PREST_DATA warnings:', ".json_encode($warn, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).");");
}
