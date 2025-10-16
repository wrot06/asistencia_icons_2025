<?php
$username = "icons_user";          // Usuario creado en MySQL
$password = "Icons2025!";          // Contraseña asignada al usuario
$database = "icons2025";           // Nombre de la base de datos
$host     = "10.10.10.109";        // Dirección del servidor MySQL (ajústalo si es local: "localhost")

$conec = new mysqli($host, $username, $password, $database);

// Verificar conexión
if ($conec->connect_error) {
    die("Error de conexión: " . $conec->connect_error);
}

// Forzar UTF-8
if (!$conec->set_charset("utf8mb4")) {
    die("Error al establecer el conjunto de caracteres UTF-8: " . $conec->error);
}
?>
