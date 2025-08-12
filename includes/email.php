<?php
// arquivo: ../includes/email.php
function enviarNotificacaoRenovacao($email, $nome, $dominio, $vencimento) {
    $assunto = "Renovação do domínio $dominio";
    $mensagem = "Olá $nome,<br><br>Seu domínio <b>$dominio</b> vence em <b>$vencimento</b>.<br>Por favor, acesse seu painel para renová-lo.<br><br>Equipe de Suporte";

    $headers  = "From: suporte@seusite.com\r\n";
    $headers .= "Reply-To: suporte@seusite.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    mail($email, $assunto, $mensagem, $headers);
}