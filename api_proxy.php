<?php 
// api_proxy.php — Reenvía peticiones del navegador al backend FastAPI

$backend_url = "http://10.10.10.109:8001";
$endpoint = $_GET['endpoint'] ?? '';
$url = rtrim($backend_url, '/') . '/' . ltrim($endpoint, '/');

// Permitir CORS para frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Salida rápida para OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// No limitar por IP
/*
$frontend_ip = $_SERVER['REMOTE_ADDR'];
if ($frontend_ip !== '10.10.10.35') {
    http_response_code(403);
    echo json_encode(["error" => "Acceso no autorizado ($frontend_ip)"]);
    exit;
}
*/

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Si es POST, reenviar cuerpo y cabeceras
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
}

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la conexión al backend", "detalle" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

http_response_code($http_code);
header('Content-Type: application/json');
echo $response;
?>
