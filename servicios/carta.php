<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../configuracion/base_datos.php';

use Dompdf\Dompdf;

$nomina       = $_GET['nomina'] ?? '';
$codigoActivo = $_GET['activo'] ?? '';

if (empty($nomina) || empty($codigoActivo)) {
    die('Parámetros incompletos');
}

try {
    $pdo = obtenerConexion();

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nomina = ?");
    $stmt->execute([$nomina]);
    $usuario = $stmt->fetch();

    $stmt2 = $pdo->prepare("SELECT * FROM activos WHERE id_producto = ?");
    $stmt2->execute([$codigoActivo]);
    $activo = $stmt2->fetch();

    if (!$usuario || !$activo) {
        die('Datos no encontrados');
    }

} catch (Throwable $e) {
    die('Error de conexión');
}

$e = fn($s) => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');

$html = "
<style>
body { font-family: Arial; }
h2 { text-align: center; }
table { width: 100%; border-collapse: collapse; }
td { border: 1px solid #000; padding: 6px; }
</style>

<h2>CARTA RESPONSIVA</h2>

<p>Yo, <strong>{$e($usuario['nombre'])}</strong>, con nómina <strong>{$e($usuario['nomina'])}</strong>,
adscrito a <strong>{$e($usuario['ubicacion'])}</strong>, recibo el siguiente equipo:</p>

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
<p>Fecha: " . date('d/m/Y') . "</p>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Carta_{$e($usuario['nombre'])}.pdf", ['Attachment' => 1]);
