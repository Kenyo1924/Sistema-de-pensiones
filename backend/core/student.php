<?php
class Student {
    private $conn;
    private $table_name = "students";

    public $id;
    public $first_name;
    public $last_name;
    public $dni;
    // program_type y is_scholarship_holder ahora se gestionan vía student_programs

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener trayectorias/programas de un estudiante por su ID
    public function getProgramsByStudentId($student_id) {
        $query = "SELECT program_type, is_scholarship_holder FROM student_programs WHERE student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener todos los estudiantes junto a sus trayectorias/programas
    public function getAllWithPrograms() {
        $query = "
            SELECT s.*, 
                GROUP_CONCAT(sp.program_type ORDER BY sp.program_type SEPARATOR ',') AS programs,
                GROUP_CONCAT(sp.is_scholarship_holder ORDER BY sp.program_type SEPARATOR ',') AS scholarships
            FROM students s
            LEFT JOIN student_programs sp ON s.id = sp.student_id
            GROUP BY s.id
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search($search_term, $semester_id = null) {
        $query = "SELECT s.* FROM " . $this->table_name . " s";
        $params = [];
        $where_clauses = [];

        if ($semester_id) {
            $query .= " INNER JOIN payments p ON s.id = p.student_id WHERE p.semester_id = ?";
            $params[] = $semester_id;
        }

        if ($search_term) {
            $search_where = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.dni LIKE ?)";
            if ($semester_id) {
                $query .= " AND " . $search_where;
            } else {
                $query .= " WHERE " . $search_where;
            }
            $params[] = "%{$search_term}%";
            $params[] = "%{$search_term}%";
            $params[] = "%{$search_term}%";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crea un estudiante (no añade programas, solo datos principales)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (first_name, last_name, dni, created_at)
                  VALUES (:first_name, :last_name, :dni, NOW())";
        $stmt = $this->conn->prepare($query);
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->dni = htmlspecialchars(strip_tags($this->dni));
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':dni', $this->dni);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    // Los programas se añaden con métodos específicos y a través de student_programs

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // La actualización de un estudiante ahora no incluye información de programas

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getTotalCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
public function existsByDni($dni) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE dni = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dni);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }
}
?>