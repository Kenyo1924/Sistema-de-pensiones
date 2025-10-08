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

    public function search($search_term, $semester_id = null, $payment_type = null, $start_date = null, $end_date = null) {
        $query = "SELECT p.*, s.name as semester_name, st.first_name, st.last_name, st.dni
                  FROM " . $this->table_name . " p
                  LEFT JOIN semesters s ON p.semester_id = s.id
                  LEFT JOIN students st ON p.student_id = st.id";

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
        } elseif ($prog === 'maestria') {
            if ($payment_type == 'matricula') return $is_beca ? 50 : 100;
            else return $is_beca ? 150 : 300;
        }
        return 0;
    }

    public function create() {
        // Calcular el monto automático
        $this->amount = $this->calculateAmount($this->student_id, $this->payment_type);

        $query = "INSERT INTO " . $this->table_name . " SET student_id=:student_id, semester_id=:semester_id, payment_type=:payment_type, amount=:amount, payment_date=:payment_date, voucher_path=:voucher_path, payment_code=:payment_code";
        $stmt = $this->conn->prepare($query);

        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $this->semester_id = htmlspecialchars(strip_tags($this->semester_id));
        $this->payment_type = htmlspecialchars(strip_tags($this->payment_type));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->payment_date = htmlspecialchars(strip_tags($this->payment_date));
        $this->voucher_path = htmlspecialchars(strip_tags($this->voucher_path));
        $this->payment_code = htmlspecialchars(strip_tags($this->payment_code));

        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":semester_id", $this->semester_id);
        $stmt->bindParam(":payment_type", $this->payment_type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":payment_date", $this->payment_date);
        $stmt->bindParam(":voucher_path", $this->voucher_path);
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
}
?>