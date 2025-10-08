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
$semesters = $semester->getAll();
// Listar pagos filtrados (detalle para tabla) con paginación
include_once 'backend/core/payment.php';
$payment_model = new Payment($db);

// PAGINACIÓN PARA REPORTES
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$all_filtered_payments = $payment_model->search('', $semester_id, $payment_type, $start_date, $end_date, $program_type);
$total_payments = count($all_filtered_payments);
$total_pages = max(1, ceil($total_payments / $per_page));
$filtered_payments = array_slice($all_filtered_payments, ($page - 1) * $per_page, $per_page);

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
            <h1>Reportes</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php" class="active">Reportes</a>
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
                    <option value="doctorado" <?php echo (isset($_GET['program_type']) && $_GET['program_type'] == 'doctorado') ? 'selected' : ''; ?>>Doctorado</option>
                    <option value="maestria_gestion_educativa" <?php echo (isset($_GET['program_type']) && $_GET['program_type'] == 'maestria_gestion_educativa') ? 'selected' : ''; ?>>Maestría en Gestión Educativa</option>
                    <option value="maestria_educacion_superior" <?php echo (isset($_GET['program_type']) && $_GET['program_type'] == 'maestria_educacion_superior') ? 'selected' : ''; ?>>Maestría en Educación Superior</option>
                    <option value="maestria_psicologia_educativa" <?php echo (isset($_GET['program_type']) && $_GET['program_type'] == 'maestria_psicologia_educativa') ? 'selected' : ''; ?>>Maestría en Psicología Educativa</option>
                    <option value="maestria_ensenanza_estrategica" <?php echo (isset($_GET['program_type']) && $_GET['program_type'] == 'maestria_ensenanza_estrategica') ? 'selected' : ''; ?>>Maestría en Enseñanza Estratégica</option>
                </select>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <button type="submit">Filtrar</button>
                <a href="generate_excel.php?<?php echo http_build_query($_GET); ?>" class="button" target="_blank">Exportar a Excel</a>
            </form>
            
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <h2 style="margin:0;">Pagos Detallados del Reporte</h2>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                    <?php
                    $query_params = $_GET;
                    $window = 2;
                    $show_pages = [];
                    if ($total_pages <= 7) {
                        for ($i = 1; $i <= $total_pages; $i++) $show_pages[] = $i;
                    } else {
                        $show_pages[] = 1;
                        if ($page - $window > 2) $show_pages[] = '...';
                        for ($i = max(2, $page - $window); $i <= min($total_pages - 1, $page + $window); $i++) $show_pages[] = $i;
                        if ($page + $window < $total_pages - 1) $show_pages[] = '...';
                        $show_pages[] = $total_pages;
                    }

                    foreach ($show_pages as $i):
                        if ($i === '...') {
                            echo '<span style="padding:0 6px;color:var(--secondary-color);font-weight:bold;">...</span>';
                        } else {
                            $query_params['page'] = $i;
                            $link = '?' . http_build_query($query_params);
                            $active = ($i == $page) ? 'active' : '';
                            echo '<a href="' . htmlspecialchars($link) . '" class="page-link ' . $active . '">' . $i . '</a>';
                        }
                    endforeach;
                    ?>
                    </div>
                <?php endif; ?>
            </div>
            <table>
                <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>DNI</th>
                    <th>Semestre</th>
                    <th>Programa</th>
                    <th>Tipo de Pago</th>
                    <th>Monto</th>
                    <th>Fecha de Pago</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$filtered_payments || count($filtered_payments) === 0): ?>
                <tr><td colspan="7">No hay pagos con los filtros seleccionados.</td></tr>
                <?php else: ?>
                <?php foreach($filtered_payments as $pay): ?>
                    <tr>
                        <td><?php echo $pay['first_name'].' '.$pay['last_name']; ?></td>
                        <td><?php echo $pay['dni']; ?></td>
                        <td><?php echo $pay['semester_name']; ?></td>
                        <td><?php echo isset($pay['program_type']) ? $pay['program_type'] : '-'; ?></td>
                        <td><?php echo $pay['payment_type']; ?></td>
                        <td>S/ <?php echo number_format($pay['amount'],2); ?></td>
                        <td><?php echo $pay['payment_date']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            
            <div class="dashboard-summary">
                <div class="summary-card">
                    <h3>Total Recaudado (Filtro)</h3>
                    <p>S/ <?php echo number_format($total_recaudado, 2); ?></p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>