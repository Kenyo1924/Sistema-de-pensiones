<?php
class Payment {
    private $conn;
    private $table_name = "payments";

    public $id;
    public $student_id;
    public $semester_id;
    public $payment_type;
    public $amount;
    public $payment_date;
    public $voucher_path;
    public $payment_code;
    public $voucher_group_id;
    public $is_multi_payment;
    public $voucher_description;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByStudentId($student_id) {
        $query = "SELECT p.*, s.name as semester_name FROM " . $this->table_name . " p
                  LEFT JOIN semesters s ON p.semester_id = s.id
                  WHERE p.student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $query = "SELECT p.*, s.name as semester_name, st.first_name, st.last_name FROM " . $this->table_name . " p
                  LEFT JOIN semesters s ON p.semester_id = s.id
                  LEFT JOIN students st ON p.student_id = st.id
                  ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search($search_term, $semester_id = null, $payment_type = null, $start_date = null, $end_date = null, $program_type = null) {
        $query = "SELECT p.*, s.name as semester_name, st.first_name, st.last_name, st.dni, 
                         COALESCE(p.program_type, sp.program_type) AS program_type
                  FROM " . $this->table_name . " p
                  LEFT JOIN semesters s ON p.semester_id = s.id
                  LEFT JOIN students st ON p.student_id = st.id
                  LEFT JOIN student_programs sp ON sp.student_id = st.id
                      AND (sp.start_date IS NULL OR sp.start_date <= p.payment_date)
                      AND (sp.end_date IS NULL OR sp.end_date >= p.payment_date)";

        $where_clauses = [];
        $params = [];

        if ($search_term) {
            $where_clauses[] = "(st.first_name LIKE ? OR st.last_name LIKE ? OR p.payment_code LIKE ?)";
            $params[] = "%{$search_term}%";
            $params[] = "%{$search_term}%";
            $params[] = "%{$search_term}%";
        }

        if ($semester_id) {
            $where_clauses[] = "p.semester_id = ?";
            $params[] = $semester_id;
        }

        if ($payment_type) {
            $where_clauses[] = "p.payment_type = ?";
            $params[] = $payment_type;
        }

        if ($start_date) {
            $where_clauses[] = "p.payment_date >= ?";
            $params[] = $start_date;
        }

        if ($end_date) {
            $where_clauses[] = "p.payment_date <= ?";
            $params[] = $end_date;
        }

        if ($program_type) {
            $where_clauses[] = "COALESCE(p.program_type, sp.program_type) = ?";
            $params[] = $program_type;
        }

        if (count($where_clauses) > 0) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function calculateAmount($student_id, $payment_type) {
        // Intentar obtener programa y beca desde student_programs (actual)
        $prog = null;
        $is_beca = 0;

        // Preferir programa activo (sin end_date o vigente), si no, el más reciente
        $query = "SELECT program_type, is_scholarship_holder
                  FROM student_programs
                  WHERE student_id = ? AND (end_date IS NULL OR end_date >= CURDATE())
                  ORDER BY COALESCE(end_date, '9999-12-31') DESC, start_date DESC, id DESC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_id);
        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $prog = $row['program_type'];
                $is_beca = (int)$row['is_scholarship_holder'];
            }
        }

        if ($prog === null) {
            // Respaldo: tomar el último registro disponible en student_programs
            $fallback = $this->conn->prepare("SELECT program_type, is_scholarship_holder FROM student_programs WHERE student_id = ? ORDER BY start_date DESC, id DESC LIMIT 1");
            $fallback->bindParam(1, $student_id);
            if ($fallback->execute()) {
                $row = $fallback->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $prog = $row['program_type'];
                    $is_beca = (int)$row['is_scholarship_holder'];
                }
            }
        }

        if ($prog === null) {
            // Compatibilidad hacia atrás: leer de students si aún existen las columnas
            try {
                $studentQuery = "SELECT program_type, is_scholarship_holder FROM students WHERE id = ?";
                $stmt2 = $this->conn->prepare($studentQuery);
                $stmt2->bindParam(1, $student_id);
                $stmt2->execute();
                $student = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($student) {
                    $prog = isset($student['program_type']) ? $student['program_type'] : null;
                    $is_beca = isset($student['is_scholarship_holder']) ? (int)$student['is_scholarship_holder'] : 0;
                }
            } catch (Exception $e) {
                // Si falla (columna no existe), dejar $prog como null para devolver 0
            }
        }

        if ($prog === 'doctorado') {
            if ($payment_type == 'matricula') return $is_beca ? 100 : 200;
            else return $is_beca ? 175 : 350;
        } elseif (in_array($prog, ['maestria_gestion_educativa', 'maestria_educacion_superior', 'maestria_psicologia_educativa', 'maestria_ensenanza_estrategica'])) {
            if ($payment_type == 'matricula') return $is_beca ? 50 : 100;
            else return $is_beca ? 150 : 300;
        }
        return 0;
    }

    public function create() {
        // Calcular el monto automático
        $this->amount = $this->calculateAmount($this->student_id, $this->payment_type);

        $query = "INSERT INTO " . $this->table_name . " SET student_id=:student_id, semester_id=:semester_id, payment_type=:payment_type, amount=:amount, payment_date=:payment_date, payment_code=:payment_code";
        $stmt = $this->conn->prepare($query);

        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $this->semester_id = htmlspecialchars(strip_tags($this->semester_id));
        $this->payment_type = htmlspecialchars(strip_tags($this->payment_type));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->payment_date = htmlspecialchars(strip_tags($this->payment_date));
        $this->payment_code = htmlspecialchars(strip_tags($this->payment_code));

        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":semester_id", $this->semester_id);
        $stmt->bindParam(":payment_type", $this->payment_type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":payment_date", $this->payment_date);
        $stmt->bindParam(":payment_code", $this->payment_code);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function getTotalPaymentsToday() {
        $query = "SELECT SUM(amount) as total FROM " . $this->table_name . " WHERE DATE(payment_date) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getMonthlyPayments() {
        $query = "SELECT MONTH(payment_date) as month, SUM(amount) as total
                  FROM " . $this->table_name . "
                  WHERE YEAR(payment_date) = YEAR(CURDATE())
                  GROUP BY MONTH(payment_date)
                  ORDER BY MONTH(payment_date)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function existsByPaymentCode($code) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE payment_code = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $code);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }

    // Método para verificar si un código de voucher ya existe en pagos individuales (no múltiples)
    public function existsByPaymentCodeIndividual($code) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE payment_code = ? AND (is_multi_payment = 0 OR is_multi_payment IS NULL)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $code);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }

    // Método para crear pagos múltiples con un solo voucher
    public function createMultiPayment($student_id, $semester_id, $payment_types, $payment_date, $payment_code, $voucher_description = null) {
        try {
            $this->conn->beginTransaction();
            
            $voucher_group_id = $payment_code . '_' . time(); // ID único para el grupo
            $total_amount = 0;
            
            foreach ($payment_types as $payment_type) {
                $amount = $this->calculateAmount($student_id, $payment_type);
                $total_amount += $amount;
                
                $query = "INSERT INTO " . $this->table_name . " 
                         (student_id, semester_id, program_type, payment_type, amount, payment_date, payment_code, voucher_group_id, is_multi_payment, voucher_description) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->conn->prepare($query);
                
                // Obtener program_type del estudiante
                $program_type = $this->getStudentProgramType($student_id);
                
                $stmt->execute([
                    $student_id,
                    $semester_id,
                    $program_type,
                    $payment_type,
                    $amount,
                    $payment_date,
                    $payment_code,
                    $voucher_group_id,
                    true,
                    $voucher_description
                ]);
            }
            
            $this->conn->commit();
            return ['success' => true, 'total_amount' => $total_amount, 'voucher_group_id' => $voucher_group_id];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Método para crear pagos múltiples con montos personalizados
    public function createMultiPaymentWithAmounts($student_id, $semester_id, $payment_data, $payment_date, $payment_code, $voucher_description = null) {
        try {
            $this->conn->beginTransaction();
            
            $voucher_group_id = $payment_code . '_' . time(); // ID único para el grupo
            $total_amount = 0;
            
            foreach ($payment_data as $payment) {
                $payment_type = $payment['type'];
                $amount = $payment['amount'];
                $total_amount += $amount;
                
                $query = "INSERT INTO " . $this->table_name . " 
                         (student_id, semester_id, program_type, payment_type, amount, payment_date, payment_code, voucher_group_id, is_multi_payment, voucher_description) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->conn->prepare($query);
                
                // Obtener program_type del estudiante
                $program_type = $this->getStudentProgramType($student_id);
                
                $stmt->execute([
                    $student_id,
                    $semester_id,
                    $program_type,
                    $payment_type,
                    $amount,
                    $payment_date,
                    $payment_code,
                    $voucher_group_id,
                    true,
                    $voucher_description
                ]);
            }
            
            $this->conn->commit();
            return ['success' => true, 'total_amount' => $total_amount, 'voucher_group_id' => $voucher_group_id];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Obtener el tipo de programa del estudiante
    private function getStudentProgramType($student_id) {
        $query = "SELECT program_type FROM student_programs 
                  WHERE student_id = ? AND (end_date IS NULL OR end_date >= CURDATE())
                  ORDER BY COALESCE(end_date, '9999-12-31') DESC, start_date DESC, id DESC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['program_type'] : 'maestria_gestion_educativa';
    }
    
    // Obtener pagos agrupados por voucher
    public function getPaymentGroups($student_id = null) {
        $query = "SELECT 
                    voucher_group_id,
                    student_id,
                    semester_id,
                    program_type,
                    payment_date,
                    payment_code,
                    voucher_description,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount,
                    GROUP_CONCAT(payment_type ORDER BY payment_type SEPARATOR ', ') as payment_types,
                    GROUP_CONCAT(amount ORDER BY payment_type SEPARATOR ', ') as amounts,
                    created_at
                  FROM " . $this->table_name . " 
                  WHERE voucher_group_id IS NOT NULL";
        
        $params = [];
        if ($student_id) {
            $query .= " AND student_id = ?";
            $params[] = $student_id;
        }
        
        $query .= " GROUP BY voucher_group_id, student_id, semester_id, program_type, payment_date, payment_code, voucher_description, created_at
                    ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener detalles de un grupo de pagos
    public function getPaymentGroupDetails($voucher_group_id) {
        $query = "SELECT p.*, s.name as semester_name, st.first_name, st.last_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN semesters s ON p.semester_id = s.id
                  LEFT JOIN students st ON p.student_id = st.id
                  WHERE p.voucher_group_id = ?
                  ORDER BY p.payment_type";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $voucher_group_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>