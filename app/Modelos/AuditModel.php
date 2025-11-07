<?php
namespace App\Modelos;

use App\Core\BaseDatos;
use PDO;

class AuditModel {
    private $db;

    public function __construct() {
        $this->db = BaseDatos::getInstancia();
    }

    /**
     * Inserta un registro de auditoría.
     * $data: [
     *   'user_id' => int|null,
     *   'user_name' => string|null,
     *   'action' => 'crear'|'editar'|'eliminar'|...,
     *   'entity' => 'Usuarios',
     *   'entity_id' => '123',
     *   'field_name' => 'correo', (nullable)
     *   'old_value' => 'old', (nullable)
     *   'new_value' => 'new'  (nullable)
     * ]
     */
    public function crearRegistro(array $data): bool {
        $sql = "INSERT INTO audit_logs (user_id, user_name, action, entity, entity_id, field_name, old_value, new_value)
                VALUES (:user_id, :user_name, :action, :entity, :entity_id, :field_name, :old_value, :new_value)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id',   $data['user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':user_name', $data['user_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':action',    $data['action'], PDO::PARAM_STR);
        $stmt->bindValue(':entity',    $data['entity'], PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', $data['entity_id'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':field_name', $data['field_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':old_value',  $data['old_value'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':new_value',  $data['new_value'] ?? null, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Lista registros con filtros opcionales (GET style):
     * $filters = ['entity'=>'Usuarios','entity_id'=>'12','user_name'=>'Luis','date_from'=>'2025-01-01','date_to'=>'2025-01-10']
     */
    public function listar(array $filters = []): array {
        $where = [];
        $params = [];

        if (!empty($filters['entity'])) {
            $where[] = 'entity = :entity';
            $params[':entity'] = $filters['entity'];
        }
        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = :entity_id';
            $params[':entity_id'] = $filters['entity_id'];
        }
        if (!empty($filters['user_name'])) {
            $where[] = 'user_name LIKE :user_name';
            $params[':user_name'] = '%'.$filters['user_name'].'%';
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT * FROM audit_logs";
        if (count($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY created_at DESC LIMIT 1000";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Para exportar CSV: devuelve filas según filtros (sin límite)
     */
    public function listarParaExport(array $filters = []): array {
        // Reuse listar but remove limit
        $where = [];
        $params = [];

        if (!empty($filters['entity'])) {
            $where[] = 'entity = :entity';
            $params[':entity'] = $filters['entity'];
        }
        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = :entity_id';
            $params[':entity_id'] = $filters['entity_id'];
        }
        if (!empty($filters['user_name'])) {
            $where[] = 'user_name LIKE :user_name';
            $params[':user_name'] = '%'.$filters['user_name'].'%';
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT * FROM audit_logs";
        if (count($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
