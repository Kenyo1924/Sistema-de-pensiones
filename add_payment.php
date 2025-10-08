<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'editor' && $_SESSION['role'] != 'superadmin')) {
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
$selected_student_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['student_id'])) {
    $selected_student_id = $_POST['student_id'];
} elseif (isset($_GET['student_id'])) {
    $selected_student_id = $_GET['student_id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment']) && $_POST['submit_payment'] === '1') {
    $payment = new Payment($db);
    $payment->student_id = $_POST['student_id'];
    $progress_program_type = null;
    // Obtener datos del avance seleccionado
    if (!empty($_POST['progress_id'])) {
        $progress_stmt = $db->prepare("SELECT semester_id, program_type FROM student_semester_progress WHERE id = ?");
        $progress_stmt->execute([$_POST['progress_id']]);
        $progress_data = $progress_stmt->fetch(PDO::FETCH_ASSOC);
        $payment->semester_id = $progress_data ? $progress_data['semester_id'] : null;
        $payment->program_type = $progress_data ? $progress_data['program_type'] : null;
        $progress_program_type = $payment->program_type;
    } else {
        $payment->semester_id = null;
        $payment->program_type = null;
    }
    $payment->payment_type = $_POST['payment_type'];
    $payment->amount = $_POST['amount'];
    $payment->payment_date = $_POST['payment_date'];
    $payment->payment_code = $_POST['payment_code'];

    if ($payment->existsByPaymentCode($payment->payment_code)) {
        $message = "El código de pago ya existe. Por favor, ingrese un código diferente.";
    } else {
        // Insertar pago con program_type de forma explícita
        $query = "INSERT INTO payments (student_id, semester_id, program_type, payment_type, amount, payment_date, payment_code) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $ok = $stmt->execute([
            $payment->student_id,
            $payment->semester_id,
            $payment->program_type,
            $payment->payment_type,
            $payment->amount,
            $payment->payment_date,
            $payment->payment_code
        ]);
        if ($ok) {
            header("Location: payments.php");
            exit();
        } else {
            $message = "Error al agregar el pago.";
        }
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
            <div class="logos-container">
                <div class="logo">
                    <img src="logo_posgrado.jpg" alt="Logo Posgrado">
                    <span class="logo-text">Unidad de Posgrado</span>
                </div>
                <div class="logo">
                    <img src="logo_educacion.jpg" alt="Logo Facultad de Educación">
                    <span class="logo-text">Facultad de Educación</span>
                </div>
            </div>
            <h1>Agregar Pago</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php" class="active">Pagos</a>
                <a href="reports.php">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor' || $_SESSION['role'] == 'superadmin'): ?>
                    <a href="add_semester.php">Administrar Semestres</a>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                    <a href="users.php">Gestión de Usuarios</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Agregar Nuevo Pago</h2>
            <?php if ($message): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            <!-- Paso 1: Seleccionar estudiante -->
            <form action="add_payment.php" method="get" class="search-form">
                <div class="form-group">
                    <label for="search">Buscar Estudiante</label>
                    <input type="text" name="search" id="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Buscar</button>
                </div>
            </form>

            <?php if (!empty($students)) : ?>
                <!-- Paso 2: Elegir estudiante -->
                <form action="add_payment.php" method="get" class="form" style="margin-bottom: 20px;">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <div class="form-group">
                        <label for="student_id">Selecciona Estudiante</label>
                        <select name="student_id" id="student_id" required onchange="this.form.submit()">
                            <option value="">Seleccione...</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php if($selected_student_id == $s['id']) echo 'selected'; ?>><?php echo $s['first_name'] . ' ' . $s['last_name'] . ' (DNI: ' . $s['dni'] . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php endif; ?>

            <?php
            // Mostrar el formulario de pago solo si el estudiante está seleccionado
            $progress_options = [];
            if ($selected_student_id) {
                $stmt = $db->prepare("SELECT sp.*, sem.name as semester_name FROM student_semester_progress sp JOIN semesters sem ON sp.semester_id = sem.id WHERE sp.student_id = ? ORDER BY sp.program_type, sp.semester_number");
                $stmt->execute([$selected_student_id]);
                $progress_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            ?>
            <?php if ($selected_student_id) : ?>
                <?php if(count($progress_options) === 0): ?>
                    <div class="error" style="margin:30px 0;">Este estudiante no está registrado en ningún semestre ni programa.<br>Por favor registre el avance académico del alumno antes de agregar un pago.</div>
                <?php else: ?>
                    <form action="add_payment.php" method="post">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
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
                        <button type="submit" name="submit_payment" value="1">Agregar Pago</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>