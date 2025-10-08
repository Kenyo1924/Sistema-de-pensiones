<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/student.php';
include_once 'backend/core/payment.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);
$payment = new Payment($db);

$student_data = $student->getById($_GET['id']);
$student_programs = $student->getProgramsByStudentId($_GET['id']);
$payments = $payment->getByStudentId($_GET['id']);
// Cargar historial de avance de semestre
$stmt_progress = $db->prepare("SELECT prog.*, semesters.name as semester_name FROM student_semester_progress prog JOIN semesters ON prog.semester_id = semesters.id WHERE prog.student_id = ? ORDER BY prog.semester_number");
$stmt_progress->execute([$_GET['id']]);
$progress_rows = $stmt_progress->fetchAll(PDO::FETCH_ASSOC);
// Para el formulario: cargar semestres académicos
$semesters = $db->query("SELECT * FROM semesters ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
// Mensaje de resultado
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_progress'])) {
    if (isset($_POST['program_type'], $_POST['semester_number'], $_POST['semester_id'])) {
        // Verificar existencia previa
        $exist_stmt = $db->prepare("SELECT id FROM student_semester_progress WHERE student_id = ? AND program_type = ? AND semester_id = ? LIMIT 1");
        $exist_stmt->execute([
            $_GET['id'],
            $_POST['program_type'],
            $_POST['semester_id']
        ]);
        if ($exist_stmt->fetch()) {
            $message = 'Ya existe un avance registrado para este estudiante, programa y semestre académico.';
        } else {
            $stmt = $db->prepare("INSERT INTO student_semester_progress (student_id, program_type, semester_number, semester_id) VALUES (?, ?, ?, ?)");
            $ok = $stmt->execute([
                $_GET['id'],
                $_POST['program_type'],
                $_POST['semester_number'],
                $_POST['semester_id']
            ]);
            if ($ok) {
                header("Location: view_student.php?id=".$_GET['id']);
                exit();
            } else {
                $message = 'Error al registrar el progreso.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Estudiante</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Ver Estudiante</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php" class="active">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php">Reportes</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <div style="text-align:right;margin-bottom:15px;">
                <a href="student_report.php?id=<?php echo $student_data['id']; ?>" class="button" target="_blank">Generar Reporte</a>
            </div>
            <h2>Detalles del Estudiante</h2>
            <p><strong>Nombres:</strong> <?php echo $student_data['first_name']; ?></p>
            <p><strong>Apellidos:</strong> <?php echo $student_data['last_name']; ?></p>
            <p><strong>DNI del de Estudiante:</strong> <?php echo $student_data['dni']; ?></p>
            <p><strong>Trayectoria(s):</strong>
                <?php
                if ($student_programs && count($student_programs) > 0) {
                    foreach ($student_programs as $prog) {
                        echo ($prog['program_type'] === 'maestria' ? 'Maestría' : 'Doctorado');
                        echo ' (Becado: ' . ($prog['is_scholarship_holder'] ? 'Sí' : 'No') . ')<br>';
                    }
                } else {
                    echo '-';
                }
                ?>
            </p>

            <div style="margin: 10px 0 20px 0;">
                <?php if ($_SESSION['role'] == 'editor' || $_SESSION['role'] == 'superadmin'): ?>
                    <a class="button" href="add_semester_progress.php?student_id=<?php echo $student_data['id']; ?>">Registrar nuevo avance</a>
                <?php endif; ?>
            </div>

            <h3>Historial de Pagos</h3>
            <?php
            // Clasificar los pagos según program_type del propio pago
            $maestria_payments = [];
            $doctorado_payments = [];
            foreach ($payments as $p) {
                if (isset($p['program_type']) && $p['program_type'] === 'maestria') {
                    $maestria_payments[] = $p;
                } elseif (isset($p['program_type']) && $p['program_type'] === 'doctorado') {
                    $doctorado_payments[] = $p;
                }
            }
            // (Quitado comportamiento por retrocompatibilidad: solo mostrar si realmente hay pagos de maestría)
            ?>
            <?php
            $has_maestria = false;
            $has_doctorado = false;
            foreach ($student_programs as $prog_check) {
                if ($prog_check['program_type'] === 'maestria') $has_maestria = true;
                if ($prog_check['program_type'] === 'doctorado') $has_doctorado = true;
            }
            ?>
            <?php if ($has_maestria): ?>
            <h4>Pagos Maestría</h4>
            <table>
                <thead>
                    <tr>
                        <th>Semestre Académico</th>
                        <th>Sem. Curricular</th>
                        <th>Tipo de Pago</th>
                        <th>Monto</th>
                        <th>Fecha de Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($maestria_payments) == 0): ?>
                        <tr><td colspan="6" style="text-align:center; color:#bbb;">No hay pagos de maestría registrados.</td></tr>
                    <?php else: ?>
                    <?php foreach ($maestria_payments as $row): ?>
                        <tr>
                            <td><?php echo isset($row['semester_name']) ? $row['semester_name'] : '-'; ?></td>
                            <td>
                            <?php
                            // Buscar semestre curricular con student_id, program_type, semester_id
                            $curr_sem = '-';
                            foreach ($progress_rows as $prog) {
                                if ($prog['program_type'] === 'maestria' && $prog['semester_id'] == $row['semester_id']) {
                                    $curr_sem = $prog['semester_number'];
                                    break;
                                }
                            }
                            echo $curr_sem;
                            ?>
                            </td>
                            <td><?php echo $row['payment_type']; ?></td>
                            <td>S/ <?php echo $row['amount']; ?></td>
                            <td><?php echo $row['payment_date']; ?></td>
                            <td>
                                <a href="delete_payment.php?id=<?php echo $row['id']; ?>&from_student=1" onclick="return confirm('¿Está seguro de eliminar este pago? Esta acción no se puede deshacer.');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php if ($has_doctorado): ?>
            <h4>Pagos Doctorado</h4>
            <table>
                <thead>
                    <tr>
                        <th>Semestre Académico</th>
                        <th>Sem. Curricular</th>
                        <th>Tipo de Pago</th>
                        <th>Monto</th>
                        <th>Fecha de Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($doctorado_payments) == 0): ?>
                        <tr><td colspan="6" style="text-align:center; color:#bbb;">No hay pagos de doctorado registrados.</td></tr>
                    <?php else: ?>
                    <?php foreach ($doctorado_payments as $row): ?>
                        <tr>
                            <td><?php echo isset($row['semester_name']) ? $row['semester_name'] : '-'; ?></td>
                            <td>
                            <?php
                            $curr_sem = '-';
                            foreach ($progress_rows as $prog) {
                                if ($prog['program_type'] === 'doctorado' && $prog['semester_id'] == $row['semester_id']) {
                                    $curr_sem = $prog['semester_number'];
                                    break;
                                }
                            }
                            echo $curr_sem;
                            ?>
                            </td>
                            <td><?php echo $row['payment_type']; ?></td>
                            <td>S/ <?php echo $row['amount']; ?></td>
                            <td><?php echo $row['payment_date']; ?></td>
                            <td>
                                <a href="delete_payment.php?id=<?php echo $row['id']; ?>&from_student=1" onclick="return confirm('¿Está seguro de eliminar este pago? Esta acción no se puede deshacer.');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>
        <!-- Progreso curricular y formulario -->
            <h3>Historial académico anclado a semestre</h3>
            <?php if (!empty($progress_rows)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Programa</th>
                        <th>Semestre Curricular</th>
                        <th>Semestre Académico</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress_rows as $pr): ?>
                    <tr>
                        <td><?php echo ucfirst($pr['program_type']); ?></td>
                        <td><?php echo $pr['semester_number']; ?></td>
                        <td><?php echo htmlspecialchars($pr['semester_name']); ?></td>
                        <td>
                            <a href="edit_student_progress.php?id=<?php echo $pr['id']; ?>">Editar</a>
                            <a href="delete_student_progress.php?id=<?php echo $pr['id']; ?>" onclick="return confirm('¿Está seguro de eliminar este avance académico? Esta acción no se puede deshacer.');">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No hay progreso curricular registrado.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
