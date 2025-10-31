<?php
// config/cors.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE"); // <-- CORRECCIÓN CLAVE: Agregamos DELETE
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>