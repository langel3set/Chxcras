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
            $clave_raw = $_POST['contraseña'] ?? '';
            $id_rol = (int)($_POST['id_rol'] ?? 2);
            $id_nivel = (int)($_POST['id_nivel'] ?? 2);
            $dni = trim($_POST['dni'] ?? '');
            $celular = trim($_POST['celular'] ?? '');

            // Validaciones servidor
            if ($nombre === '' || $correo === '' || $clave_raw === '' || $dni === '' || $celular === '' || $id_nivel <= 0) {
                $mensaje_error = "⚠️ Todos los campos son obligatorios.";
            } elseif (!preg_match('/^\d{8}$/', $dni)) {
                $mensaje_error = "⚠️ El DNI debe tener exactamente 8 números.";
            } elseif (!preg_match('/^\d{9}$/', $celular)) {
                $mensaje_error = "⚠️ El celular debe tener exactamente 9 números.";
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $mensaje_error = "⚠️ El formato del correo no es válido.";
            } elseif (strlen($clave_raw) < 5 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{5,}$/', $clave_raw)) {
                $mensaje_error = "⚠️ La contraseña debe tener al menos 5 caracteres e incluir letras y números.";
            } else {
                try {
                    // Verificar duplicidad
                    $stmtCheck = $this->conexion->prepare("
                        SELECT COUNT(*) as cnt 
                        FROM Usuarios 
                        WHERE correo = ? OR dni = ? OR celular = ?
                    ");
                    $stmtCheck->execute([$correo, $dni, $celular]);
                    $row = $stmtCheck->fetch();
                    $existe = (int)($row['cnt'] ?? $row[0] ?? 0);

                    if ($existe > 0) {
                        $mensaje_error = "⚠️ Ya existe un usuario con el mismo correo, DNI o número de celular.";
                    } else {
                        // Iniciamos transacción para insertar + auditoría
                        $this->conexion->beginTransaction();

                        $clave = password_hash($clave_raw, PASSWORD_BCRYPT);
                        $stmt = $this->conexion->prepare("
                            INSERT INTO Usuarios (nombre, correo, contrasena, id_rol, id_nivel, dni, celular, estado) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')
                        ");
                        $stmt->execute([$nombre, $correo, $clave, $id_rol, $id_nivel, $dni, $celular]);

                        // Obtener ID insertado (PDO)
                        $nuevoId = $this->conexion->lastInsertId();

                        // Registrar auditoría (no guardar contraseñas en texto plano)
                        $this->audit->crearRegistro([
                            'user_id' => $_SESSION['usuario']['id_usuario'] ?? null,
                            'user_name' => $_SESSION['usuario']['nombre'] ?? ($_SESSION['usuario']['user_name'] ?? 'Sistema'),
                            'action' => 'crear',
                            'entity' => 'Usuarios',
                            'entity_id' => (string)$nuevoId,
                            'field_name' => null,
                            'old_value' => null,
                            'new_value' => json_encode([
                                'nombre' => $nombre,
                                'correo' => $correo,
                                'dni' => $dni,
                                'celular' => $celular,
                                'id_nivel' => $id_nivel
                            ])
                        ]);

                        $this->conexion->commit();

                        $_SESSION['mensaje_exito'] = "✅ Empleado registrado con éxito.";
                        header("Location: " . BASE_URL . "rrhh");
                        exit;
                    }
                } catch (PDOException $e) {
                    if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                    $mensaje_error = "Error al registrar empleado: " . $e->getMessage();
                } catch (Exception $e) {
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

        // Inicia sesión si es necesario (para mensajes)
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
            $dni = trim($_POST['dni'] ?? '');
            $celular = trim($_POST['celular'] ?? '');
            $estado = trim($_POST['estado'] ?? ($trabajador['estado'] ?? 'activo'));

            // Normalizar y validar estado
            $estado = ($estado === 'inactivo') ? 'inactivo' : 'activo';

            // Validaciones servidor
            if ($nombre === '' || $correo === '' || $dni === '' || $celular === '' || $id_nivel <= 0) {
                $mensaje_error = "⚠️ Todos los campos son obligatorios.";
            } elseif (!preg_match('/^\d{8}$/', $dni)) {
                $mensaje_error = "⚠️ El DNI debe tener exactamente 8 números.";
            } elseif (!preg_match('/^\d{9}$/', $celular)) {
                $mensaje_error = "⚠️ El celular debe tener exactamente 9 números.";
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $mensaje_error = "⚠️ El formato del correo no es válido.";
            } else {
                try {
                    // Verificar duplicidad excluyendo el propio id
                    $stmtCheck = $this->conexion->prepare("
                        SELECT COUNT(*) as cnt
                        FROM Usuarios
                        WHERE (correo = ? OR dni = ? OR celular = ?) AND id_usuario != ?
                    ");
                    $stmtCheck->execute([$correo, $dni, $celular, $id]);
                    $row = $stmtCheck->fetch();
                    $existe = (int)($row['cnt'] ?? $row[0] ?? 0);

                    if ($existe > 0) {
                        $mensaje_error = "⚠️ Ya existe otro usuario con el mismo correo, DNI o número de celular.";
                    } else {
                        // Iniciamos transacción
                        $this->conexion->beginTransaction();

                        // Ejecutar update (incluye estado)
                        $stmt = $this->conexion->prepare("
                            UPDATE Usuarios
                            SET nombre = ?, correo = ?, id_nivel = ?, dni = ?, celular = ?, estado = ?
                            WHERE id_usuario = ?
                        ");
                        $stmt->execute([$nombre, $correo, $id_nivel, $dni, $celular, $estado, $id]);

                        // Registrar auditoría: comparar campo a campo
                        $userId = $_SESSION['usuario']['id_usuario'] ?? null;
                        $userName = $_SESSION['usuario']['nombre'] ?? ($_SESSION['usuario']['user_name'] ?? null);

                        $campos = ['nombre','correo','dni','celular','id_nivel','estado'];
                        foreach ($campos as $campo) {
                            $oldVal = $trabajador[$campo] ?? null;
                            // mapear id_nivel a valor actual, ya que en row name es id_nivel
                            $newVal = $$campo ?? null; // variable variable para nombre,dni,celular
                            // Para id_nivel y estado tratamos explícitamente
                            if ($campo === 'id_nivel') {
                                $oldVal = $trabajador['id_nivel'] ?? null;
                                $newVal = $id_nivel;
                            } elseif ($campo === 'estado') {
                                $oldVal = $trabajador['estado'] ?? null;
                                $newVal = $estado;
                            } else {
                                // nombre, correo, dni, celular
                                $newVal = ${$campo} ?? null;
                            }

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

                        // Si el usuario se desactiva a sí mismo, forzar cierre (opcional)
                        if ((int)$id === (int)($_SESSION['usuario']['id_usuario'] ?? 0) && $estado === 'inactivo') {
                            // cerrar sesión
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
                } catch (PDOException $e) {
                    if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                    $mensaje_error = "Error al actualizar empleado: " . $e->getMessage();
                } catch (Exception $e) {
                    if ($this->conexion->inTransaction()) $this->conexion->rollBack();
                    $mensaje_error = "Error inesperado: " . $e->getMessage();
                }
            }
        }

        // Incluir la vista (le pasamos $trabajador, $niveles y $mensaje_error)
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
