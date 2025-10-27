<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controllers/InsumoController.php';

$database = new Database();
$db = $database->getConnection();

$keyword = $_GET['q'] ?? '';

$controller = new InsumoController($db);
$controller->buscar($keyword);
?>
