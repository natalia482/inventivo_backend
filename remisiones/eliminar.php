<?php
require_once '../controllers/remisionController.php';
require_once '../config/cors.php';

$controller = new RemisionController();

// El controlador ahora lee los datos JSON directamente del cuerpo.
$controller->eliminarRemision(null); 
?>