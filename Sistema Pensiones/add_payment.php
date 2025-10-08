<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'editor') {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/payment.php';
include_once 'backend/core/student.php';
include_once 'backend/core/semester.php';

$database = new Database();
$db = $database->getConnection();

$student = new Student($db);
$students = [];
$search_term = isset($_GET['search']) ? $_GET['search'] : (isset($_POST['search']) ? $_POST['search'] : '');
if ($search_term !== '') {
    $students = $student->search($search_term);
}

$semester = new Semester($db);
$semesters = $semester->getAll();

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment']) && $_POST['submit_payment'] === '1') {
    $payment = new Payment($db);

    $payment->student_id = $_POST['student_id'];
    // Obtener datos del avance seleccionado
    if (!empty($_POST['progress_id'])) {
        $progress_stmt = $db->prepare("SELECT semester_id, program_type FROM student_semester_progress WHERE id = ?");
        $progress_stmt->execute([$_POST['progress_id']]);
        $progress_data = $progress_stmt->fetch(PDO::FETCH_ASSOC);
        $payment->semester_id = $progress_data ? $progress_data['semester_id'] : null;
        $payment->program_type = $progress_data ? $progress_data['program_type'] : null;
    } else {
        $payment->semester_id = null;
        $payment->program_type = null;
    }
    $payment->payment_type = $_POST['payment_type'];
    $payment->amount = $_POST['amount'];
    $payment->payment_date = $_POST['payment_date'];
    $payment->payment_code = $_POST['payment_code'];

    // File upload
    $target_dir = "backend/uploads/";
    $voucher_path = $target_dir . basename($_FILES["voucher"]["name"]);
    if (isset($_FILES["voucher"]) && is_uploaded_file($_FILES["voucher"]["tmp_name"]) && move_uploaded_file($_FILES["voucher"]["tmp_name"], $voucher_path)) {
        $payment->voucher_path = $voucher_path;
        if ($payment->existsByPaymentCode($payment->payment_code)) {
            $message = "El código de pago (voucher) ya existe. Por favor, ingrese un código diferente.";
        } else if ($payment->create()) {
            header("Location: payments.php");
            exit();
        } else {
            $message = "Error al agregar el pago.";
        }
    } else {
        $message = "Lo sentimos, hubo un error al subir tu archivo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Pago</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Agregar Pago</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php" class="active">Pagos</a>
                <a href="reports.php">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor'): ?>
                    <a href="add_semester.php">Agregar Semestre</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Agregar Nuevo Pago</h2>
            <?php if ($message): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            <form action="add_payment.php" method="get" class="search-form">
                <div class="form-group">
                    <label for="search">Buscar Estudiante</label>
                    <input type="text" name="search" id="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Buscar</button>
                </div>
            </form>

            <?php if (!empty($students)): ?>
            <?php
            // Determinar estudiante seleccionado: POST tiene prioridad, si no, único resultado de búsqueda
            $selected_student_id = null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['student_id'])) {
                $selected_student_id = $_POST['student_id'];
            } elseif (!empty($search_term) && count($students) === 1) {
                $selected_student_id = $students[0]['id'];
            }
            $progress_options = [];
            if ($selected_student_id) {
                // Traer historial de student_semester_progress con joins
                $stmt = $db->prepare("SELECT sp.*, sem.name as semester_name FROM student_semester_progress sp JOIN semesters sem ON sp.semester_id = sem.id WHERE sp.student_id = ? ORDER BY sp.program_type, sp.semester_number");
                $stmt->execute([$selected_student_id]);
                $progress_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            ?>
            <form action="add_payment.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
            <div class="form-group">
            <label for="student_id">Estudiante</label>
            <select name="student_id" id="student_id" required onchange="this.form.submit()">
            <option value="">Seleccione...</option>
            <?php foreach ($students as $s): ?>
            <option value="<?php echo $s['id']; ?>" <?php if($selected_student_id == $s['id']) echo 'selected'; ?>><?php echo $s['first_name'] . ' ' . $s['last_name'] . ' (DNI: ' . $s['dni'] . ')'; ?></option>
            <?php endforeach; ?>
            </select>
            </div>
            <?php if($selected_student_id && count($progress_options) > 0): ?>
            <div class="form-group">
            <label for="progress_id">Selecciona avance a pagar</label>
            <select name="progress_id" id="progress_id" required>
            <option value="">Selecciona...</option>
            <?php foreach ($progress_options as $option): ?>
            <option value="<?php echo $option['id']; ?>">
            <?php echo strtoupper($option['program_type']) . ", Sem. curricular: " . $option['semester_number'] . ", Academico: " . htmlspecialchars($option['semester_name']); ?>
            </option>
            <?php endforeach; ?>
            </select>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <form action="add_payment.php" method="post" enctype="multipart/form-data" style="display: none;">
            <?php endif; ?>
                <div class="form-group">
                <label for="payment_type">Tipo de Pago</label>
                <select name="payment_type" id="payment_type" required>
                <option value="matricula">Matrícula</option>
                <option value="pension_1">Pensión 1</option>
                <option value="pension_2">Pensión 2</option>
                <option value="pension_3">Pensión 3</option>
                <option value="pension_4">Pensión 4</option>
                </select>
                </div>
                <!-- Mapeo oculto para identificar programa, semestre académico y curricular -->
                <?php if($selected_student_id && count($progress_options) > 0): ?>
                <script>
                // Asignar al submit ocultamente los extras del registro progresado
                document.addEventListener('DOMContentLoaded', function() {
                var progressSelect = document.getElementById('progress_id');
                if(progressSelect) progressSelect.addEventListener('change', function() {
                var selected = this.selectedOptions[0];
                });
                });
                </script>
                <?php endif; ?>
                <div class="form-group">
                    <label for="amount">Monto</label>
                    <input type="number" step="0.01" name="amount" id="amount" required>
                </div>
                <div class="form-group">
                    <label for="payment_date">Fecha de Pago</label>
                    <input type="date" name="payment_date" id="payment_date" required>
                </div>
                <div class="form-group">
                    <label for="payment_code">Código de Pago</label>
                    <input type="text" name="payment_code" id="payment_code" required>
                </div>
                <div class="form-group">
                    <label for="voucher">Voucher</label>
                    <input type="file" name="voucher" id="voucher" required>
                </div>
                <button type="submit" name="submit_payment" value="1">Agregar Pago</button>
            </form>
        </main>
    </div>
</body>
</html>