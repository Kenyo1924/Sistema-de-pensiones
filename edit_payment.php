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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de pago no válido.";
    exit();
}
$payment_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$payment = new Payment($db);

// Obtener pago actual
$stmt = $db->prepare('SELECT * FROM payments WHERE id=?');
$stmt->execute([$payment_id]);
$current_payment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$current_payment) {
    echo "Pago no encontrado.";
    exit();
}

// Para selects:
$student = new Student($db);
$students = $student->getAll();
$semester = new Semester($db);
$semesters = $semester->getAll();

$message = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $student_id = $_POST['student_id'];
    $semester_id = $_POST['semester_id'];
    $payment_type = $_POST['payment_type'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $payment_code = $_POST['payment_code'];
    // Validar SOLO cuando el usuario está cambiando alumno/semestre/tipo
    if ($student_id != $current_payment['student_id'] || $semester_id != $current_payment['semester_id'] || $payment_type != $current_payment['payment_type']) {
        $stmt_check = $db->prepare("SELECT id FROM payments WHERE student_id=? AND semester_id=? AND payment_type=? AND id<>? LIMIT 1");
        $stmt_check->execute([$student_id, $semester_id, $payment_type, $payment_id]);
        if ($stmt_check->rowCount() > 0) {
            $message = 'Ya existe un pago de ese tipo para este estudiante y semestre.';
        } else {
            $stmt_update = $db->prepare('UPDATE payments SET student_id=?, semester_id=?, payment_type=?, amount=?, payment_date=?, payment_code=? WHERE id=?');
            if ($stmt_update->execute([$student_id, $semester_id, $payment_type, $amount, $payment_date, $payment_code, $payment_id])) {
                header('Location: payments.php');
                exit();
            } else {
                $message = 'Error al actualizar el pago.';
            }
        }
    } else {
        $stmt_update = $db->prepare('UPDATE payments SET student_id=?, semester_id=?, payment_type=?, amount=?, payment_date=?, payment_code=? WHERE id=?');
        if ($stmt_update->execute([$student_id, $semester_id, $payment_type, $amount, $payment_date, $payment_code, $payment_id])) {
            header('Location: payments.php');
            exit();
        } else {
            $message = 'Error al actualizar el pago.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Pago</title>
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
    <h1>Editar Pago</h1>
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
    <h2>Editar Pago</h2>
    <?php if ($message): ?><p class="error"><?php echo $message; ?></p><?php endif; ?>
    <form action="edit_payment.php?id=<?php echo $payment_id; ?>" method="post">
        <div class="form-group">
            <label for="student_id">Estudiante</label>
            <select name="student_id" id="student_id" required>
                <option value="">Seleccione...</option>
                <?php foreach ($students as $s): ?>
                <option value="<?php echo $s['id']; ?>" <?php if ($current_payment['student_id']==$s['id']) echo 'selected'; ?>><?php echo $s['first_name']." ".$s['last_name'].' (DNI: '.$s['dni'].')'; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="semester_id">Semestre</label>
            <select name="semester_id" id="semester_id" required>
                <option value="">Seleccione...</option>
                <?php foreach ($semesters as $sem): ?>
                <option value="<?php echo $sem['id']; ?>" <?php if ($current_payment['semester_id']==$sem['id']) echo 'selected'; ?>><?php echo $sem['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="payment_type">Tipo de Pago</label>
            <select name="payment_type" id="payment_type" required>
                <option value="matricula" <?php if ($current_payment['payment_type']=='matricula') echo 'selected'; ?>>Matrícula</option>
                <option value="pension_1" <?php if ($current_payment['payment_type']=='pension_1') echo 'selected'; ?>>Pensión 1</option>
                <option value="pension_2" <?php if ($current_payment['payment_type']=='pension_2') echo 'selected'; ?>>Pensión 2</option>
                <option value="pension_3" <?php if ($current_payment['payment_type']=='pension_3') echo 'selected'; ?>>Pensión 3</option>
                <option value="pension_4" <?php if ($current_payment['payment_type']=='pension_4') echo 'selected'; ?>>Pensión 4</option>
            </select>
        </div>
        <div class="form-group">
            <label for="amount">Monto</label>
            <input type="number" step="0.01" name="amount" id="amount" value="<?php echo htmlspecialchars($current_payment['amount']); ?>" required>
        </div>
        <div class="form-group">
            <label for="payment_date">Fecha de Pago</label>
            <input type="date" name="payment_date" id="payment_date" value="<?php echo htmlspecialchars($current_payment['payment_date']); ?>" required>
        </div>
        <div class="form-group">
            <label for="payment_code">Código de Pago</label>
            <input type="text" name="payment_code" id="payment_code" value="<?php echo htmlspecialchars($current_payment['payment_code']); ?>" required>
        </div>
        <button type="submit">Actualizar Pago</button>
    </form>
</main></div></body></html>