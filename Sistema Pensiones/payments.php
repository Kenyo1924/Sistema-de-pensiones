<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';
include_once 'backend/core/payment.php';
include_once 'backend/core/semester.php';

$database = new Database();
$db = $database->getConnection();
$payment = new Payment($db);
$semester = new Semester($db);

$search = isset($_GET['search']) ? $_GET['search'] : '';
$semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : '';
$payment_type = isset($_GET['payment_type']) ? $_GET['payment_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$payments = $payment->search($search, $semester_id, $payment_type, $start_date, $end_date);
$semesters = $semester->getAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Gestión de Pagos</h1>
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
            <h2><span class="emoji">💸</span> Pagos</h2>
            <?php if ($_SESSION['role'] == 'editor'): ?>
                <a href="add_payment.php" class="button">Agregar Pago</a>
            <?php endif; ?>
            <form method="GET" action="payments.php" class="search-form">
                <input type="text" name="search" placeholder="Buscar por estudiante..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="semester_id">
                    <option value="">Todos los Semestres</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo $sem['id']; ?>" <?php echo $semester_id == $sem['id'] ? 'selected' : ''; ?>><?php echo $sem['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="payment_type">
                    <option value="">Todos los Tipos</option>
                    <option value="matricula" <?php echo $payment_type == 'matricula' ? 'selected' : ''; ?>>Matrícula</option>
                    <option value="pension_1" <?php echo $payment_type == 'pension_1' ? 'selected' : ''; ?>>Pensión 1</option>
                    <option value="pension_2" <?php echo $payment_type == 'pension_2' ? 'selected' : ''; ?>>Pensión 2</option>
                    <option value="pension_3" <?php echo $payment_type == 'pension_3' ? 'selected' : ''; ?>>Pensión 3</option>
                    <option value="pension_4" <?php echo $payment_type == 'pension_4' ? 'selected' : ''; ?>>Pensión 4</option>
                </select>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <button type="submit">Filtrar</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>DNI</th>
                        <th>Semestre</th>
                        <th>Tipo de Pago</th>
                        <th>Monto</th>
                        <th>Fecha de Pago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $row): ?>
                        <tr>
                            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                            <td><?php echo isset($row['dni']) ? $row['dni'] : ''; ?></td>
                            <td><?php echo $row['semester_name']; ?></td>
                            <td><?php echo $row['payment_type']; ?></td>
                            <td>S/ <?php echo $row['amount']; ?></td>
                            <td><?php echo $row['payment_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>