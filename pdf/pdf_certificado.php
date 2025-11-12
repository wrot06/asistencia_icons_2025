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
$id_ponencia = $_POST['id_panel'] ?? 0;
$nombre = trim($_POST['moderador'] ?? '');
if (!$id_ponencia || !$nombre) die("Faltan datos del autor o ID de ponencia.");

// 🔹 Obtener datos de la ponencia
$stmt = $pdo->prepare("SELECT * FROM panel WHERE id = ?");
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
$textoVisible = "Verificar autenticidad";
// 🔹 Generar QR usando tu API
$qrApiUrl = "https://ciesju.udenar.edu.co/qr-api/generate-qr/?data=" . urlencode($urlVerificacion);
$imageString = @file_get_contents($qrApiUrl);
if ($imageString === false) die("Error al obtener el QR desde la API FastAPI.");
$imageData = 'data://image/png;base64,' . base64_encode($imageString);

// -------------------------------------------------------
// 🧾 Crear el PDF
class PDF extends FPDF {
    public $qrData;
    public $id_ponencia;

    function __construct($qrData, $id_ponencia) {
        parent::__construct('L', 'mm', 'Letter');
        $this->qrData = $qrData;
        $this->id_ponencia = $id_ponencia;
    }

    function Header() {
        if ($this->id_ponencia == 273) {
            $this->Image('../img/CERTIFICADO_PONENTE_M.jpg', 0, 0, 279.4, 215.9);
        } elseif($this->id_ponencia == 265) {
            $this->Image('../img/CERTIFICADO_PONENTE_MD.jpg', 0, 0, 279.4, 215.9);
        }
        else {
            $this->Image('../img/CERTIFICADO_PONENTE.jpg', 0, 0, 279.4, 215.9);
        }
    }
}

// Instancia del PDF
$pdf = new PDF($imageData, $id_ponencia);
$pdf->AddPage();
$pdf->SetMargins(25, 25, 25);
$pdf->SetAutoPageBreak(true, 35);
$pdf->SetTitle(utf8_decode("CERTIFICACIÓN ($nombre) IconS 2025 - Universidad de Nariño"));
$pdf->SetFont('Times', '', 14);

// Nombre del asistente
$pdf->AddFont('Touche-Regular-BF642a2ebfe9ff0','',"Touche-Regular-BF642a2ebfe9ff0.php");
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',28);
$pdf->SetTextColor(29,29,27);
$pdf->SetXY(0,85);
$pdf->Cell(279.4,10,utf8_decode($nombre),0,1,'C');


// 🔹 Coordenadas manuales (posición del texto)
$pdf->SetXY(18, 55); // X = 50mm, Y = 130mm
// 🔹 Cambiar color del texto (RGB)
$pdf->SetTextColor(143, 188, 190); // Rojo
// Ejemplo: (0,0,255)=azul, (0,0,0)=negro, (255,0,0)=rojo, (0,128,0)=verde
// 🔹 Fuente personalizada
$pdf->AddFont('Touche-Regular-BF642a2ebfe9ff0','',"Touche-Regular-BF642a2ebfe9ff0.php");
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',9);
// 🔹 Texto visible
$textoVisible = "Verificar autenticidad";
// 🔹 Mostrar texto con enlace (clickeable)
$pdf->Write(6, $textoVisible, $urlVerificacion);
// 🔹 Volver al color normal (negro)
$pdf->SetTextColor(0, 0, 0);





// QR
$pdf->Image($pdf->qrData, 16, 16, 40, 40, 'PNG');

// Salida
$nombreArchivo = "Certificado ICON-S2025 (" . utf8_decode ( $nombre) . ").pdf";
$pdf->Output("I", $nombreArchivo);
exit;
?>
