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

            <h3>Historial de Pagos</h3>
            <?php
            // Clasificar los pagos según programación del estudiante
            $maestria_payments = [];
            $doctorado_payments = [];
            foreach ($payments as $p) {
                // Si existe relación en student_programs entonces $student_programs tiene los tipos permitidos
                // Se asume que pagan separado si tienen ambos, por control administrativo
                // Reglas flexibles para asociar el pago (opcional: se recomienda tener campo program_type en payments en el futuro)
                foreach ($student_programs as $prog) {
                    if (
                        ($prog['program_type'] === 'maestria' && $p['payment_type'])
                        && (
                            strpos(strtolower($p['payment_type']), 'maestr') !== false
                        )
                    ) {
                        $maestria_payments[] = $p;
                        break;
                    } elseif (
                        ($prog['program_type'] === 'doctorado' && $p['payment_type'])
                        && (
                            strpos(strtolower($p['payment_type']), 'doctor') !== false
                        )
                    ) {
                        $doctorado_payments[] = $p;
                        break;
                    }
                }
            }
            // Si no se logra clasificar por nombre se agregan todos abajo
            if (empty($maestria_payments)) $maestria_payments = array_filter($payments, function($pay) {
                return true;
            });
            ?>
            <?php if (count($maestria_payments) > 0): ?>
            <h4>Pagos Maestría</h4>
            <table>
                <thead>
                    <tr>
                        <th>Semestre</th>
                        <th>Tipo de Pago</th>
                        <th>Monto</th>
                        <th>Fecha de Pago</th>
                        <th>Voucher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maestria_payments as $row): ?>
                        <tr>
                            <td><?php echo $row['semester_name']; ?></td>
                            <td><?php echo $row['payment_type']; ?></td>
                            <td>S/ <?php echo $row['amount']; ?></td>
                            <td><?php echo $row['payment_date']; ?></td>
                            <td><a href="<?php echo $row['voucher_path']; ?>" target="_blank">Ver Voucher</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php if (count($doctorado_payments) > 0): ?>
            <h4>Pagos Doctorado</h4>
            <table>
                <thead>
                    <tr>
                        <th>Semestre</th>
                        <th>Tipo de Pago</th>
                        <th>Monto</th>
                        <th>Fecha de Pago</th>
                        <th>Voucher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctorado_payments as $row): ?>
                        <tr>
                            <td><?php echo $row['semester_name']; ?></td>
                            <td><?php echo $row['payment_type']; ?></td>
                            <td>S/ <?php echo $row['amount']; ?></td>
                            <td><?php echo $row['payment_date']; ?></td>
                            <td><a href="<?php echo $row['voucher_path']; ?>" target="_blank">Ver Voucher</a></td>
                        </tr>
                    <?php endforeach; ?>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress_rows as $pr): ?>
                    <tr>
                        <td><?php echo ucfirst($pr['program_type']); ?></td>
                        <td><?php echo $pr['semester_number']; ?></td>
                        <td><?php echo htmlspecialchars($pr['semester_name']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No hay progreso curricular registrado.</p>
            <?php endif; ?>
            <h4>Registrar nuevo avance</h4>
            <?php if ($message): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="add_progress" value="1">
                <div class="form-group">
                    <label for="program_type">Programa</label>
                    <select name="program_type" id="program_type" required onchange="updateSemesterOptions()">
                        <option value="">Selecciona...</option>
                        <?php foreach ($student_programs as $prog): ?>
                            <option value="<?php echo $prog['program_type']; ?>"><?php echo ucfirst($prog['program_type']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester_number">Nº de Semestre Curricular</label>
                    <select name="semester_number" id="semester_number" required>
                        <option value="">Selecciona...</option>
                        <!-- Las opciones serán generadas por JS -->
                    </select>
                </div>
                <script>
                function updateSemesterOptions() {
                    var prog = document.getElementById('program_type').value;
                    var sem = document.getElementById('semester_number');
                    sem.innerHTML = '<option value="">Selecciona...</option>';
                    var max = prog === 'maestria' ? 3 : (prog === 'doctorado' ? 6 : 0);
                    for (var i = 1; i <= max; i++) {
                        sem.innerHTML += '<option value="'+i+'">'+i+'</option>';
                    }
                }
                // Cargar opciones correctas si el valor se mantiene tras recarga
                window.addEventListener('DOMContentLoaded', function() {
                    updateSemesterOptions();
                });
                </script>
                <div class="form-group">
                    <label for="semester_id">Semestre Académico</label>
                    <select name="semester_id" id="semester_id" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Registrar Avance</button>
            </form>
        </main>
    </div>
</body>
</html>