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
$total_students = $student->getTotalCount();

$payment = new Payment($db);
$total_payments_today = $payment->getTotalPaymentsToday();
$monthly_payments = $payment->getMonthlyPayments();

$months = [];
$totals = [];
for ($i = 1; $i <= 12; $i++) {
    $month_name = date('F', mktime(0, 0, 0, $i, 10));
    $months[] = $month_name;
    $totals[] = 0;
}

foreach ($monthly_payments as $p) {
    $totals[$p['month'] - 1] = $p['total'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control</title>
    <link rel="stylesheet" href="frontend/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1><span class="emoji">🏠</span> Sistema de Pensiones</h1>
            <nav>
                <a href="dashboard.php" class="active">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor'): ?>
                    <a href="add_semester.php">Agregar Semestre</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2><span class="emoji">👋</span> Bienvenido, <?php echo $_SESSION['username']; ?>!</h2>
            
            <div class="dashboard-summary">
                <div class="summary-card">
                    <h3><span class="emoji">🎓</span> Total de Estudiantes</h3>
                    <p><?php echo $total_students; ?></p>
                </div>
                <div class="summary-card">
                    <h3><span class="emoji">💸</span> Pagos de Hoy</h3>
                    <p>S/ <?php echo number_format($total_payments_today, 2); ?></p>
                </div>
                <div class="summary-card">
                    <h3><span class="emoji">⏳</span> Pagos Pendientes</h3>
                    <p>0</p>
                </div>
            </div>

            <div class="chart-container">
                <h3><span class="emoji">📊</span> Pagos Mensuales (Año Actual)</h3>
                <canvas id="monthlyPaymentsChart"></canvas>
            </div>
        </main>
    </div>
    <script>
        const ctx = document.getElementById('monthlyPaymentsChart').getContext('2d');
        const monthlyPaymentsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Total de Pagos',
                    data: <?php echo json_encode($totals); ?>,
                    backgroundColor: 'rgba(0, 198, 255, 0.5)',
                    borderColor: 'rgba(0, 198, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>