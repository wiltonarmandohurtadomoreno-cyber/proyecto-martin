<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$volver = '../pages/contacto.html';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $volver);
    exit;
}

// Anti-spam básico: un envío cada 30 segundos por visitante
if (isset($_SESSION['ultimo_contacto']) && time() - $_SESSION['ultimo_contacto'] < 30) {
    mensaje('Espera unos segundos antes de enviar otro mensaje.', $volver);
}

$nombre  = trim((string) ($_POST['name'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$mensajeTxt = trim((string) ($_POST['message'] ?? ''));

if ($nombre === '' || mb_strlen($nombre) > 100) {
    mensaje('Ingresa un nombre válido.', $volver);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    mensaje('Ingresa un correo válido.', $volver);
}
if ($mensajeTxt === '' || mb_strlen($mensajeTxt) > 5000) {
    mensaje('Escribe un mensaje (máximo 5000 caracteres).', $volver);
}

// Busca PHPMailer instalado a mano (carpeta dentro de php/) o con Composer
$cargado = false;
$base = __DIR__ . '/PhpMailer';
$candidatas = [
    $base . '/src/',                          // php/PhpMailer/src/PHPMailer.php
    $base . '/',                              // php/PhpMailer/PHPMailer.php
    $base . '/PHPMailer-master/src/',         // carpeta anidada al descomprimir
    $base . '/PHPMailer/src/',
];
foreach ($candidatas as $src) {
    if (is_file($src . 'PHPMailer.php') && is_file($src . 'SMTP.php') && is_file($src . 'Exception.php')) {
        require $src . 'Exception.php';
        require $src . 'PHPMailer.php';
        require $src . 'SMTP.php';
        $cargado = true;
        break;
    }
}
if (!$cargado && is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    $cargado = true;
}
if (!$cargado) {
    error_log('PHPMailer no encontrado en la carpeta php/');
    mensaje('El envío de correo aún no está configurado en el servidor.', $volver);
}

$cfg = require __DIR__ . '/config_mail.php';

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $cfg['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg['smtp_user'];
    $mail->Password   = $cfg['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $cfg['smtp_port'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($cfg['smtp_user'], $cfg['nombre_sitio']);
    $mail->addAddress($cfg['destino']);
    $mail->addReplyTo($email, $nombre);   // al responder, le contestas al visitante

    $mail->Subject = 'Nuevo mensaje de contacto de ' . str_replace(["\r", "\n"], ' ', $nombre);
    $mail->isHTML(true);
    $fechaHora = date('d/m/Y H:i');
    $sitio     = e($cfg['nombre_sitio']);
    $mail->Body = '
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#d63031;font-family:Segoe UI,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#2d3436;padding:30px 12px;">
<tr><td align="center">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(13,71,161,0.15);">

    <!-- Encabezado -->
    <tr><td style="background-color:#2d3436;background-image:linear-gradient(135deg,#2d3436,#d63031);padding:32px 30px;text-align:center;">
      <div style="font-size:13px;letter-spacing:2px;color:#bbdefb;text-transform:uppercase;">' . $sitio . '</div>
      <h1 style="margin:10px 0 0;font-size:26px;color:#ffffff;font-weight:600;">&#9993; Nuevo mensaje de contacto</h1>
    </td></tr>

    <!-- Datos -->
    <tr><td style="padding:28px 30px 8px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:12px 14px;background-color:#f5f9ff;border-left:4px solid #2d3436;border-radius:6px;">
            <div style="font-size:12px;color:#1565c0;text-transform:uppercase;letter-spacing:1px;">Nombre</div>
            <div style="font-size:17px;color:#0d2a52;font-weight:600;margin-top:3px;">' . e($nombre) . '</div>
          </td>
        </tr>
        <tr><td style="height:10px;"></td></tr>
        <tr>
          <td style="padding:12px 14px;background-color:#f5f9ff;border-left:4px solid #2d3436;border-radius:6px;">
            <div style="font-size:12px;color:#1565c0;text-transform:uppercase;letter-spacing:1px;">Correo electr&oacute;nico</div>
            <div style="font-size:17px;margin-top:3px;"><a href="mailto:' . e($email) . '" style="color:#0d2a52;text-decoration:none;font-weight:600;">' . e($email) . '</a></div>
          </td>
        </tr>
        <tr><td style="height:10px;"></td></tr>
        <tr>
          <td style="padding:12px 14px;background-color:#f5f9ff;border-left:4px solid #2d3436;border-radius:6px;">
            <div style="font-size:12px;color:#1565c0;text-transform:uppercase;letter-spacing:1px;">Fecha y hora</div>
            <div style="font-size:17px;color:#0d2a52;font-weight:600;margin-top:3px;">' . $fechaHora . '</div>
          </td>
        </tr>
      </table>
    </td></tr>

    <!-- Mensaje -->
    <tr><td style="padding:14px 30px 8px;">
      <div style="font-size:12px;color:#1565c0;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Mensaje</div>
      <div style="background-color:#f5f9ff;border:1px solid #bbdefb;border-radius:10px;padding:18px 20px;font-size:16px;line-height:1.6;color:#1a2b45;">' . nl2br(e($mensajeTxt)) . '</div>
    </td></tr>

    <!-- Boton responder -->
    <tr><td align="center" style="padding:24px 30px 30px;">
      <a href="mailto:' . e($email) . '?subject=' . rawurlencode('Re: tu mensaje en ' . $cfg['nombre_sitio']) . '" style="display:inline-block;background-color:#1565c0;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:13px 32px;border-radius:30px;">Responder a ' . e($nombre) . '</a>
    </td></tr>

    <!-- Pie -->
    <tr><td style="background-color:#2d3436;padding:16px 30px;text-align:center;font-size:12px;color:#bbdefb;">
      Este mensaje fue enviado desde el formulario de contacto de ' . $sitio . '.
    </td></tr>

  </table>
</td></tr>
</table>
</body></html>';
    $mail->AltBody = "Nombre: $nombre\nCorreo: $email\nFecha: " . date('d/m/Y H:i') . "\n\nMensaje:\n$mensajeTxt";

    $mail->send();
    $_SESSION['ultimo_contacto'] = time();
} catch (Exception $ex) {
    error_log('Error enviando contacto: ' . $mail->ErrorInfo);
    mensaje('No se pudo enviar el mensaje. Inténtalo más tarde.', $volver);
}

mensaje('¡Gracias! Tu mensaje fue enviado correctamente.', '../index.html', 'Volver al inicio');