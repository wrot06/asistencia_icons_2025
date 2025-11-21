<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../pdf/fpdf.php';

// 🔹 Configuración HMAC
$cfgPath = __DIR__ . '/../../config/secrets.php';
if (!file_exists($cfgPath)) die("Archivo de configuración no encontrado: $cfgPath");
$cfg = require $cfgPath;
$hmac_secret = $cfg['hmac_secret'] ?? null;
if (!$hmac_secret) die("Secreto HMAC no configurado.");

// 🔹 Parámetros recibidos desde el formulario
$id_comentarista = $_POST['id_comentarista'] ?? 0;
$comentarista    = trim($_POST['comentarista'] ?? '');
$libro           = trim($_POST['titulo_libro'] ?? '');

if (empty($id_comentarista) || empty($comentarista) || empty($libro)) {
    die("Faltan datos del comentarista o del libro.");
}

// 🔹 Fecha
setlocale(LC_TIME, 'es_CO.UTF-8');
$fecha = strftime("%d de %B de %Y");

// 🔹 Token seguro HMAC
$timestamp = time();
$payload = $id_comentarista . '|' . $comentarista . '|' . $libro . '|' . $timestamp;
$hmac = hash_hmac('sha256', $payload, $hmac_secret, true);
$token_b64url = rtrim(strtr(base64_encode($hmac . '|' . $timestamp), '+/', '-_'), '=');

// 🔹 URL de verificación y QR
$urlVerificacion = "https://ciesju.udenar.edu.co/app/icons2025/certificar.html?ip4=" 
                   . urlencode($id_comentarista) 
                   . "&com=" . urlencode($comentarista) 
                   . "&tok=" . urlencode($token_b64url);
                   
$qrApiUrl = "https://ciesju.udenar.edu.co/qr-api/generate-qr/?data=" . urlencode($urlVerificacion);
$imageString = @file_get_contents($qrApiUrl);
if ($imageString === false) die("Error al obtener el QR desde la API.");
$imageData = 'data://image/png;base64,' . base64_encode($imageString);

// -------------------------------------------------------
// Clase PDF
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

// -------------------------------------------------------
// Instancia PDF
$pdf = new PDF($imageData);
$pdf->AddPage();
$pdf->SetMargins(25, 25, 25);
$pdf->SetAutoPageBreak(true, 35);
$pdf->SetTitle(utf8_decode("Certificado Comentarista ($comentarista) - ICONS 2025"));

// 🔹 Nombre del comentarista
$pdf->AddFont('Touche-Regular-BF642a2ebfe9ff0','',"Touche-Regular-BF642a2ebfe9ff0.php");
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',28);
$pdf->SetTextColor(29,29,27);
$pdf->SetXY(0,85);
$pdf->Cell(279.4,10,utf8_decode($comentarista),0,1,'C');

// 🔹 Texto del libro
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',12);
$pdf->SetXY(25, 97);
$pdf->MultiCell(230, 8, utf8_decode("Por ser comentarista del libro titulado:"), 0, 'C');

// Ajustar tamaño de fuente si el título es muy largo
$tituloLength = strlen($libro);
if ($tituloLength > 70) {
    $pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',10);
} else {
    $pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',12);
}
$pdf->SetXY(25, 104);
$pdf->MultiCell(230, 6, utf8_decode($libro), 0, 'C');

// 🔹 Enlace de verificación
$pdf->SetXY(18, 55);
$pdf->SetTextColor(143, 188, 190);
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',9);
$pdf->Write(6, "Verificar autenticidad", $urlVerificacion);
$pdf->SetTextColor(0, 0, 0);

// 🔹 QR
$pdf->Image($pdf->qrData, 16, 16, 40, 40, 'PNG');

// 🔹 Salida PDF
$nombreArchivo = "Certificado Comentarista ICON-S2025 (" . utf8_decode($comentarista) . ").pdf";
$pdf->Output("I", $nombreArchivo);
exit;
