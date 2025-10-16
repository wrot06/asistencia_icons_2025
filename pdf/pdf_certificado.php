<?php
// Rutas a FPDF y al archivo de secretos
require_once __DIR__ . '/../../pdf/fpdf.php';

// 🔹 Cargar clave HMAC desde /app/config/secrets.php
$cfgPath = __DIR__ . '/../../config/secrets.php';

if (!file_exists($cfgPath)) {
    die("Archivo de configuración no encontrado: $cfgPath");
}
$cfg = require $cfgPath;
$hmac_secret = $cfg['hmac_secret'] ?? null;
if (!$hmac_secret) die("Secreto HMAC no configurado.");

// 🔹 Configuración de base de datos
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
$id_ponencia = $_POST['id_ponencia'] ?? 0;
$nombre = trim($_POST['nombre'] ?? '');
if (!$id_ponencia || !$nombre) die("Faltan datos del autor o ID de ponencia.");

// 🔹 Obtener datos de la ponencia
$stmt = $pdo->prepare("SELECT * FROM ponencias WHERE id = ?");
$stmt->execute([$id_ponencia]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) die("Ponencia no encontrada.");

// 🔹 Fecha actual
setlocale(LC_TIME, 'es_CO.UTF-8');
$fecha = strftime("%d de %B de %Y");

// === Generar token seguro (HMAC-SHA256 + base64url) ===
$timestamp = time();
$payload = $id_ponencia . '|' . $nombre . '|' . ($p['titulo_ponencia'] ?? '') . '|' . $timestamp;
$hmac = hash_hmac('sha256', $payload, $hmac_secret, true);
$token_b64url = rtrim(strtr(base64_encode($hmac . '|' . $timestamp), '+/', '-_'), '=');

// Guardar token en la tabla certificados
try {
    $stmtIns = $pdo->prepare("INSERT INTO certificados (ponencia_id, nombre, token, created_by) VALUES (?, ?, ?, ?)");
    $stmtIns->execute([$id_ponencia, $nombre, $token_b64url, 'pdf_certificado']);
} catch (PDOException $e) {
    // Si el certificado ya existe, no interrumpimos
}

// 🔹 URL de verificación (incluye el token)
$urlVerificacion = "https://ciesju.udenar.edu.co/app/icons2025/certificar.html?ip=" . urlencode($id_ponencia) . "&tok=" . urlencode($token_b64url);

// 🔹 Generar QR usando tu API
$qrApiUrl = "https://ciesju.udenar.edu.co/qr-api/generate-qr/?data=" . urlencode($urlVerificacion);
$imageString = @file_get_contents($qrApiUrl);
if ($imageString === false) die("Error al obtener el QR desde la API FastAPI.");
$imageData = 'data://image/png;base64,' . base64_encode($imageString);

// -------------------------------------------------------
// 🧾 Crear el PDF
class PDF extends FPDF {
    public $qrData;
    function __construct($qrData) {
        parent::__construct();
        $this->qrData = $qrData;
    }
    function Footer() {
        global $urlVerificacion;
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(80);
        $this->Cell(0, 5, utf8_decode("Verifique este certificado en:"), 0, 1, 'L');
        $this->SetTextColor(0, 0, 200);
        $this->Cell(0, 5, $urlVerificacion, 0, 0, 'L');
    }
}

$pdf = new PDF($imageData);
$pdf->AddPage();
$pdf->SetMargins(25, 25, 25);
$pdf->SetAutoPageBreak(true, 35);
$pdf->SetFont('Times', '', 14);

// Logo
$logoPath = __DIR__ . '/logo_udenar.png';
if (file_exists($logoPath)) $pdf->Image($logoPath, 80, 10, 50);
$pdf->Ln(40);

// Título
$pdf->SetFont('Times', 'B', 18);
$pdf->Cell(0, 10, utf8_decode('CERTIFICACIÓN'), 0, 1, 'C');
$pdf->Ln(10);

// Texto
$pdf->SetFont('Times', '', 12);
$texto = "El Centro de Investigaciones y Estudios Socio-Jurídicos (CIESJU) de la Universidad de Nariño hace constar que 
$nombre participó en el VII Seminario Nacional ICON-S 'Democracia, Derechos Humanos e Inteligencia Artificial', 
con la ponencia titulada: '" . ($p['titulo_ponencia'] ?: 'Sin título registrado') . "'. 
Este evento académico se realizó los días 22, 23 y 24 de octubre de 2025, con una intensidad de 30 horas académicas, 
en las instalaciones de la Universidad de Nariño, en la ciudad de Pasto, República de Colombia.

En constancia de lo anterior, se firma en San Juan de Pasto, $fecha.";

$pdf->MultiCell(0, 8, utf8_decode($texto));
$pdf->Ln(15);

// Firmas
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(95, 8, utf8_decode('LEONARDO A. ENRÍQUEZ MARTÍNEZ'), 0, 0, 'C');
$pdf->Cell(95, 8, utf8_decode('CRISTHIAN ALEXANDER PEREIRA OTERO'), 0, 1, 'C');
$pdf->SetFont('Times', '', 12);
$pdf->Cell(95, 8, utf8_decode('Decano - Facultad de Derecho y Ciencias Políticas'), 0, 0, 'C');
$pdf->Cell(95, 8, utf8_decode('Director - CIESJU Universidad de Nariño'), 0, 1, 'C');
$pdf->Ln(20);

// QR
$pdf->Image($pdf->qrData, 160, 230, 35, 35, 'PNG');

// Salida
$nombreArchivo = "Certificado_ICON-S_2025_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre) . ".pdf";
$pdf->Output("I", $nombreArchivo);
exit;
?>
