<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/payment.php';

$database = new Database();
$db = $database->getConnection();
$payment = new Payment($db);

$voucher_group_id = $_GET['id'] ?? '';

if (empty($voucher_group_id)) {
    header("Location: payments.php");
    exit();
}

$payment_details = $payment->getPaymentGroupDetails($voucher_group_id);

if (empty($payment_details)) {
    header("Location: payments.php");
    exit();
}

$total_amount = array_sum(array_column($payment_details, 'amount'));
$payment_types = array_unique(array_column($payment_details, 'payment_type'));
$student_info = $payment_details[0]; // Información del estudiante (es la misma para todos)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Voucher</title>
    <link rel="stylesheet" href="frontend/css/style.css">
    <style>
        .voucher-header {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .voucher-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }
        .payment-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .payment-type-badge {
            background: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .total-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>
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
            <h1>Detalles del Voucher</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php">Pagos</a>
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
            <div class="voucher-header">
                <h2><span class="emoji">🧾</span> Voucher de Pago Múltiple</h2>
                <p>Código: <strong><?php echo htmlspecialchars($student_info['payment_code']); ?></strong></p>
                <?php if (!empty($student_info['voucher_description'])): ?>
                    <p><em><?php echo htmlspecialchars($student_info['voucher_description']); ?></em></p>
                <?php endif; ?>
            </div>
            
            <div class="voucher-info">
                <div class="info-card">
                    <h4><span class="emoji">👤</span> Estudiante</h4>
                    <p><strong><?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></strong></p>
                </div>
                
                <div class="info-card">
                    <h4><span class="emoji">📅</span> Fecha de Pago</h4>
                    <p><strong><?php echo date('d/m/Y', strtotime($student_info['payment_date'])); ?></strong></p>
                </div>
                
                <div class="info-card">
                    <h4><span class="emoji">🎓</span> Semestre</h4>
                    <p><strong><?php echo htmlspecialchars($student_info['semester_name']); ?></strong></p>
                </div>
                
                <div class="info-card">
                    <h4><span class="emoji">📚</span> Programa</h4>
                    <p><strong><?php echo htmlspecialchars($student_info['program_type']); ?></strong></p>
                </div>
            </div>
            
            <h3><span class="emoji">💳</span> Detalle de Pagos</h3>
            
            <?php foreach ($payment_details as $detail): ?>
                <div class="payment-item">
                    <div>
                        <span class="payment-type-badge">
                            <?php 
                            switch($detail['payment_type']) {
                                case 'matricula': echo '🎓 Matrícula'; break;
                                case 'pension_1': echo '💰 Pensión 1'; break;
                                case 'pension_2': echo '💰 Pensión 2'; break;
                                case 'pension_3': echo '💰 Pensión 3'; break;
                                case 'pension_4': echo '💰 Pensión 4'; break;
                                default: echo $detail['payment_type'];
                            }
                            ?>
                        </span>
                    </div>
                    <div>
                        <strong>S/ <?php echo number_format($detail['amount'], 2); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="total-card">
                <span class="emoji">💰</span> Total Pagado: S/ <?php echo number_format($total_amount, 2); ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="payments.php" class="button">Volver a Pagos</a>
                <a href="view_student.php?id=<?php echo $student_info['student_id']; ?>" class="button">Ver Estudiante</a>
            </div>
        </main>
    </div>
</body>
</html>
