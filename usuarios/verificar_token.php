<?php
// Archivo: inventivo_backend/usuarios/verificar_token.php (FINAL)

require '../config/conexion.php'; 

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Obtener el token de la URL
$token = $_GET['token'] ?? '';
$mensaje_error = '';

if (empty($token)) {
    $mensaje_error = "Error: Token de recuperación faltante. El enlace está incompleto.";
} else {
    // 2. Verificar token en la base de datos y su expiración
    $queryUser = "SELECT id, token_expires_at 
                  FROM usuarios 
                  WHERE reset_token = :token 
                  LIMIT 1";
    $stmtUser = $db->prepare($queryUser);
    $stmtUser->bindParam(':token', $token);
    $stmtUser->execute();
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $mensaje_error = "El token es inválido o ya ha sido utilizado para restablecer la contraseña.";
    } else {
        $expira = strtotime($usuario['token_expires_at']);
        if ($expira < time()) {
            $mensaje_error = "El enlace de recuperación ha expirado. Por favor, solicita una nueva recuperación.";
        }
    }
}

// 3. Generar la página HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventivo - Restablecer Contraseña</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f4f4f4; }
        .container { background-color: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .success { color: #2E7D32; font-weight: bold; }
        .error { color: #D32F2F; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($mensaje_error)): ?>
            <h2 class="error">Error de Recuperación</h2>
            <p><?php echo $mensaje_error; ?></p>
            <p>Vuelve a la aplicación para solicitar un nuevo enlace.</p>
        <?php else: ?>
            <h2 class="success">Verificación Exitosa</h2>
            <p>Redirigiendo a la aplicación para ingresar tu nueva contraseña...</p>
            
            <script>
                // 🛑 ESTA ES LA LÍNEA CRÍTICA: REDIRECCIONA AL DEEP LINK DE FLUTTER
                // Si estás usando Flutter Web o Mobile/Desktop, puedes usar:
                
                // NOTA: Reemplaza esta URL por el patrón de tu ruta /reset_password
                // El token se pasa como parámetro de consulta
                window.location.replace("/reset_password?token=<?php echo $token; ?>");
                
                // Si la ruta del frontend requiere la URL completa:
                // window.location.replace("http://192.168.101.25/reset_password?token=<?php echo $token; ?>"); 
            </script>
            <p style="margin-top: 20px;">Si no eres redirigido, haz clic aquí: 
                <a href="/reset_password?token=<?php echo $token; ?>">Ir al Formulario</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>