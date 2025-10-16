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
$urlVerificacion = "https://ciesju.udenar.edu.co/app/icons2025/certificar.html?ip=" . urlencode($cedula) . "&rol=" . urlencode($rol);
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
        $this->Image('../../img/diplomaFondo2.jpg', 0, 0, 279.4, 215.9); // CORRECTO
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
$pdf->SetFont('Arial','B',28);
$pdf->SetTextColor(29,29,27);
$pdf->SetXY(0,80);
$pdf->Cell(279.4,10,utf8_decode($nombre),0,1,'C');

// Documento de identificación
$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(87,87,86);
$pdf->SetXY(0,95);
$pdf->Cell(279.4,10,utf8_decode("Identificado(a) con $docTipoLargo"),0,1,'C');

// Participación
$pdf->SetTextColor(32,57,106);
$pdf->SetXY(0,110);
$pdf->Cell(279.4,10,utf8_decode("Participó en calidad de $calidadTexto en el"),0,1,'C');

// Firma y QR
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','B',12);
$pdf->SetXY(0, 170);
$pdf->Cell(140,10,utf8_decode("LEONARDO A. ENRÍQUEZ MARTÍNEZ\nDecano - Facultad de Derecho y Ciencias Políticas"),0,0,'C');
$pdf->Cell(140,10,utf8_decode("CRISTHIAN ALEXANDER PEREIRA OTERO\nDirector - CIESJU Universidad de Nariño"),0,1,'C');

$pdf->Image($imageData, 220, 20, 35, 35, 'PNG');

// ---------------------------------------------------
// Salida PDF
// ---------------------------------------------------
$pdf->Output("I", "Certificado_ICON-S_2025_$cedula.pdf");
exit();
?>
