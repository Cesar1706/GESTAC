<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

$conn = new mysqli("localhost","root","","GESTAC");
if ($conn->connect_error) die("Error de conexión");

$nomina = $_GET['nomina'] ?? '';
$activoCodigo = $_GET['activo'] ?? '';

$sqlUser = "SELECT * FROM usuarios WHERE nomina = ?";
$stmt = $conn->prepare($sqlUser);
$stmt->bind_param("s", $nomina);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$sqlActivo = "SELECT * FROM activos WHERE id_producto = ?";
$stmt2 = $conn->prepare($sqlActivo);
$stmt2->bind_param("s", $activoCodigo);
$stmt2->execute();
$activo = $stmt2->get_result()->fetch_assoc();

if(!$user || !$activo){
  die("Datos no encontrados");
}

$e = fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');

$html = "
<style>
body{font-family:Arial;}
h2{text-align:center;}
table{width:100%;border-collapse:collapse;}
td{border:1px solid #000;padding:6px;}
</style>

<h2>CARTA RESPONSIVA</h2>

<p>Yo, <strong>{$e($user['nombre'])}</strong>, con nómina <strong>{$e($user['nomina'])}</strong>, adscrito a <strong>{$e($user['ubicacion'])}</strong>, recibo el siguiente equipo:</p>

<table>
<tr><td><b>Equipo</b></td><td>{$e($activo['nombre'])}</td></tr>
<tr><td><b>Marca</b></td><td>{$e($activo['marca'])}</td></tr>
<tr><td><b>Procesador</b></td><td>{$e($activo['procesador'])}</td></tr>
<tr><td><b>RAM</b></td><td>{$e($activo['ram'])}</td></tr>
<tr><td><b>Almacenamiento</b></td><td>{$e($activo['almacenamiento'])}</td></tr>
<tr><td><b>Lote</b></td><td>{$e($activo['lote'])}</td></tr>
</table>

<p>Me comprometo a cuidar el equipo y devolverlo en buen estado.</p>

<br><br>
<p>Firma: ____________________________</p>
<p>Fecha: ".date('d/m/Y')."</p>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream("Carta_{$user['nombre']}.pdf", ["Attachment"=>1]);