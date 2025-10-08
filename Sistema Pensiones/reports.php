<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';
include_once 'backend/core/report.php';

include_once 'backend/core/semester.php';

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);
$semester = new Semester($db);

$semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : '';
$payment_type = isset($_GET['payment_type']) ? $_GET['payment_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$program_type = isset($_GET['program_type']) ? $_GET['program_type'] : '';
$total_recaudado = $report->getTotalRecaudado($semester_id, $payment_type, $start_date, $end_date, $program_type);
$late_students = $report->getLateStudents();
$semesters = $semester->getAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Reportes</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php" class="active">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor'): ?>
                    <a href="add_semester.php">Agregar Semestre</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Reportes Financieros</h2>
            <form method="GET" action="reports.php" class="filter-form">
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
                <select name="program_type">
                    <option value="">Todos los Programas</option>
                    <option value="maestria" <?php echo (isset($_GET['program_type']) && $_GET['program_type'] == 'maestria') ? 'selected' : ''; ?>>Maestría</option>
                    <option value="doctorado" <?php echo (isset($_GET['program_type']) && $_GET['program_type'] == 'doctorado') ? 'selected' : ''; ?>>Doctorado</option>
                </select>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <button type="submit">Filtrar</button>
                <a href="generate_excel.php?<?php echo http_build_query($_GET); ?>" class="button" target="_blank">Exportar a Excel</a>
            </form>

            <div class="dashboard-summary">
                <div class="summary-card">
                    <h3>Total Recaudado (Filtro)</h3>
                    <p>S/ <?php echo number_format($total_recaudado, 2); ?></p>
                </div>
            </div>

            <h2>Estudiantes con Pagos Atrasados</h2>
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Última Fecha de Pago</th>
                        <th>Días de Atraso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($late_students)): ?>
                        <tr>
                            <td colspan="3">No hay estudiantes con pagos atrasados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($late_students as $student): ?>
                            <tr>
                                <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                <td><?php echo $student['last_payment_date']; ?></td>
                                <td><?php echo $student['days_late']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>