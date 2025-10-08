<?php
class Report {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTotalRecaudado($semester_id = '', $payment_type = '', $start_date = '', $end_date = '', $program_type = '') {
        $query = "SELECT SUM(p.amount) as total FROM payments p INNER JOIN students s ON p.student_id = s.id WHERE 1=1";
        $params = [];
        if ($semester_id) {
            $query .= " AND p.semester_id = ?";
            $params[] = $semester_id;
        }
        if ($payment_type) {
            $query .= " AND p.payment_type = ?";
            $params[] = $payment_type;
        }
        if ($start_date) {
            $query .= " AND p.payment_date >= ?";
            $params[] = $start_date;
        }
        if ($end_date) {
            $query .= " AND p.payment_date <= ?";
            $params[] = $end_date;
        }
        if ($program_type) {
            $query .= " AND s.program_type = ?";
            $params[] = $program_type;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getTotalEstimado() {
        // This is a simplified estimation. A more accurate calculation would
        // require knowing the exact number of students per semester and their scholarship status.
        $query = "SELECT
                    (SELECT COUNT(*) FROM students WHERE is_scholarship_holder = 0) * (200 + 350 * 4) +
                    (SELECT COUNT(*) FROM students WHERE is_scholarship_holder = 1) * (100 + 175 * 4)
                  AS total_estimado";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_estimado'] ?? 0;
    }

    public function getLateStudents() {
        $query = "SELECT s.first_name, s.last_name, p.last_payment_date, DATEDIFF(CURDATE(), p.last_payment_date) as days_late
                FROM students s
                LEFT JOIN (
                    SELECT student_id, MAX(payment_date) as last_payment_date
                    FROM payments
                    GROUP BY student_id
                ) p ON s.id = p.student_id
                WHERE p.last_payment_date < DATE_SUB(CURDATE(), INTERVAL 1 MONTH) OR p.last_payment_date IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>