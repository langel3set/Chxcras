<?php
namespace App\Controladores;

use App\Core\BaseDatos;
use App\Core\ControladorBase;
use App\Core\Sesion;
use App\Modelos\FinanzasModel;

class FinanzasControlador extends ControladorBase {
    private $modelo;

    public function __construct() {
        Sesion::iniciar();
        $this->modelo = new FinanzasModel();
    }

    public function index() {
        if (!$this->verificarAcceso([1,6])) return;
        $this->movimientos();
    }

    public function movimientos() {
        if (!$this->verificarAcceso([1,6])) return;
        $ingresos_desde = $_GET['ingresos_desde'] ?? null;
        $ingresos_hasta = $_GET['ingresos_hasta'] ?? null;
        $egresos_desde = $_GET['egresos_desde'] ?? null;
        $egresos_hasta = $_GET['egresos_hasta'] ?? null;

        $ingresos = $this->modelo->filtrarIngresos($ingresos_desde, $ingresos_hasta);
        $egresos = $this->modelo->filtrarEgresos($egresos_desde, $egresos_hasta);

        $total_ingresos = array_sum(array_column($ingresos, 'monto'));
        $total_egresos = array_sum(array_column($egresos, 'monto'));
        $balance = $total_ingresos - $total_egresos;

        $this->vista('finanzas/movimientos', compact(
            'ingresos', 'egresos',
            'total_ingresos', 'total_egresos', 'balance',
            'ingresos_desde', 'ingresos_hasta', 'egresos_desde', 'egresos_hasta'
        ));
    }

    public function exportar_excel() {
        $ingresos = $this->modelo->filtrarIngresos($_GET['ingresos_desde'] ?? null, $_GET['ingresos_hasta'] ?? null);
        $egresos = $this->modelo->filtrarEgresos($_GET['egresos_desde'] ?? null, $_GET['egresos_hasta'] ?? null);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=finanzas.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['INGRESOS']);
        fputcsv($output, ['Fuente', 'Monto', 'Descripción', 'Fecha']);
        foreach ($ingresos as $i) {
            fputcsv($output, [$i['fuente'], $i['monto'], $i['descripcion'], $i['fecha']]);
        }

        fputcsv($output, []);
        fputcsv($output, ['EGRESOS']);
        fputcsv($output, ['Motivo', 'Monto', 'Fecha']);
        foreach ($egresos as $e) {
            fputcsv($output, [$e['motivo'], $e['monto'], $e['fecha']]);
        }

        fclose($output);
        exit;
    }

    public function nuevoIngresoManual() {
    $this->vista('finanzas/crear_ingreso');
}

public function guardarIngresoManual() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $this->modelo->crearIngresoManual($_POST);
        header('Location: ' . BASE_URL . 'finanzas/movimientos');
    }
}

public function nuevoEgresoManual() {
    $this->vista('finanzas/crear_egreso');
}

public function guardarEgresoManual() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $this->modelo->crearEgresoManual($_POST);
        header('Location: ' . BASE_URL . 'finanzas/movimientos');
    }
}

public function registrarIngresoReserva() {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['id_reserva']) || empty($data['monto'])) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        return;
    }

    try {
        $id_reserva = intval($data['id_reserva']);
        $fuente = 'Reserva de mesa';
        $monto = floatval($data['monto']);
        $descripcion = $data['descripcion'] ?? 'Ingreso generado automáticamente por reserva';
        $fecha = date('Y-m-d');

        $db = new BaseDatos();
        $sql = "INSERT INTO ingresos (id_reserva, fuente, monto, descripcion, fecha)
                VALUES (?, ?, ?, ?, ?)";
        $db->query($sql, [$id_reserva, $fuente, $monto, $descripcion, $fecha]);

        echo json_encode(['status' => 'ok']);
    } catch (\Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

}
