<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Libraries/Mailer.php';

class UsuarioController {

    public function enviarBienvenida($correoDestino) {

        // Cargar plantilla HTML
        ob_start();
        include __DIR__ . '/../../app/Views/emails/bienvenida.php';
        $html = ob_get_clean();

        // Enviar correo
        $mailer = new Mailer();
        $r = $mailer->enviar($correoDestino, "Bienvenido!", $html);

        if ($r === true) {
            echo "Correo enviado correctamente";
        } else {
            echo $r; // muestra error
        }
    }
}