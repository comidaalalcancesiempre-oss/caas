<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

// ── Credenciales Gmail ─────────────────────────────────────────────
define('GMAIL_USER',         'comidaallacancesiempre@gmail.com');
define('GMAIL_APP_PASSWORD', 'ghnxqxanrfnbtkuf');
define('ADMIN_EMAIL',        'comidaallacancesiempre@gmail.com');
define('SITE_NAME',          'C.A.A.S. - Comida Al Alcance Siempre');

/**
 * Función base para enviar cualquier email
 * @param string $destinatario Email del destinatario
 * @param string $nombre_dest  Nombre del destinatario
 * @param string $asunto       Asunto del email
 * @param string $htmlBody     Cuerpo en HTML
 * @param string $textBody     Cuerpo en texto plano (fallback)
 */
function enviarEmail(string $destinatario, string $nombre_dest, string $asunto, string $htmlBody, string $textBody = ''): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host        = 'smtp.gmail.com';
        $mail->SMTPAuth    = true;
        $mail->Username    = GMAIL_USER;
        $mail->Password    = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port        = 587;
        $mail->CharSet     = 'UTF-8';
        $mail->Timeout     = 10;

        $mail->setFrom(GMAIL_USER, SITE_NAME);
        $mail->addAddress($destinatario, $nombre_dest);
        $mail->addReplyTo(GMAIL_USER, SITE_NAME);

        $mail->isHTML(true);
        $mail->Subject = '[C.A.A.S.] ' . $asunto;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[CAAS Mailer] Error enviando a ' . $destinatario . ': ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envía notificación al administrador
 */
function enviarNotificacionAdmin(string $asunto, string $htmlBody, string $textBody = ''): bool {
    return enviarEmail(ADMIN_EMAIL, 'Admin C.A.A.S.', $asunto, $htmlBody, $textBody);
}

/**
 * Envía email de recuperación de contraseña al usuario
 */
function enviarEmailRecuperacion(string $emailUsuario, string $linkReset): bool {
    $html = "
    <!DOCTYPE html><html><body style='margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;'>
    <div style='max-width:480px;margin:32px auto;background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;'>
        <div style='background:#f97316;padding:20px 32px;'>
            <h1 style='margin:0;color:#fff;font-size:20px;font-weight:900;'>C.A.A.S.</h1>
            <p style='margin:4px 0 0;color:#fed7aa;font-size:12px;'>Comida Al Alcance Siempre</p>
        </div>
        <div style='padding:28px 32px;'>
            <h2 style='color:#1e293b;font-size:18px;margin:0 0 12px;'>Recuperá tu contraseña</h2>
            <p style='color:#64748b;font-size:13px;line-height:1.6;'>Recibimos una solicitud para restablecer la contraseña de tu cuenta. Hacé clic en el botón para continuar:</p>
            <div style='text-align:center;margin:24px 0;'>
                <a href='{$linkReset}' style='background:#f97316;color:#fff;font-weight:bold;padding:14px 32px;border-radius:12px;text-decoration:none;font-size:14px;display:inline-block;'>
                    Restablecer contraseña →
                </a>
            </div>
            <div style='background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:12px;margin-bottom:16px;'>
                <p style='margin:0;font-size:12px;color:#92400e;'><strong>⏰ Este link expira en 30 minutos.</strong></p>
            </div>
            <p style='color:#94a3b8;font-size:11px;'>Si no solicitaste esto, ignorá este email. Tu contraseña no cambiará. Este link es de un solo uso.</p>
            <hr style='border:none;border-top:1px solid #f1f5f9;margin:16px 0;'>
            <p style='color:#94a3b8;font-size:10px;text-align:center;'>C.A.A.S. — Comida Al Alcance Siempre</p>
        </div>
    </div>
    </body></html>";

    return enviarEmail(
        $emailUsuario,
        'Usuario C.A.A.S.',
        'Recuperación de contraseña',
        $html,
        "Para restablecer tu contraseña, usá este link (válido 30 min): {$linkReset}"
    );
}

/**
 * HTML del email de nueva empresa para el admin
 */
function emailNuevaEmpresa(array $datos): string {
    $nombre    = htmlspecialchars($datos['nombre']    ?? '—');
    $email     = htmlspecialchars($datos['email']     ?? '—');
    $telefono  = htmlspecialchars($datos['telefono']  ?? '—');
    $categoria = htmlspecialchars($datos['categoria'] ?? '—');
    $direccion = htmlspecialchars($datos['direccion'] ?? '—');
    $horarios  = htmlspecialchars($datos['horarios']  ?? '—');
    $fecha     = date('d/m/Y H:i');
    $tel_limpio = preg_replace('/[^0-9]/', '', $datos['telefono'] ?? '');
    $wa_link   = "https://wa.me/{$tel_limpio}?text=" . urlencode(
        "Hola {$nombre}, somos el equipo de C.A.A.S. 🍔\n\n" .
        "Recibimos tu solicitud de registro como empresa y necesitamos verificar que sea legítima antes de aprobarte.\n\n" .
        "¿Podés confirmar los datos de tu local?\n" .
        "- Categoría: {$categoria}\n" .
        "- Dirección: {$direccion}\n\n" .
        "¡Gracias! Te respondemos a la brevedad."
    );

    return "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;'>
    <div style='max-width:520px;margin:32px auto;background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;'>
        <div style='background:#f97316;padding:24px 32px;'>
            <h1 style='margin:0;color:#fff;font-size:22px;font-weight:900;'>C.A.A.S.</h1>
            <p style='margin:4px 0 0;color:#fed7aa;font-size:13px;'>Panel de Administración</p>
        </div>
        <div style='padding:28px 32px;'>
            <h2 style='margin:0 0 6px;color:#1e293b;font-size:18px;'>🆕 Nueva empresa registrada</h2>
            <p style='margin:0 0 20px;color:#64748b;font-size:13px;'>Está pendiente de verificación. <strong>Contactá a la empresa por WhatsApp</strong> para verificar su legitimidad antes de aprobarla.</p>
            <table style='width:100%;border-collapse:collapse;font-size:13px;'>
                <tr style='border-bottom:1px solid #f1f5f9;'><td style='padding:10px 8px;color:#64748b;font-weight:bold;width:38%;'>Nombre</td><td style='padding:10px 8px;color:#1e293b;'>{$nombre}</td></tr>
                <tr style='border-bottom:1px solid #f1f5f9;'><td style='padding:10px 8px;color:#64748b;font-weight:bold;'>Email</td><td style='padding:10px 8px;color:#1e293b;'>{$email}</td></tr>
                <tr style='border-bottom:1px solid #f1f5f9;'><td style='padding:10px 8px;color:#64748b;font-weight:bold;'>Teléfono</td><td style='padding:10px 8px;color:#1e293b;'>{$telefono}</td></tr>
                <tr style='border-bottom:1px solid #f1f5f9;'><td style='padding:10px 8px;color:#64748b;font-weight:bold;'>Categoría</td><td style='padding:10px 8px;color:#1e293b;'>{$categoria}</td></tr>
                <tr style='border-bottom:1px solid #f1f5f9;'><td style='padding:10px 8px;color:#64748b;font-weight:bold;'>Dirección</td><td style='padding:10px 8px;color:#1e293b;'>{$direccion}</td></tr>
                <tr><td style='padding:10px 8px;color:#64748b;font-weight:bold;'>Fecha</td><td style='padding:10px 8px;color:#1e293b;'>{$fecha}</td></tr>
            </table>
            <div style='margin-top:24px;text-align:center;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;'>
                <a href='{$wa_link}' style='background:#25D366;color:#fff;font-weight:bold;padding:12px 20px;border-radius:12px;text-decoration:none;font-size:13px;'>
                    📱 Contactar por WhatsApp
                </a>
                <a href='http://localhost/caas_nuevo/admin.php' style='background:#f97316;color:#fff;font-weight:bold;padding:12px 20px;border-radius:12px;text-decoration:none;font-size:13px;'>
                    Panel Admin →
                </a>
            </div>
        </div>
    </div></body></html>";
}
