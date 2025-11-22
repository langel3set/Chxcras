<?php
namespace App\Controladores;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    public function enviar($destino, $asunto, $cuerpo) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'lasolutions354@gmail.com'; 
            $mail->Password = 'ocgd dyht ifiw niwb';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('lasolutions354@gmail.com', 'Sistema');
            $mail->addAddress($destino);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpo;

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}