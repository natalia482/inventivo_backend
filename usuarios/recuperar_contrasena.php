<?php
// Archivo: inventivo_backend/usuarios/recuperar_contrasena.php (CORREGIDO)
require '../config/cors.php';
require '../config/conexion.php';

// ✅ Asegúrate que estas rutas sean correctas para tu instalación de PHPMailer
// Si usas Composer, probablemente solo necesitas 'vendor/autoload.php'
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Incluye los archivos si no usas Composer
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
// FIN DE INCLUSIONES DE PHPMailer

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... */ }

$data = json_decode(file_get_contents("php://input"), true);
$correo = $data['correo'] ?? null;

if (empty($correo)) { /* ... */ }

// 1. Verificar si el usuario existe
$queryUser = "SELECT id, nombre, apellido FROM usuarios WHERE correo = :correo LIMIT 1";
$stmtUser = $db->prepare($queryUser);
$stmtUser->bindParam(':correo', $correo);
$stmtUser->execute();
$usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(["success" => true, "message" => "Si la dirección de correo existe, recibirás un enlace para restablecer tu contraseña."]);
    exit;
}

// 2. Generar Token y Expiración (1 hora)
$token = bin2hex(random_bytes(32)); 
$expira = date('Y-m-d H:i:s', time() + 3600); 

try {
    $db->beginTransaction();

    // 3. Guardar Token en la Base de Datos
    $queryUpdate = "UPDATE usuarios 
                    SET reset_token = :token, token_expires_at = :expira
                    WHERE id = :id";
    $stmtUpdate = $db->prepare($queryUpdate);
    $stmtUpdate->execute([
        ':token' => $token,
        ':expira' => $expira,
        ':id' => $usuario['id']
    ]);

    $db->commit();

    // 4. Construir el Enlace de Recuperación (Ajusta la URL aquí)
    // ✅ Reemplaza con tu IP/Dominio de la aplicación frontend
    $dominio_base = "http://localhost:63909"; // O la base de tu proyecto PHP
    $enlace_recuperacion = $dominio_base . "/#/reset_password?token=" . $token;
    // =========================================================================
    // ✅ 5. LÓGICA DE ENVÍO DE CORREO (DESCOMENTADA Y CONFIGURADA)
    // =========================================================================

    $mail = new PHPMailer(true);
    try {
        // Configuración del Servidor
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // ⬅️ CAMBIA ESTO: Tu Servidor SMTP (ej. smtp.gmail.com)
        $mail->SMTPAuth = true;
        $mail->Username = 'inventivonj@gmail.com'; // ⬅️ CAMBIA ESTO: Tu Correo de Envío
        $mail->Password = 'gisozvutbnuqlxka'; // ⬅️ CAMBIA ESTO: Tu Contraseña SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Usar TLS
        $mail->Port = 587; // Puerto

        // Contenido del Correo
        $mail->setFrom('no-reply@inventivo.com', 'Inventivo Viveros');
        $mail->addAddress($correo, $usuario['nombre']);
        $mail->isHTML(true);



        $mail->Subject = 'Recuperación de Contraseña - Inventivo';
        $mail->Body    = "Hola {$usuario['nombre']},<br><br>
                          Hemos recibido una solicitud para restablecer tu contraseña.<br>
                          Haz clic en el siguiente enlace:<br><br>
                          <a href=\"$enlace_recuperacion\">Restablecer Contraseña Ahora</a><br><br>
                          Si no solicitaste este cambio, ignora este correo.<br>
                          Este enlace expirará en 1 hora.";

        $mail->send();
        
        // Respuesta de éxito de envío
        $mensaje_exito = "Si la dirección de correo existe, recibirás un enlace para restablecer tu contraseña.";
        
    } catch (Exception $e) {
        // En caso de fallo de envío, el token ya fue guardado.
        error_log("Fallo el envío de correo: " . $mail->ErrorInfo);
        $mensaje_exito = "Se guardó el token, pero falló el envío del correo. Contacte al soporte. (Motivo: " . $mail->ErrorInfo . ")";
    }


    // 6. Respuesta final
    echo json_encode(["success" => true, "message" => $mensaje_exito]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de base de datos al guardar token: " . $e->getMessage()]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error interno: " . $e->getMessage()]);
}
?>