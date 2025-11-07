<?php
namespace App\Controladores;

use App\Core\ControladorBase;
use App\Core\Sesion;
use App\Modelos\AuditModel;

class AuditControlador extends ControladorBase {
    private $audit;

    public function __construct() {
        Sesion::iniciar();
        $this->audit = new AuditModel();
    }

    // Solo admin (id_rol = 1)
    public function index() {
        if (!$this->verificarAcceso([1])) return;

        // Leer filtros desde GET
        $filters = [
            'entity' => $_GET['entity'] ?? null,
            'entity_id' => $_GET['entity_id'] ?? null,
            'user_name' => $_GET['user_name'] ?? null,
            'action' => $_GET['action'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];

        $logs = $this->audit->listar($filters);

        include __DIR__ . '/../Vistas/audit/index.php';
    }

    // Export CSV (admin)
    public function exportar() {
        if (!$this->verificarAcceso([1])) return;

        $filters = [
            'entity' => $_GET['entity'] ?? null,
            'entity_id' => $_GET['entity_id'] ?? null,
            'user_name' => $_GET['user_name'] ?? null,
            'action' => $_GET['action'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];

        $rows = $this->audit->listarParaExport($filters);

        $fecha = date('Y-m-d');
        $filename = "AuditLogs_{$fecha}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Usuario','Acción','Entidad','Entity ID','Campo','Antiguo','Nuevo','Fecha','Hora']);

        foreach ($rows as $r) {
            $dt = new \DateTime($r['created_at']);
            fputcsv($output, [
                $r['user_name'] ?? '',
                $r['action'] ?? '',
                $r['entity'] ?? '',
                $r['entity_id'] ?? '',
                $r['field_name'] ?? '',
                $r['old_value'] ?? '',
                $r['new_value'] ?? '',
                $dt->format('Y-m-d'),
                $dt->format('H:i:s')
            ]);
        }
        fclose($output);
        exit;
    }
}
