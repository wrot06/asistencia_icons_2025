<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../pdf/fpdf.php';

$cfgPath = __DIR__ . '/../../config/secrets.php';
if (!file_exists($cfgPath)) die("Archivo de configuración no encontrado: $cfgPath");
$cfg = require $cfgPath;
$hmac_secret = $cfg['hmac_secret'] ?? null;
if (!$hmac_secret) die("Secreto HMAC no configurado.");

// 🔹 Config DB
$host = "10.10.10.109";
$username = "icons_user";
$password = "Icons2025!";
$database = "icons2025";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 🔹 Parámetros recibidos
$id_panel   = $_POST['id_panel'] ?? 0;
$moderador  = trim($_POST['moderador'] ?? '');
$panel      = trim($_POST['panel'] ?? '');


if (empty($id_panel) || empty($moderador) || empty($panel)) {
    die("Faltan datos del moderador o del panel.");
}

// 🔹 Obtener datos del panel
$stmt = $pdo->prepare("SELECT * FROM panel WHERE id = ?");
$stmt->execute([$id_panel]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) die("Panel no encontrado.");

// 🔹 Fecha
setlocale(LC_TIME, 'es_CO.UTF-8');
$fecha = strftime("%d de %B de %Y");

// === Token seguro HMAC
$timestamp = time();
$payload = $id_panel . '|' . $moderador . '|' . ($p['no'] ?? '') . '|' . $timestamp;
$hmac = hash_hmac('sha256', $payload, $hmac_secret, true);
$token_b64url = rtrim(strtr(base64_encode($hmac . '|' . $timestamp), '+/', '-_'), '=');

// Guardar token (evita error si ya existe)
try {
    $stmtIns = $pdo->prepare("INSERT INTO certificados_panel (panel_id, nombre, token, created_by) VALUES (?, ?, ?, ?)");
    $stmtIns->execute([$id_panel, $moderador, $token_b64url, 'pdf_certificado_panel']);
} catch (PDOException $e) {
    // Ignorar si ya existe
}

// 🔹 URL de verificación
$urlVerificacion = "https://ciesju.udenar.edu.co/app/icons2025/certificar.html?ip2=" . urlencode($id_panel) . "&tok=" . urlencode($token_b64url);

// 🔹 QR
$qrApiUrl = "https://ciesju.udenar.edu.co/qr-api/generate-qr/?data=" . urlencode($urlVerificacion);
$imageString = @file_get_contents($qrApiUrl);
if ($imageString === false) die("Error al obtener el QR desde la API.");
$imageData = 'data://image/png;base64,' . base64_encode($imageString);

// -------------------------------------------------------
class PDF extends FPDF {
    public $qrData;
    function __construct($qrData) {
        parent::__construct('L', 'mm', 'Letter');
        $this->qrData = $qrData;
    }
    function Header() {
        $this->Image('../img/CERTIFICADO_PANEL.jpg', 0, 0, 279.4, 215.9);
    }
}

// Instancia PDF
$pdf = new PDF($imageData);
$pdf->AddPage();
$pdf->SetMargins(25, 25, 25);
$pdf->SetAutoPageBreak(true, 35);
$pdf->SetTitle(utf8_decode("Certificación de Panel ($moderador) - ICONS 2025"));
$pdf->SetFont('Times', '', 14);

// 🔹 Nombre del moderador
$pdf->AddFont('Touche-Regular-BF642a2ebfe9ff0','',"Touche-Regular-BF642a2ebfe9ff0.php");
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',28);
$pdf->SetTextColor(29,29,27);
$pdf->SetXY(0,85);
$pdf->Cell(279.4,10,utf8_decode($moderador),0,1,'C');

// 🔹 Texto del panel
$pdf->AddFont('Touche-Regular-BF642a2ebfe9ff0','',"Touche-Regular-BF642a2ebfe9ff0.php");
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',12);
$pdf->SetXY(25, 97);
$pdf->MultiCell(230, 8, utf8_decode("Por su participación como moderador del panel"), 0, 'C');
$pdf->SetXY(25, 104);
$pdf->MultiCell(230, 4, utf8_decode($panel), 0, 'C');


// 🔹 Enlace de verificación
$pdf->SetXY(18, 55);
$pdf->SetTextColor(143, 188, 190);
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',9);
$pdf->Write(6, "Verificar autenticidad", $urlVerificacion);
$pdf->SetTextColor(0, 0, 0);

// 🔹 QR
$pdf->Image($pdf->qrData, 16, 16, 40, 40, 'PNG');

// Salida final
$nombreArchivo = "Certificado Panel ICON-S2025 (" . utf8_decode($moderador) . ").pdf";
$pdf->Output("I", $nombreArchivo);
exit;
?>
