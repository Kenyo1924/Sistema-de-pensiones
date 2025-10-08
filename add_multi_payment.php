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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_multi_payment']) && $_POST['submit_multi_payment'] === '1') {
    $payment = new Payment($db);
    $student_id = $_POST['student_id'];
    $progress_program_type = null;
    
    // Obtener datos del avance seleccionado
    if (!empty($_POST['progress_id'])) {
        $progress_stmt = $db->prepare("SELECT semester_id, program_type FROM student_semester_progress WHERE id = ?");
        $progress_stmt->execute([$_POST['progress_id']]);
        $progress_data = $progress_stmt->fetch(PDO::FETCH_ASSOC);
        $semester_id = $progress_data ? $progress_data['semester_id'] : null;
        $progress_program_type = $progress_data ? $progress_data['program_type'] : null;
    } else {
        $semester_id = null;
        $progress_program_type = null;
    }
    
    $amounts = $_POST['amounts'] ?? [];
    $payment_date = $_POST['payment_date'];
    $payment_code = $_POST['payment_code'];
    $voucher_description = $_POST['voucher_description'] ?? '';
    
    // Preparar datos para el pago múltiple basado en los montos ingresados
    $payment_data = [];
    $payment_types = ['matricula', 'pension_1', 'pension_2', 'pension_3', 'pension_4'];
    
    foreach ($payment_types as $type) {
        if (isset($amounts[$type]) && $amounts[$type] > 0) {
            $payment_data[] = [
                'type' => $type,
                'amount' => $amounts[$type]
            ];
        }
    }
    
    if (empty($payment_data)) {
        $message = "Debe ingresar al menos un monto válido para algún tipo de pago.";
    } elseif ($payment->existsByPaymentCodeIndividual($payment_code)) {
        $message = "El código de voucher ya existe en un pago individual. Por favor, ingrese un código diferente.";
    } else {
            $result = $payment->createMultiPaymentWithAmounts(
                $student_id,
                $semester_id,
                $payment_data,
                $payment_date,
                $payment_code,
                $voucher_description
            );
            
            if ($result['success']) {
                header("Location: payments.php");
                exit();
            } else {
                $message = "Error al registrar el pago múltiple: " . $result['error'];
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Pago Múltiple</title>
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
            <h1>Agregar Pago Múltiple</h1>
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
            <h2>Agregar Nuevo Pago Múltiple</h2>
            <?php if ($message): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            
            <!-- Paso 1: Seleccionar estudiante -->
            <form action="add_multi_payment.php" method="get" class="search-form">
                <div class="form-group">
                    <label for="search">Buscar Estudiante</label>
                    <input type="text" name="search" id="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Buscar</button>
                </div>
            </form>

            <?php if (!empty($students)) : ?>
                <!-- Paso 2: Elegir estudiante -->
                <form action="add_multi_payment.php" method="get" class="form" style="margin-bottom: 20px;">
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
            // Mostrar el formulario de pago múltiple solo si el estudiante está seleccionado
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
                    <form action="add_multi_payment.php" method="post" id="multiPaymentForm">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
                        
                        <div class="form-group">
                            <label for="progress_id">Selecciona avance a pagar</label>
                            <select name="progress_id" id="progress_id" required onchange="updateSuggestedAmounts()">
                                <option value="">Selecciona...</option>
                                <?php foreach ($progress_options as $option): ?>
                                    <option value="<?php echo $option['id']; ?>">
                                        <?php echo strtoupper($option['program_type']) . ", Sem. curricular: " . $option['semester_number'] . ", Academico: " . htmlspecialchars($option['semester_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Ingresa los montos para los tipos de pago que deseas incluir:</label>
                            <div class="payment-types-grid">
                                <div class="payment-type-card" id="card_matricula">
                                    <div style="font-size: 1.5em;">🎓</div>
                                    <div><strong>Matrícula</strong></div>
                                    <div class="amount-input-container">
                                        <input type="number" name="amounts[matricula]" id="amount_matricula"
                                               class="amount-input" step="0.01" min="0"
                                               placeholder="0.00" onchange="updatePaymentType('matricula')" oninput="updateTotal()">
                                        <span class="currency">S/</span>
                                    </div>
                                    <div class="suggested-amount" id="suggested_matricula">Sugerido: S/ 0.00</div>
                                </div>

                                <div class="payment-type-card" id="card_pension_1">
                                    <div style="font-size: 1.5em;">📚</div>
                                    <div><strong>Pensión 1</strong></div>
                                    <div class="amount-input-container">
                                        <input type="number" name="amounts[pension_1]" id="amount_pension_1"
                                               class="amount-input" step="0.01" min="0"
                                               placeholder="0.00" onchange="updatePaymentType('pension_1')" oninput="updateTotal()">
                                        <span class="currency">S/</span>
                                    </div>
                                    <div class="suggested-amount" id="suggested_pension_1">Sugerido: S/ 0.00</div>
                                </div>

                                <div class="payment-type-card" id="card_pension_2">
                                    <div style="font-size: 1.5em;">📖</div>
                                    <div><strong>Pensión 2</strong></div>
                                    <div class="amount-input-container">
                                        <input type="number" name="amounts[pension_2]" id="amount_pension_2"
                                               class="amount-input" step="0.01" min="0"
                                               placeholder="0.00" onchange="updatePaymentType('pension_2')" oninput="updateTotal()">
                                        <span class="currency">S/</span>
                                    </div>
                                    <div class="suggested-amount" id="suggested_pension_2">Sugerido: S/ 0.00</div>
                                </div>

                                <div class="payment-type-card" id="card_pension_3">
                                    <div style="font-size: 1.5em;">📝</div>
                                    <div><strong>Pensión 3</strong></div>
                                    <div class="amount-input-container">
                                        <input type="number" name="amounts[pension_3]" id="amount_pension_3"
                                               class="amount-input" step="0.01" min="0"
                                               placeholder="0.00" onchange="updatePaymentType('pension_3')" oninput="updateTotal()">
                                        <span class="currency">S/</span>
                                    </div>
                                    <div class="suggested-amount" id="suggested_pension_3">Sugerido: S/ 0.00</div>
                                </div>

                                <div class="payment-type-card" id="card_pension_4">
                                    <div style="font-size: 1.5em;">🎯</div>
                                    <div><strong>Pensión 4</strong></div>
                                    <div class="amount-input-container">
                                        <input type="number" name="amounts[pension_4]" id="amount_pension_4"
                                               class="amount-input" step="0.01" min="0"
                                               placeholder="0.00" onchange="updatePaymentType('pension_4')" oninput="updateTotal()">
                                        <span class="currency">S/</span>
                                    </div>
                                    <div class="suggested-amount" id="suggested_pension_4">Sugerido: S/ 0.00</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="payment_date">Fecha de Pago</label>
                            <input type="date" name="payment_date" id="payment_date" required>
                        </div>

                        <div class="form-group">
                            <label for="payment_code">Código de Voucher</label>
                            <input type="text" name="payment_code" id="payment_code" required placeholder="Ej: VCH-2024-001, BOL-123456, etc.">
                        </div>

                        <div class="form-group">
                            <label for="voucher_description">Descripción del Voucher (Opcional)</label>
                            <textarea name="voucher_description" id="voucher_description" rows="3" placeholder="Ej: Pago de matrícula y primera pensión"></textarea>
                        </div>

                        <div class="total-preview">
                            <strong>Total a Pagar: S/ <span id="total-amount">0.00</span></strong>
                        </div>

                        <button type="submit" name="submit_multi_payment" value="1">Registrar Pago Múltiple</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Establecer fecha actual por defecto
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('payment_date').value = today;
        });

        function updatePaymentType(type) {
            const amountInput = document.getElementById('amount_' + type);
            const card = document.getElementById('card_' + type);
            const amount = parseFloat(amountInput.value) || 0;
            
            if (amount > 0) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        }

        function updateTotal() {
            let total = 0;
            const paymentTypes = ['matricula', 'pension_1', 'pension_2', 'pension_3', 'pension_4'];
            
            paymentTypes.forEach(type => {
                const amountInput = document.getElementById('amount_' + type);
                const amount = parseFloat(amountInput.value) || 0;
                total += amount;
            });
            
            document.getElementById('total-amount').textContent = total.toFixed(2);
        }

        function getSuggestedAmount(programType, paymentType) {
            const amounts = {
                'doctorado': {
                    'matricula': 150,
                    'pension_1': 400,
                    'pension_2': 400,
                    'pension_3': 400,
                    'pension_4': 400
                },
                'maestria_gestion_educativa': {
                    'matricula': 100,
                    'pension_1': 300,
                    'pension_2': 300,
                    'pension_3': 300,
                    'pension_4': 300
                },
                'maestria_educacion_superior': {
                    'matricula': 100,
                    'pension_1': 300,
                    'pension_2': 300,
                    'pension_3': 300,
                    'pension_4': 300
                },
                'maestria_psicologia_educativa': {
                    'matricula': 100,
                    'pension_1': 300,
                    'pension_2': 300,
                    'pension_3': 300,
                    'pension_4': 300
                },
                'maestria_ensenanza_estrategica': {
                    'matricula': 100,
                    'pension_1': 300,
                    'pension_2': 300,
                    'pension_3': 300,
                    'pension_4': 300
                }
            };
            
            return amounts[programType] && amounts[programType][paymentType] ? amounts[programType][paymentType] : 0;
        }

        function updateSuggestedAmounts() {
            const progressSelect = document.getElementById('progress_id');
            const selectedOption = progressSelect.options[progressSelect.selectedIndex];
            
            if (selectedOption.value) {
                const programType = selectedOption.text.split(',')[0].toLowerCase();
                
                const paymentTypes = ['matricula', 'pension_1', 'pension_2', 'pension_3', 'pension_4'];
                paymentTypes.forEach(type => {
                    const suggestedAmount = getSuggestedAmount(programType, type);
                    const suggestedElement = document.getElementById('suggested_' + type);
                    suggestedElement.textContent = 'Sugerido: S/ ' + suggestedAmount.toFixed(2);
                    
                    // NO llenar automáticamente, solo mostrar sugerencia
                    // El usuario debe escribir manualmente el monto que desea
                });
            }
        }

        // Actualizar total cuando cambie cualquier input de monto
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('amount-input')) {
                updateTotal();
            }
        });
    </script>
</body>
</html>