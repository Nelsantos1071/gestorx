<?php
// arquivo: ../includes/enviar_email.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclui os arquivos do PHPMailer
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function enviarEmailSMTP($para, $assunto, $mensagem) {
    $mail = new PHPMailer(true);

    try {
        // Ativar depuração SMTP para diagnóstico
        $mail->SMTPDebug = 2; // 0 = sem debug, 2 = debug detalhado
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer: [$level] $str");
        };

        // Configurações do servidor SMTP (SendGrid com SSL)
        $mail->isSMTP();
        $mail->Host       = 'smtp.sendgrid.net';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'apikey';
        $mail->Password   = 'SUA_CHAVE_API_SENDGRID'; // Substitua pela sua chave API do SendGrid
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465; // Porta SSL

        // Configurações de codificação
        $mail->CharSet = 'UTF-8';

        // Remetente e destinatário
        $mail->setFrom('no-reply@gestorx.com', 'GestorX'); // Domínio corrigido
        $mail->addAddress($para);

        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $mensagem;
        $mail->AltBody = strip_tags($mensagem);

        $mail->send();
        error_log("E-mail enviado com sucesso para $para via SMTP");
        return true;
    } catch (Exception $e) {
        error_log("Erro no envio do e-mail para $para via SMTP: {$mail->ErrorInfo}");
        // Fallback para mail()
        try {
            $headers  = "From: no-reply@gestorx.com\r\n";
            $headers .= "Reply-To: no-reply@gestorx.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            if (mail($para, $assunto, $mensagem, $headers)) {
                error_log("E-mail enviado com sucesso para $para via mail()");
                return true;
            } else {
                error_log("Erro no envio do e-mail para $para via mail()");
                return "Erro ao enviar via mail()";
            }
        } catch (Exception $e) {
            error_log("Erro no envio do e-mail para $para via mail(): {$e->getMessage()}");
            return "Erro ao enviar via mail(): {$e->getMessage()}";
        }
    }
}