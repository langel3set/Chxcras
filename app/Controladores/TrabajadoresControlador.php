<?php
namespace App\Controladores;

use App\Core\ControladorBase;
use App\Core\Sesion;

class TrabajadoresControlador extends ControladorBase {

public function index() {
    Sesion::iniciar();
    $usuario = $_SESSION['usuario'] ?? null;

    if (!$usuario || ($usuario['id_rol'] ?? '') != 2) {
        $_SESSION['error'] = "Acceso no autorizado.";
        return $this->redirect(BASE_URL . "login");
    }

    // 🔒 Verificación adicional: si el usuario está inactivo, bloquear acceso
    if (isset($usuario['estado']) && $usuario['estado'] === 'inactivo') {
        session_destroy();
        $_SESSION['error'] = "Tu cuenta está inactiva. No puedes acceder al sistema.";
        header("Location: " . BASE_URL . "login");
        exit;
    }

    $nivel = $usuario['nivel'] ?? 'sin nivel';

    $this->vista('trabajadores/dashboard', [
        'nivel' => $nivel,
        'usuario' => $usuario
    ]);
}

}
