<?php
// FILE: app/Services/Mailer.php

namespace App\Services;

class Mailer
{
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->fromEmail = 'no-reply@converclick.net';
        $this->fromName = APP_NAME;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ]);

        $result = mail($to, $subject, $body, $headers);
        
        if (!$result) {
            error_log("Mailer Error: Failed to send email to $to. Check server mail configuration.");
        }
        
        return $result;
    }

    /**
     * Envia o email de recuperação de senha
     */
    public function sendPasswordReset(string $email, string $token): bool
    {
        $link = APP_URL . "/reset-password?token=" . $token;
        
        $subject = "Recuperación de Contraseña - " . APP_NAME;
        
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;'>
                <h2 style='color: #0d6efd;'>Restablecer Contraseña</h2>
                <p>Hola,</p>
                <p>Hemos recibido una solicitud para restablecer la contraseña de su cuenta en <strong>" . APP_NAME . "</strong>.</p>
                <p>Si usted no realizó esta solicitud, puede ignorar este correo de forma segura.</p>
                <p>Para restablecer su contraseña, haga clic en el siguiente botón:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$link}' style='background-color: #0d6efd; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Restablecer Contraseña</a>
                </p>
                <p style='font-size: 0.9em; color: #666;'>O copie y pegue el siguiente enlace en su navegador:<br>
                <a href='{$link}'>{$link}</a></p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 0.8em; color: #999;'>Este enlace es válido por 24 horas.</p>
            </div>
        </body>
        </html>
        ";

        return $this->send($email, $subject, $body);
    }
}
