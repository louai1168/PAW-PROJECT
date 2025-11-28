<?php
require_once __DIR__ . '/../config/database.php';

class Student {
    private $conn;
    private $table = 'students';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $matricule, $group_id, $enrollment_year, $level) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (student_id, matricule, group_id, enrollment_year, level) 
                     VALUES (:user_id, :matricule, :group_id, :enrollment_year, :level)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':matricule', $matricule);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->bindParam(':enrollment_year', $enrollment_year);
            $stmt->bindParam(':level', $level);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Student creation error: " . $e->getMessage());
            throw new Exception("Failed to create student");
        }
    }

    public function getAll($search = '', $page = 1, $per_page = 20) {
        try {
            $offset = ($page - 1) * $per_page;
            
            $where = "";
            $params = [];
            
            if (!empty($search)) {
                $where = "WHERE u.full_name LIKE :search OR s.matricule LIKE :search";
                $params[':search'] = "%{$search}%";
            }
            
            $query = "SELECT s.student_id, u.full_name, u.email, s.matricule, 
                            s.enrollment_year, s.level, s.group_id, g.group_name, g.department
                     FROM " . $this->table . " s
                     JOIN users u ON s.student_id = u.user_id
                     LEFT JOIN `groups` g ON s.group_id = g.group_id
                     {$where}
                     ORDER BY u.full_name
                     LIMIT :offset, :per_page";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get students error: " . $e->getMessage());
            return [];
        }
    }

    public function getById($student_id) {
        try {
            $query = "SELECT s.student_id, u.full_name, u.email, u.username, s.matricule, 
                            s.enrollment_year, s.level, s.group_id, g.group_name, g.department
                     FROM " . $this->table . " s
                     JOIN users u ON s.student_id = u.user_id
                     LEFT JOIN `groups` g ON s.group_id = g.group_id
                     WHERE s.student_id = :student_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get student error: " . $e->getMessage());
            return false;
        }
    }

    public function getByGroup($group_id) {
        try {
            $query = "SELECT s.student_id, u.full_name, u.email, s.matricule, s.level,
                            g.group_name
                     FROM " . $this->table . " s
                     JOIN users u ON s.student_id = u.user_id
                     LEFT JOIN `groups` g ON s.group_id = g.group_id
                     WHERE s.group_id = :group_id AND u.is_active = 1
                     ORDER BY u.full_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get students by group error: " . $e->getMessage());
            return [];
        }
    }

    public function update($student_id, $data) {
        try {
            $fields = [];
            $params = [':student_id' => $student_id];
            
            if (isset($data['matricule'])) {
                $fields[] = "matricule = :matricule";
                $params[':matricule'] = $data['matricule'];
            }
            if (isset($data['group_id'])) {
                $fields[] = "group_id = :group_id";
                $params[':group_id'] = $data['group_id'];
            }
            if (isset($data['level'])) {
                $fields[] = "level = :level";
                $params[':level'] = $data['level'];
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE student_id = :student_id";
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update student error: " . $e->getMessage());
            throw new Exception("Failed to update student");
        }
    }

    public function delete($student_id) {
        try {
            $query = "DELETE FROM users WHERE user_id = :student_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete student error: " . $e->getMessage());
            throw new Exception("Failed to delete student");
        }
    }

    public function count($search = '') {
        try {
            $where = "";
            $params = [];
            
            if (!empty($search)) {
                $where = "WHERE u.full_name LIKE :search OR s.matricule LIKE :search";
                $params[':search'] = "%{$search}%";
            }
            
            $query = "SELECT COUNT(*) as total
                     FROM " . $this->table . " s
                     JOIN users u ON s.student_id = u.user_id
                     {$where}";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Count students error: " . $e->getMessage());
            return 0;
        }
    }
}
?>
