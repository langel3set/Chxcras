<?php
namespace App\Controladores;

use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use App\Core\ControladorBase;
use App\Core\Sesion;
use App\Modelos\UsuariosModel;
use App\Config\GoogleConfig;


class LoginControlador extends ControladorBase {
    private $modelo;

    public function __construct() {
        parent::__construct();
        $this->modelo = new UsuariosModel();
    }

public function index() {
    // Asegurar que la sesión esté iniciada
    \App\Core\Sesion::iniciar();

    // Generar token si no existe
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $client = new \Google_Client();
    $client->setClientId(GoogleConfig::CLIENT_ID);
    $client->setClientSecret(GoogleConfig::CLIENT_SECRET);
    $client->setRedirectUri(GoogleConfig::REDIRECT_URI);
    $client->addScope("email");
    $client->addScope("profile");

    $google_login_url = $client->createAuthUrl();

    $this->vista('Login/index', [
        'google_login_url' => $google_login_url
    ]);
}


    public function login() 
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // 🛡️ Verificación CSRF
        if (!Sesion::verificarCsrf($_POST['csrf_token'] ?? '')) {
            die("Token CSRF inválido.");
        }

        // 🧹 Sanitización de datos
        $correo   = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password'] ?? '');

        // ⚠️ Validar campos vacíos
        if ($correo === '' || $password === '') {
            $_SESSION['error'] = "Correo y contraseña requeridos.";
            return $this->redirect(BASE_URL . "login");
        }

        // 🔍 Verificar credenciales
        $usuario = $this->modelo->verificarLogin($correo, $password);

        if ($usuario) {

            // 🚫 Bloquear usuarios inactivos
            if (isset($usuario['estado']) && strtolower($usuario['estado']) === 'inactivo') {
                $_SESSION['error'] = "Tu cuenta está inactiva. Contacta con el administrador.";
                return $this->redirect(BASE_URL . "login");
            }

            // 👤 Avatar por defecto
            $usuario['avatar'] = BASE_URL . 'assets/img/default-avatar.png';

            // 💾 Guardar sesión
            $_SESSION['user'] = $usuario;
            $_SESSION['usuario'] = $usuario;

            // 🔁 Redirigir según rol o nivel
            if ((int) $usuario['id_rol'] === 2) {
                $this->redirigirTrabajador($usuario['id_nivel']);
            } else {
                $this->redirect(BASE_URL . "inicio");
            }
        } 
        else {
            // 🚫 Credenciales incorrectas
            $_SESSION['error'] = "Credenciales inválidas.";
            $this->redirect(BASE_URL . "login");
        }
    }
}

    private function redirigirTrabajador($nivel) {
        $mapa = [
            1 => 'admin',        // usa index()
            2 => 'rrhh',
            3 => 'cocina',
            4 => 'mesero',
            5 => 'inventario',
            6 => 'finanzas'
        ];
        $ruta = $mapa[(int)$nivel] ?? 'login';
        $this->redirect(BASE_URL . $ruta);
    }


    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Sesion::verificarCsrf($_POST['csrf_token'] ?? '')) {
                die("Token CSRF inválido.");
            }

            $nombre   = trim($_POST['nombre'] ?? '');
            $correo   = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm-password'] ?? '';

            if ($nombre === '' || $correo === '' || $password === '' || $confirm === '') {
                $_SESSION['error'] = "Todos los campos son obligatorios.";
                return $this->redirect(BASE_URL . "login");
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Correo inválido.";
                return $this->redirect(BASE_URL . "login");
            }

            if ($password !== $confirm) {
                $_SESSION['error'] = "Las contraseñas no coinciden.";
                return $this->redirect(BASE_URL . "login");
            }

            if ($this->modelo->getUsuarioPorCorreo($correo)) {
                $_SESSION['error'] = "El correo ya está registrado.";
                return $this->redirect(BASE_URL . "login");
            }

            $hash  = password_hash($password, PASSWORD_BCRYPT);
            $newId = $this->modelo->crearUsuarioFormulario($nombre, $correo, $hash);

            $_SESSION[$newId ? 'success' : 'error'] =
                $newId ? "Registro exitoso. Inicia sesión." : "Error al registrar.";
            return $this->redirect(BASE_URL . "login");
        }
    }

    public function logout() {
        if (!isset($_SESSION['user'])) {
            header("Location: " . BASE_URL . "login");
            exit;
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: " . BASE_URL . "login");
        exit;
    }

    public function googleRedirect() {
        $client = new \Google_Client();
        $client->setClientId(GoogleConfig::CLIENT_ID);
        $client->setClientSecret(GoogleConfig::CLIENT_SECRET);
        $client->setRedirectUri(GoogleConfig::REDIRECT_URI);
        $client->addScope("email");
        $client->addScope("profile");
        header("Location: " . $client->createAuthUrl());
        exit;
    }

    public function googleCallback() {
        if (isset($_GET['code'])) {
            $client = new \Google_Client();
            $client->setClientId(GoogleConfig::CLIENT_ID);
            $client->setClientSecret(GoogleConfig::CLIENT_SECRET);
            $client->setRedirectUri(GoogleConfig::REDIRECT_URI);

            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            if (isset($token['error'])) {
                $_SESSION['error'] = "Error autenticando con Google.";
                return $this->redirect(BASE_URL . "login");
            }

            $client->setAccessToken($token['access_token']);
            $oauth      = new \Google_Service_Oauth2($client);
            $googleUser = $oauth->userinfo->get();

            $usuario = $this->modelo->getUsuarioPorGoogleId($googleUser->id);
            if (!$usuario) {
                if ($exist = $this->modelo->getUsuarioPorCorreo($googleUser->email)) {
                    $this->modelo->vincularGoogle($exist['id_usuario'], $googleUser->id);
                    $usuario = $exist;
                } else {
                    $this->modelo->crearUsuarioGoogle(
                        $googleUser->name,
                        $googleUser->email,
                        $googleUser->id
                    );
                    $usuario = $this->modelo->getUsuarioPorCorreo($googleUser->email);
                }
            }

            $usuario['avatar'] = BASE_URL . 'assets/img/default-avatar.png';
            $_SESSION['user'] = $usuario;
            $_SESSION['usuario'] = $usuario;

            return $this->redirect(BASE_URL . "inicio");
        } else {
            $_SESSION['error'] = "No se recibió código de Google.";
            return $this->redirect(BASE_URL . "login");
        }
    }

    public function recuperacion() {
    require_once __DIR__ . '/../Vistas/Login/recuperacion.php';
    }

    public function enviarCodigo() {

    \App\Core\Sesion::iniciar();

    $correo = trim($_POST['correo']);

    if ($correo == '') {
        die("Debes ingresar un correo.");
    }

    $codigo = rand(100000, 999999);

    // Crear la sesión correcta
    $_SESSION['recuperacion'] = [
        'correo' => $correo,
        'codigo' => $codigo,
        'expira' => time() + 300 // 5 minutos
    ];

    // Enviar correo (asegúrate de incluir Mailer)
    $mailer = new Mailer();
    $mailer->enviar(
        $correo,
        "Código de recuperación",
        "Tu código de recuperación es: $codigo"
    );

    header("Location: " . BASE_URL . "login/verificarCodigo");
    exit;
}

    public function verificarCodigo()
    {
        \App\Core\Sesion::iniciar();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_SESSION['recuperacion'])) {
                die("La sesión de recuperación ha expirado.");
            }

            $codigoIngresado = trim($_POST['codigo']);
            $data = $_SESSION['recuperacion'];

            if (time() > $data['expira']) {
                unset($_SESSION['recuperacion']);
                die("Código expirado. Solicítalo de nuevo.");
            }

            if ($codigoIngresado === (string)$data['codigo']) {
                header("Location: " . BASE_URL . "login/restablecer");
                exit;
            }

            $error = "El código es incorrecto.";
        }

        include __DIR__ . '/../Vistas/login/verificar_codigo.php';
    }
   public function restablecer()
{
    if (!isset($_SESSION['recuperacion'])) {
        die("Acceso no autorizado.");
    }

    $error = ""; // <-- evita warnings

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $pass1 = $_POST['pass1'] ?? null;
        $pass2 = $_POST['pass2'] ?? null;

        if ($pass1 === null || $pass2 === null) {
            $error = "Debes completar ambos campos.";
        } elseif ($pass1 !== $pass2) {
            $error = "Las contraseñas no coinciden.";
        } else {

            $correo = $_SESSION['recuperacion']['correo'];
            $hash = password_hash($pass1, PASSWORD_DEFAULT);

            $this->modelo->actualizarPasswordPorCorreo($correo, $hash);

            unset($_SESSION['recuperacion']);

            $_SESSION['mensaje_exito'] = "Contraseña actualizada correctamente.";

            header("Location: " . BASE_URL . "login");
            exit;
        }
    }

    include __DIR__ . '/../Vistas/login/restablecer.php';
}
public function actualizarPasswordPorCorreo($correo, $nuevoPassword)
{
    $sql = "UPDATE usuarios SET contrasena = ? WHERE correo = ?";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$nuevoPassword, $correo]);
}
public function loginApi() 
{
    header("Content-Type: application/json; charset=utf-8");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
        return;
    }

    $correo   = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if ($correo === '' || $password === '') {
        echo json_encode(['status' => 'error', 'msg' => 'Correo y contraseña requeridos']);
        return;
    }

    $usuario = $this->modelo->verificarLogin($correo, $password);

    if (!$usuario) {
        echo json_encode(['status' => 'error', 'msg' => 'Credenciales incorrectas']);
        return;
    }

    // Guardar sesión en PHP
    $_SESSION['user']    = $usuario;
    $_SESSION['usuario'] = $usuario;

    echo json_encode([
        'status' => 'ok',
        'correo' => $usuario['correo'],
        'usuario' => [
            'id' => $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo']
        ]
    ]);
}

}
