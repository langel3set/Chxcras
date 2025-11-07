<?php
namespace App\Controladores;

use App\Core\Sesion;

class UsuariosControlador
{
    public function verificarSesion()
    {
        Sesion::iniciar();

        if (Sesion::get('user')) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'no', 'message' => 'No hay sesión activa']);
        }
    }

    // Opcional: función de login si quieres centralizarla aquí
    public function login()
    { 
    }
}