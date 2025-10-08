<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';
include_once 'backend/core/student.php';
include_once 'backend/core/payment.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de estudiante no válido.";
    exit();
}

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);
$payment = new Payment($db);

$student_id = (int)$_GET['id'];
$student_data = $student->getById($student_id);
if (!$student_data) {
    echo "Estudiante no encontrado.";
    exit();
}
$student_programs = $student->getProgramsByStudentId($student_id);
$payments = $payment->getByStudentId($student_id);

// Historial académico
$stmt_progress = $db->prepare("SELECT prog.*, semesters.name as semester_name FROM student_semester_progress prog JOIN semesters ON prog.semester_id = semesters.id WHERE prog.student_id = ? ORDER BY prog.program_type, prog.semester_number");
$stmt_progress->execute([$student_id]);
$progress_rows = $stmt_progress->fetchAll(PDO::FETCH_ASSOC);

$date_gen = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Académico Integral</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background:#fff; color:#111; margin:0; }
        .print-report { max-width: 950px; margin: 64px auto 32px auto; background:#fff; border-radius: 8px; box-shadow:0 6px 32px #0002; padding:40px 50px 30px 50px; }
        h1, h2, h3 { margin-top: 18px; margin-bottom: 8px; color:#003e7c; }
        h2 { border-bottom:2px solid #003e7c1a; padding-bottom:6px; }
        table { width: 100%; border-collapse: collapse; margin: 18px 0 32px 0; font-size: 1rem; }
        th, td { border:1px solid #bbb; padding: 10px 8px; text-align: left; }
        th { background: #e6f0ff; font-weight: bold; }
        .headerbox {margin-bottom:20px;padding-bottom:16px;border-bottom:2px solid #003e7c2b;}
        .info-block {margin-bottom:14px; font-size: 1.07em;}
        .tr-prog {background:#f8f8fc;}
        .align-right {text-align:right;}
        @media print {
          body {background: #fff; color:#000;}
          .print-report { box-shadow:none; border:0; margin: 0;}
          .no-print { display:none !important;}
          .footer-print { position: fixed; bottom: 0; left: 0; right: 0; width:100vw; text-align:center; font-size:11px; color:#aaa;}
        }
        .footer-print {display:block; position:relative; margin-top:30px;text-align:right;font-size:12px;color:#888;}
    </style>
</head>
<body>
<div class="print-report">
    <div class="headerbox">
        <h1 style="margin-bottom:8px;">Reporte Académico Integral</h1>
        <div class="info-block"><b>Alumno:</b> <?php echo htmlspecialchars($student_data['first_name'].' '.$student_data['last_name']); ?></div>
        <div class="info-block"><b>DNI:</b> <?php echo htmlspecialchars($student_data['dni']); ?></div>
        <div class="info-block"><b>Programas:</b>
            <ul style="margin:0 0 0 20px;padding:0;">
            <?php
              if ($student_programs && count($student_programs)>0) {
                foreach ($student_programs as $prog) {
                  echo '<li>';
                  echo ($prog['program_type']==='maestria'?'Maestría':'Doctorado');
                  echo ' (Becado: '.($prog['is_scholarship_holder']?'Sí':'No').')';
                  echo '</li>';
                }
              } else {
                echo '<li>-</li>';
              }
            ?>
            </ul>
        </div>
    </div>
    <h2>Historial de Pagos</h2>
    <?php if (count($payments)): ?>
    <table>
        <thead>
            <tr>
                <th>Semestre</th>
                <th>Tipo de Pago</th>
                <th>Monto (S/)</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['semester_name']); ?></td>
                <td><?php echo htmlspecialchars($p['payment_type']); ?></td>
                <td class="align-right"><?php echo number_format($p['amount'],2); ?></td>
                <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No tiene pagos registrados.</p>
    <?php endif; ?>
    <h2>Historial Académico</h2>
    <?php if (!empty($progress_rows)): ?>
    <table>
        <thead>
            <tr>
                <th>Programa</th>
                <th>Semestre Curricular</th>
                <th>Semestre Académico</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($progress_rows as $pr): ?>
            <tr class="tr-prog">
                <td><?php echo ucfirst($pr['program_type']); ?></td>
                <td><?php echo $pr['semester_number']; ?></td>
                <td><?php echo htmlspecialchars($pr['semester_name']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No hay avance académico registrado.</p>
    <?php endif; ?>
    <div class="footer-print">Reporte generado el <?php echo $date_gen; ?>.</div>
</div>
</body>
</html>
