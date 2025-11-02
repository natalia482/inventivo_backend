<?php
// Archivo: inventivo_backend/config/cors.php (Completo)

// Permitir acceso desde cualquier origen (*)
header("Access-Control-Allow-Origin: *");
// Permitir los métodos HTTP utilizados (GET, POST, OPTIONS)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
// Permitir los encabezados que Flutter/navegador pueden enviar
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Manejar preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>