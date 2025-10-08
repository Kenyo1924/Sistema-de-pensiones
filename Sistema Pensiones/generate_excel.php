<?php
// Requiere PHPSpreadsheet. Instala con: composer require phpoffice/phpspreadsheet
require 'vendor/autoload.php';
include_once 'backend/config/database.php';
include_once 'backend/core/payment.php';
include_once 'backend/core/student.php';
include_once 'backend/core/semester.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$database = new Database();
$db = $database->getConnection();
$payment = new Payment($db);

// Filtros
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : null;
$payment_type = isset($_GET['payment_type']) ? $_GET['payment_type'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Obtener pagos filtrados
$pagos = $payment->search('', $semester_id, $payment_type, $start_date, $end_date);
if ($student_id) {
    $pagos = array_filter($pagos, function($p) use ($student_id) {
        return $p['student_id'] == $student_id;
    });
}

// Crear Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$headers = [
    'Alumno', 'DNI', 'Semestre', 'Tipo de Pago', 'Monto', 'Fecha de Pago'
];
$sheet->fromArray($headers, NULL, 'A1');

$row = 2;
foreach ($pagos as $pago) {
    $sheet->setCellValue('A'.$row, $pago['first_name'].' '.$pago['last_name']);
    $sheet->setCellValue('B'.$row, isset($pago['dni']) ? $pago['dni'] : '');
    $sheet->setCellValue('C'.$row, $pago['semester_name']);
    $sheet->setCellValue('D'.$row, $pago['payment_type']);
    $sheet->setCellValue('E'.$row, $pago['amount']);
    $sheet->setCellValue('F'.$row, $pago['payment_date']);
    $row++;
}

// Descargar Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="pagos.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
