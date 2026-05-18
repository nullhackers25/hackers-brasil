<?php
require 'vendor/autoload.php';
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailConfirmacao($destinatario, $nome, $token) {
    $mail = new PHPMailer(true);

    try {
        // Configuração do SMTP
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = MAIL_PORT;

        // Remetente e destinatário
        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($destinatario, $nome);

        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = '=?UTF-8?B?' . base64_encode('Confirmação de Cadastro - Hackers Brasil') . '?=';

        $mail->Body = '
        <div style="font-family: Arial, sans-serif; background-color: #0d1117; color: #ffffff; padding: 20px; border-radius: 10px;">
            <h2 style="color: #00ffff;">🔐 Hackers Brasil</h2>
            <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
            <p>Você está recebendo este e-mail porque iniciou um cadastro em nosso site. Para concluir o processo, insira o código abaixo na tela de verificação:</p>
            <div style="font-size: 24px; font-weight: bold; background-color: #161b22; padding: 10px 20px; border-radius: 5px; display: inline-block; margin: 15px 0;">
                ' . htmlspecialchars($token) . '
            </div>
            <p>Se você não solicitou este cadastro, ignore este e-mail.</p>
            <hr style="margin: 20px 0; border-color: #333;">
            <footer style="text-align: center; color: #999;">
                  <p>Siga-nos:</p>
                  <a href="https://tiktok.com/@hackersbrasil" style="margin: 0 10px;">
                  <img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" alt="TikTok" width="20" height="20">
                   TikTok
                  </a>
                  <a href="https://instagram.com/hackersbrasil" style="margin: 0 10px;">
                  <img src="https://cdn-icons-png.flaticon.com/512/174/174855.png" alt="Instagram" width="20" height="20">
                   Instagram
                  </a>
            </footer>
        </div>
        ';

        // Texto alternativo (fallback)
        $mail->AltBody = "Olá $nome, seu código de confirmação é: $token";

        // Enviar
        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
?>
