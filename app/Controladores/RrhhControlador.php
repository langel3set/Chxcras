<?php
namespace App\Controladores;

use App\Core\ControladorBase;
use App\Core\BaseDatos;
use App\Core\Sesion;
use App\Modelos\AuditModel; // modelo de auditoría
use Exception;
use PDOException;

class RrhhControlador extends ControladorBase
{
    private $conexion;
    private $audit;

    public function __construct()
    {
        Sesion::iniciar();
        $this->conexion = BaseDatos::getInstancia();
        $this->audit = new AuditModel();
    }

    public function index()
    {
        if (!$this->verificarAcceso([1,2])) return;

        $query = "
            SELECT 
                u.id_usuario, 
                u.nombre, 
                u.correo, 
                u.dni, 
                u.celular, 
                r.nombre AS rol, 
                n.nombre AS nivel, 
                u.id_rol, 
                u.id_nivel, 
                u.estado
            FROM Usuarios u
            JOIN Roles r ON u.id_rol = r.id_rol
            JOIN Niveles n ON u.id_nivel = n.id_nivel
            ORDER BY u.id_usuario DESC
        ";
        
        $trabajadores = $this->conexion->query($query)->fetchAll();

        include __DIR__ . '/../Vistas/rrhh/trabajadores.php';
    }


   public function crear()
{
    if (!$this->verificarAcceso([1, 2])) return;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $roles = $this->conexion->query("SELECT id_rol, nombre FROM Roles WHERE id_rol != 1")->fetchAll();
    $niveles = $this->conexion->query("SELECT id_nivel, nombre FROM Niveles")->fetchAll();

    $mensaje_error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $tipo_doc = trim($_POST['tipo_doc'] ?? 'DNI');
        $dni = trim($_POST['dni'] ?? '');
        $id_rol = (int)($_POST['id_rol'] ?? 2);
        $id_nivel = (int)($_POST['id_nivel'] ?? 2);
        $celular = trim($_POST['celular'] ?? '');

        // VALIDACIONES ---------------------------------------------------------
        if ($nombre === '' || $correo === '' || $dni === '' || $celular === '' || $id_nivel <= 0) {
            $mensaje_error = "⚠️ Todos los campos son obligatorios.";
        } elseif (!preg_match('/^[^\d]{1,50}$/u', $nombre)) {
            $mensaje_error = "⚠️ El nombre solo puede contener letras y símbolos.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensaje_error = "⚠️ El formato del correo no es válido.";
        } elseif ($tipo_doc === 'DNI' && !preg_match('/^\d{8}$/', $dni)) {
            $mensaje_error = "⚠️ El DNI debe tener 8 dígitos.";
        } elseif ($tipo_doc === 'PASAPORTE' && !preg_match('/^\d{9}$/', $dni)) {
            $mensaje_error = "⚠️ El pasaporte debe tener 9 dígitos.";
        } elseif (!preg_match('/^\d{9}$/', $celular)) {
            $mensaje_error = "⚠️ El celular debe tener 9 dígitos.";
        } else {
            try {
                // DUPLICIDAD -----------------------------------------------------
                $stmtCheck = $this->conexion->prepare("
                    SELECT COUNT(*) as cnt 
                    FROM Usuarios 
                    WHERE correo = ? OR dni = ? OR celular = ?
                ");
                $stmtCheck->execute([$correo, $dni, $celular]);
                $row = $stmtCheck->fetch();
                $existe = (int)($row['cnt'] ?? 0);

                if ($existe > 0) {
                    $mensaje_error = "⚠️ Ya existe un usuario con ese correo, DNI o celular.";
                } else {

                    // -----------------------------------------------------------------
                    // 1. GENERAR CONTRASEÑA AUTOMÁTICA
                    // -----------------------------------------------------------------
                    $prefix = strtolower(substr($nombre, 0, 3));
                    $suffix = substr($dni, -3);
                    $random = rand(10, 99);

                    $password_plain = $prefix . $suffix . $random;
                    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                    // -----------------------------------------------------------------
                    // 2. INSERTAR EN BASE DE DATOS
                    // -----------------------------------------------------------------
                    $this->conexion->beginTransaction();

                    $stmt = $this->conexion->prepare("
                        INSERT INTO Usuarios (nombre, correo, contrasena, id_rol, id_nivel, dni, celular, estado)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')
                    ");
                    $stmt->execute([
                        $nombre,
                        $correo,
                        $password_hash,
                        $id_rol,
                        $id_nivel,
                        $dni,
                        $celular
                    ]);

                    $nuevoId = $this->conexion->lastInsertId();

                    // AUDITORÍA -----------------------------------------------------
                    $this->audit->crearRegistro([
                        'user_id' => $_SESSION['usuario']['id_usuario'] ?? null,
                        'user_name' => $_SESSION['usuario']['nombre'] ?? 'Sistema',
                        'action' => 'crear',
                        'entity' => 'Usuarios',
                        'entity_id' => (string)$nuevoId,
                        'field_name' => null,
                        'old_value' => null,
                        'new_value' => json_encode([
                            'nombre' => $nombre,
                            'correo' => $correo,
                            'dni' => $dni,
                            'tipo_doc' => $tipo_doc,
                            'celular' => $celular,
                            'id_nivel' => $id_nivel
                        ])
                    ]);

                    $this->conexion->commit();
                    require_once __DIR__ . '/Mailer.php';

                    $mailer = new Mailer();

                    $mensajeCorreo = "
                        <h2>¡Bienvenido, $nombre!</h2>
                        <p>Tu cuenta ha sido creada correctamente.</p>
                        <p><b>Usuario:</b> $correo</p>
                        <p><b>Contraseña temporal:</b> $password_plain</p>
                        <p>Por favor, inicia sesión y cambia tu contraseña.</p>
                    ";

                    $mailer->enviar($correo, "Acceso al sistema", $mensajeCorreo);

                    $_SESSION['mensaje_exito'] = "✅ Empleado registrado y correo enviado.";
                    header("Location: " . BASE_URL . "rrhh");
                    exit;
                }
            } catch (\PDOException $e) {
                if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                $mensaje_error = "Error al registrar empleado: " . $e->getMessage();
            } catch (\Exception $e) {
                if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                $mensaje_error = "Error inesperado: " . $e->getMessage();
            }
        }
    }

    include __DIR__ . '/../Vistas/rrhh/crear.php';
}

    public function editar($id = null)
{
    if (!$this->verificarAcceso([1, 2])) return;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!$id) {
        echo "ID no válido.";
        exit;
    }

    // Obtener trabajador (valores antiguos)
    $stmt = $this->conexion->prepare("SELECT * FROM Usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
    $trabajador = $stmt->fetch();

    if (!$trabajador) {
        echo "<div class='mensaje-error'>🚫 Trabajador no encontrado en la base de datos.</div>";
        exit;
    }

    $niveles = $this->conexion->query("SELECT id_nivel, nombre FROM Niveles")->fetchAll();

    $mensaje_error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $id_nivel = (int)($_POST['id_nivel'] ?? 0);
        // No permitimos editar dni/pasaporte (no usamos $_POST['dni'])
        $dni_old = $trabajador['dni'] ?? '';
        $celular = trim($_POST['celular'] ?? '');
        $estado = trim($_POST['estado'] ?? ($trabajador['estado'] ?? 'activo'));
        $estado = ($estado === 'inactivo') ? 'inactivo' : 'activo';

        // Validaciones servidor
        if ($nombre === '' || $correo === '' || $celular === '' || $id_nivel <= 0) {
            $mensaje_error = "⚠️ Todos los campos son obligatorios.";
        } elseif (!preg_match('/^[^\d]{1,50}$/u', $nombre)) {
            $mensaje_error = "⚠️ El nombre solo puede contener letras y símbolos (sin números) y máximo 50 caracteres.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensaje_error = "⚠️ El formato del correo no es válido.";
        } elseif (!preg_match('/^\d{9}$/', $celular)) {
            $mensaje_error = "⚠️ El celular debe tener exactamente 9 números.";
        } else {
            try {
                // Verificar duplicidad excluyendo el propio id (correo y celular; dni no se modifica)
                $stmtCheck = $this->conexion->prepare("
                    SELECT COUNT(*) as cnt
                    FROM Usuarios
                    WHERE (correo = ? OR celular = ?) AND id_usuario != ?
                ");
                $stmtCheck->execute([$correo, $celular, $id]);
                $row = $stmtCheck->fetch();
                $existe = (int)($row['cnt'] ?? $row[0] ?? 0);

                if ($existe > 0) {
                    $mensaje_error = "⚠️ Ya existe otro usuario con el mismo correo o número de celular.";
                } else {
                    // Iniciamos transacción
                    $this->conexion->beginTransaction();

                    // UPDATE: NO cambiamos DNI
                    $stmt = $this->conexion->prepare("
                        UPDATE Usuarios
                        SET nombre = ?, correo = ?, id_nivel = ?, celular = ?, estado = ?
                        WHERE id_usuario = ?
                    ");
                    $stmt->execute([$nombre, $correo, $id_nivel, $celular, $estado, $id]);

                    // Auditoría: comparar y guardar cambios (excluyendo dni)
                    $userId = $_SESSION['usuario']['id_usuario'] ?? null;
                    $userName = $_SESSION['usuario']['nombre'] ?? ($_SESSION['usuario']['user_name'] ?? null);

                    $campos = ['nombre','correo','celular','id_nivel','estado'];
                    foreach ($campos as $campo) {
                        $oldVal = $trabajador[$campo] ?? null;
                        $newVal = null;
                        if ($campo === 'id_nivel') $newVal = $id_nivel;
                        elseif ($campo === 'estado') $newVal = $estado;
                        elseif ($campo === 'celular') $newVal = $celular;
                        else $newVal = ${$campo} ?? null;

                        if ((string)$oldVal !== (string)$newVal) {
                            $this->audit->crearRegistro([
                                'user_id' => $userId,
                                'user_name' => $userName,
                                'action' => 'editar',
                                'entity' => 'Usuarios',
                                'entity_id' => (string)$id,
                                'field_name' => $campo,
                                'old_value' => (string)$oldVal,
                                'new_value' => (string)$newVal,
                            ]);
                        }
                    }

                    $this->conexion->commit();

                    // Si el usuario se desactiva a sí mismo, forzar cierre
                    if ((int)$id === (int)($_SESSION['usuario']['id_usuario'] ?? 0) && $estado === 'inactivo') {
                        $_SESSION = [];
                        if (ini_get("session.use_cookies")) {
                            $params = session_get_cookie_params();
                            setcookie(session_name(), '', time() - 42000,
                                $params["path"], $params["domain"], $params["secure"], $params["httponly"]
                            );
                        }
                        session_destroy();
                        session_start();
                        $_SESSION['mensaje_exito'] = "Has sido desactivado. Contacta al administrador.";
                        header("Location: " . BASE_URL . "login");
                        exit;
                    }

                    $_SESSION['mensaje_exito'] = "✅ Empleado actualizado con éxito.";
                    header("Location: " . BASE_URL . "rrhh");
                    exit;
                }
            } catch (\PDOException $e) {
                if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                $mensaje_error = "Error al actualizar empleado: " . $e->getMessage();
            } catch (\Exception $e) {
                if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                $mensaje_error = "Error inesperado: " . $e->getMessage();
            }
        }
    }

    include __DIR__ . '/../Vistas/rrhh/editar.php';
}


    public function eliminar($id = null)
    {
        if (!$this->verificarAcceso([1, 2])) return;

        if ($id) {
            if ($id == 1) {
                $_SESSION['mensaje_exito'] = "⚠️ No puedes eliminar al administrador principal.";
                header("Location: " . BASE_URL . "rrhh");
                exit;
            }

            try {
                // obtener datos antes de eliminar
                $stmt = $this->conexion->prepare("SELECT nombre, correo, dni, celular FROM Usuarios WHERE id_usuario = ?");
                $stmt->execute([$id]);
                $old = $stmt->fetch();

                // Iniciar transacción
                $this->conexion->beginTransaction();

                $stmt = $this->conexion->prepare("DELETE FROM Usuarios WHERE id_usuario = ?");
                $stmt->execute([$id]);

                // registrar auditoría
                $this->audit->crearRegistro([
                    'user_id' => $_SESSION['usuario']['id_usuario'] ?? null,
                    'user_name' => $_SESSION['usuario']['nombre'] ?? ($_SESSION['usuario']['user_name'] ?? null),
                    'action' => 'eliminar',
                    'entity' => 'Usuarios',
                    'entity_id' => (string)$id,
                    'field_name' => null,
                    'old_value' => json_encode($old),
                    'new_value' => null,
                ]);

                $this->conexion->commit();

                // Mensaje de éxito
                $_SESSION['mensaje_exito'] = "🗑️ Empleado eliminado con éxito.";
            } catch (PDOException $e) {
                if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                $_SESSION['mensaje_exito'] = "Error al eliminar empleado: " . $e->getMessage();
            } catch (Exception $e) {
                if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                $_SESSION['mensaje_exito'] = "Error inesperado: " . $e->getMessage();
            }
        }

        header("Location: " . BASE_URL . "rrhh");
        exit;
    }


    // NUEVA ACCIÓN: exportar la lista como CSV
    public function exportar()
    {
        if (!$this->verificarAcceso([1,2])) return;

        // Consulta: ahora incluye estado
        $query = "
            SELECT u.nombre, u.correo, r.nombre AS rol, n.nombre AS nivel, u.dni, u.celular, u.estado
            FROM Usuarios u
            JOIN Roles r ON u.id_rol = r.id_rol
            JOIN Niveles n ON u.id_nivel = n.id_nivel
            ORDER BY u.id_usuario DESC
        ";
        $rows = $this->conexion->query($query)->fetchAll();

        // Nombre de archivo con fecha (YYYY-MM-DD)
        $fecha = date('Y-m-d');
        $filename = "Trabajadores_{$fecha}.csv";

        // Headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $output = fopen('php://output', 'w');

        // Encabezados CSV (en español) — incluimos Estado
        fputcsv($output, ['Nombre','Correo','Rol','Nivel','DNI','Celular','Estado']);

        foreach ($rows as $r) {
            $line = [
                $r['nombre'] ?? '',
                $r['correo'] ?? '',
                $r['rol'] ?? '',
                $r['nivel'] ?? '',
                $r['dni'] ?? '',
                $r['celular'] ?? '',
                $r['estado'] ?? ''
            ];
            fputcsv($output, $line);
        }

        fclose($output);
        exit;
    }

}
