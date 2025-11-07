<?php
namespace App\Modelos;

use App\Core\ModeloBase;

class TrabajadoresModel extends ModeloBase {

    // Listar todos los trabajadores
    public function listar() {
        return $this->db->query("
            SELECT u.id_usuario, u.nombre, u.correo, r.nombre AS rol, n.nombre AS nivel,
                   u.dni, u.celular, u.estado
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            JOIN niveles n ON u.id_nivel = n.id_nivel
            WHERE u.id_rol = 2
            ORDER BY u.id_usuario DESC
        ")->fetch_all(MYSQLI_ASSOC);
    }

    // Buscar trabajador por ID
    public function buscarPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Validar duplicados
    public function existeDuplicado($campo, $valor, $id = null) {
        $query = "SELECT COUNT(*) as total FROM usuarios WHERE $campo = ?";
        if ($id) {
            $query .= " AND id_usuario != ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("si", $valor, $id);
        } else {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $valor);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res['total'] > 0;
    }

    // Crear nuevo trabajador
    public function crear($data) {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nombre, correo, contrasena, id_rol, id_nivel, dni, celular, estado)
            VALUES (?, ?, ?, 2, ?, ?, ?, 'activo')
        ");
        $hashed = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        $stmt->bind_param(
            "sssiss",
            $data['nombre'],
            $data['correo'],
            $hashed,
            $data['id_nivel'],
            $data['dni'],
            $data['celular']
        );
        return $stmt->execute();
    }

    // Actualizar datos del trabajador
    public function actualizar($data) {
        $stmt = $this->db->prepare("
            UPDATE usuarios
            SET nombre = ?, correo = ?, id_nivel = ?, dni = ?, celular = ?, estado = ?
            WHERE id_usuario = ?
        ");
        $stmt->bind_param(
            "ssiissi",
            $data['nombre'],
            $data['correo'],
            $data['id_nivel'],
            $data['dni'],
            $data['celular'],
            $data['estado'],
            $data['id_usuario']
        );
        return $stmt->execute();
    }

    // Eliminar definitivamente un trabajador
    public function desactivar($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Cambiar estado activo/inactivo
    public function cambiarEstado($id, $nuevoEstado) {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $nuevoEstado, $id);
        return $stmt->execute();
    }
}
