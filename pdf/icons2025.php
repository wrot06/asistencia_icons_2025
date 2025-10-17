<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---------------------------------------------------
// Rutas a FPDF y configuración de base de datos
// ---------------------------------------------------
require_once __DIR__ . '/../../pdf/fpdf.php';

require('../rene/conexion5.php'); // conexión MySQL
if (!isset($_POST['identificacion']) || !isset($_POST['rol'])) exit('Datos incompletos.');

$document_number = trim($_POST['identificacion']);
$rol             = strtoupper(trim($_POST['rol']));

// ---------------------------------------------------
// Buscar asistente en la tabla attendees
// ---------------------------------------------------
$stmt = $conec->prepare("
    SELECT id, full_name, document_type, document_number
    FROM attendees
    WHERE document_number = ?
    LIMIT 1
");
if (!$stmt) exit("Error en la preparación de la consulta: " . $conec->error);

$stmt->bind_param("s", $document_number);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) exit("No se encontró el asistente.");
$datos = $result->fetch_assoc();
$stmt->close();

// ---------------------------------------------------
// Preparar datos del PDF
// ---------------------------------------------------
$nombre   = $datos['full_name'];
$cedula   = $datos['document_number'];
$docTipo  = strtoupper(trim($datos['document_type']));

function getDocTipoLargo($docTipo, $cedula) {
    switch($docTipo) {
        case 'CC': return "Cédula de Ciudadanía número $cedula";
        case 'TI': return "Tarjeta de Identidad número $cedula";
        case 'CEDULA DE EXTRANJERIA':
        case 'CÉDULA DE EXTRANJERÍA': return "Cédula de Extranjería número $cedula";
        case 'PASAPORTE': return "Pasaporte número $cedula";
        case 'TEMP': return "Número temporal $cedula";
        default: return "Documento número $cedula";
    }
}

$docTipoLargo = getDocTipoLargo($docTipo, $cedula);
$calidadTexto = $rol;

// ---------------------------------------------------
// Generar QR usando la API externa
// ---------------------------------------------------
// 🔹 URL de verificación (incluye el token)

$urlVerificacion = "https://ciesju.udenar.edu.co/app/icons2025/icons2025.php?identificacion=" . urlencode($cedula);
$textoVisible = "Verificar autenticidad";

$qrApiUrl = "https://ciesju.udenar.edu.co/qr-api/generate-qr/?data=" . urlencode($urlVerificacion);

$imageString = @file_get_contents($qrApiUrl);
if ($imageString === false) exit("Error al obtener el QR desde la API.");
$imageData = 'data://image/png;base64,' . base64_encode($imageString);

// ---------------------------------------------------
// Clase PDF personalizada
// ---------------------------------------------------
class PDF extends FPDF {
    public $qrData;
    function __construct($qrData) {
        parent::__construct('L', 'mm', 'Letter');
        $this->qrData = $qrData;
    }
    function Header() {
        $this->Image('../img/CERTIFICADO_ASISTENTE.jpg', 0, 0, 279.4, 215.9); // CORRECTO
    }
}


// ---------------------------------------------------
// Generar PDF
// ---------------------------------------------------
$pdf = new PDF($imageData);
$pdf->SetMargins(0,0,0);
$pdf->SetAutoPageBreak(false);
$pdf->SetTitle(utf8_decode("CERTIFICACIÓN ($cedula) IconS 2025 - Universidad de Nariño"));
$pdf->AddPage();

// Nombre del asistente
$pdf->AddFont('Touche-Regular-BF642a2ebfe9ff0','',"Touche-Regular-BF642a2ebfe9ff0.php");
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',28);
$pdf->SetTextColor(29,29,27);
$pdf->SetXY(0,85);
$pdf->Cell(279.4,10,utf8_decode($nombre),0,1,'C');

// Documento de identificación
$pdf->AddFont('Touche-Regular-BF642a2ebfe9ff0','',"Touche-Regular-BF642a2ebfe9ff0.php");
$pdf->SetFont('Touche-Regular-BF642a2ebfe9ff0','',12);
$pdf->SetTextColor(87,87,86);
$pdf->SetXY(0,93.5);
$pdf->Cell(279.4,10,utf8_decode("Quien se identifica con $docTipoLargo"),0,1,'C');



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


$pdf->Image($imageData, 16, 16, 40, 40, 'PNG');

// ---------------------------------------------------
// Salida PDF
// ---------------------------------------------------
$pdf->Output("I", "Certificado ICON-S 2025 ($cedula) - UDENAR.pdf");
exit();
?>
