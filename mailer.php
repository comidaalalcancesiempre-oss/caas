<?php
/**
 * C.A.A.S. — Sistema de notificaciones por email
 * Usa PHPMailer con SMTP de Gmail
 *
 * CONFIGURACIÓN REQUERIDA:
 * 1. Ir a https://myaccount.google.com/security
 * 2. Activar "Verificación en dos pasos"
 * 3. Ir a "Contraseñas de aplicación" → Generar una para "Correo / Otro"
 * 4. Pegar esa clave de 16 caracteres en GMAIL_APP_PASSWORD abajo
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

// ─── Configuración de credenciales ────────────────────────────────
define('GMAIL_USER',         'comidaallacancesiempre@gmail.com');
define('GMAIL_APP_PASSWORD', 'ghnxqxanrfnbtkuf'); // ← reemplazar con tu clave de app
define('ADMIN_EMAIL',        'comidaallacancesiempre@gmail.com');
define('SITE_NAME',          'C.A.A.S. - Comida Al Alcance Siempre');

/**
 * Envía un email al administrador del sistema.
 *
 * @param string $asunto  Asunto del correo
 * @param string $cuerpoHtml Cuerpo en HTML
 * @param string $cuerpoTexto Cuerpo en texto plano (fallback)
 * @return bool true si se envió, false si falló
 */
function enviarNotificacionAdmin(string $asunto, string $cuerpoHtml, string $cuerpoTexto = ''): bool {
    $mail = new PHPMailer(true);

    try {
        // ── Configuración SMTP ──────────────────────────────────
        $mail->isSMTP();
        $mail->Host        = 'smtp.gmail.com';
        $mail->SMTPAuth    = true;
        $mail->Username    = GMAIL_USER;
        $mail->Password    = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port        = 587;
        $mail->CharSet     = 'UTF-8';
        $mail->Timeout     = 10;

        // ── Remitente y destinatario ────────────────────────────
        $mail->setFrom(GMAIL_USER, SITE_NAME);
        $mail->addAddress(ADMIN_EMAIL, 'Admin C.A.A.S.');
        $mail->addReplyTo(GMAIL_USER, SITE_NAME);

        // ── Contenido ───────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = '[C.A.A.S.] ' . $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = $cuerpoTexto ?: strip_tags($cuerpoHtml);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[CAAS Mailer] Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Genera el HTML del email de nueva empresa registrada.
 */
function emailNuevaEmpresa(array $datos): string {
    $nombre    = htmlspecialchars($datos['nombre']    ?? '—');
    $email     = htmlspecialchars($datos['email']     ?? '—');
    $telefono  = htmlspecialchars($datos['telefono']  ?? '—');
    $categoria = htmlspecialchars($datos['categoria'] ?? '—');
    $direccion = htmlspecialchars($datos['direccion'] ?? '—');
    $horarios  = htmlspecialchars($datos['horarios']  ?? '—');
    $fecha     = date('d/m/Y H:i');

    return <<<HTML
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;">
      <div style="max-width:520px;margin:32px auto;background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">

        <!-- Header -->
        <div style="background:#f97316;padding:24px 32px;">
          <h1 style="margin:0;color:#fff;font-size:22px;font-weight:900;">C.A.A.S.</h1>
          <p style="margin:4px 0 0;color:#fed7aa;font-size:13px;">Comida Al Alcance Siempre</p>
        </div>

        <!-- Cuerpo -->
        <div style="padding:28px 32px;">
          <h2 style="margin:0 0 6px;color:#1e293b;font-size:18px;">🆕 Nueva empresa registrada</h2>
          <p style="margin:0 0 20px;color:#64748b;font-size:13px;">
            Se registró una nueva empresa y está <strong style="color:#f97316;">pendiente de verificación</strong>.
            Accedé al panel para aprobarla o rechazarla.
          </p>

          <!-- Datos -->
          <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:10px 8px;color:#64748b;font-weight:bold;width:38%;">Nombre</td>
              <td style="padding:10px 8px;color:#1e293b;">{$nombre}</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:10px 8px;color:#64748b;font-weight:bold;">Email</td>
              <td style="padding:10px 8px;color:#1e293b;">{$email}</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:10px 8px;color:#64748b;font-weight:bold;">Teléfono</td>
              <td style="padding:10px 8px;color:#1e293b;">{$telefono}</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:10px 8px;color:#64748b;font-weight:bold;">Categoría</td>
              <td style="padding:10px 8px;color:#1e293b;">{$categoria}</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:10px 8px;color:#64748b;font-weight:bold;">Dirección</td>
              <td style="padding:10px 8px;color:#1e293b;">{$direccion}</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:10px 8px;color:#64748b;font-weight:bold;">Horarios</td>
              <td style="padding:10px 8px;color:#1e293b;">{$horarios}</td>
            </tr>
            <tr>
              <td style="padding:10px 8px;color:#64748b;font-weight:bold;">Fecha registro</td>
              <td style="padding:10px 8px;color:#1e293b;">{$fecha}</td>
            </tr>
          </table>

          <!-- CTA -->
          <div style="margin-top:24px;text-align:center;">
            <a href="http://localhost/caas_nuevo/admin.php"
               style="display:inline-block;background:#f97316;color:#fff;font-weight:bold;padding:12px 28px;border-radius:12px;text-decoration:none;font-size:14px;">
              Ir al Panel Admin →
            </a>
          </div>
        </div>

        <!-- Footer -->
        <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 32px;text-align:center;">
          <p style="margin:0;font-size:11px;color:#94a3b8;">
            Este email fue generado automáticamente por C.A.A.S.<br>
            No respondas este correo.
          </p>
        </div>

      </div>
    </body>
    </html>
    HTML;
}
