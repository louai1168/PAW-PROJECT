<?php
require_once __DIR__ . '/../config/database.php';

class AttendanceSession {
    private $conn;
    private $table = 'attendance_sessions';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($course_id, $group_id, $professor_id, $session_date, $session_type = 'lecture') {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (course_id, group_id, professor_id, session_date, session_type, status) 
                     VALUES (:course_id, :group_id, :professor_id, :session_date, :session_type, 'open')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->bindParam(':professor_id', $professor_id);
            $stmt->bindParam(':session_date', $session_date);
            $stmt->bindParam(':session_type', $session_type);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Session creation error: " . $e->getMessage());
            throw new Exception("Failed to create session");
        }
    }

    public function getById($session_id) {
        try {
            $query = "SELECT s.*, c.course_name, c.course_code, g.group_name, u.full_name as professor_name
                     FROM " . $this->table . " s
                     JOIN courses c ON s.course_id = c.course_id
                     JOIN `groups` g ON s.group_id = g.group_id
                     JOIN users u ON s.professor_id = u.user_id
                     WHERE s.session_id = :session_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_id', $session_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get session error: " . $e->getMessage());
            return false;
        }
    }

    public function getByCourse($course_id, $group_id = null, $status = null) {
        try {
            $where = ["s.course_id = :course_id"];
            $params = [':course_id' => $course_id];
            
            if ($group_id !== null) {
                $where[] = "s.group_id = :group_id";
                $params[':group_id'] = $group_id;
            }
            
            if ($status !== null) {
                $where[] = "s.status = :status";
                $params[':status'] = $status;
            }
            
            $where_clause = implode(' AND ', $where);
            
            $query = "SELECT s.*, g.group_name,
                            stats.attendance_count,
                            stats.present_count,
                            stats.absent_count,
                            stats.late_count,
                            stats.excused_count
                     FROM " . $this->table . " s
                     JOIN `groups` g ON s.group_id = g.group_id
                     LEFT JOIN (
                        SELECT session_id,
                               COUNT(*) as attendance_count,
                               SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                               SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                               SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                               SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count
                        FROM attendance_records
                        GROUP BY session_id
                     ) stats ON stats.session_id = s.session_id
                     WHERE {$where_clause}
                     ORDER BY s.session_date DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get sessions by course error: " . $e->getMessage());
            return [];
        }
    }

    public function getByProfessor($professor_id, $status = null) {
        try {
            $where = "s.professor_id = :professor_id";
            $params = [':professor_id' => $professor_id];
            
            if ($status !== null) {
                $where .= " AND s.status = :status";
                $params[':status'] = $status;
            }
            
            $query = "SELECT s.*, c.course_name, c.course_code, g.group_name,
                            stats.attendance_count,
                            stats.present_count,
                            stats.absent_count,
                            stats.late_count,
                            stats.excused_count
                     FROM " . $this->table . " s
                     JOIN courses c ON s.course_id = c.course_id
                     JOIN `groups` g ON s.group_id = g.group_id
                     LEFT JOIN (
                        SELECT session_id,
                               COUNT(*) as attendance_count,
                               SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                               SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                               SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                               SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count
                        FROM attendance_records
                        GROUP BY session_id
                     ) stats ON stats.session_id = s.session_id
                     WHERE {$where}
                     ORDER BY s.session_date DESC, s.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get sessions by professor error: " . $e->getMessage());
            return [];
        }
    }

    public function close($session_id) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = 'closed', closed_at = NOW() 
                     WHERE session_id = :session_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_id', $session_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Close session error: " . $e->getMessage());
            return false;
        }
    }

    public function reopen($session_id) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = 'open', closed_at = NULL 
                     WHERE session_id = :session_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_id', $session_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Reopen session error: " . $e->getMessage());
            return false;
        }
    }

    public function getAbsentSessionsForStudent($student_id) {
        try {
            $query = "SELECT ats.session_id, ats.session_date, ats.session_type, ats.start_time, ats.end_time,
                            c.course_name, c.course_code, g.group_name
                     FROM " . $this->table . " ats
                     JOIN courses c ON ats.course_id = c.course_id
                     JOIN `groups` g ON ats.group_id = g.group_id
                     JOIN students s ON s.group_id = ats.group_id
                     JOIN attendance_records ar ON ar.session_id = ats.session_id AND ar.student_id = s.student_id
                     WHERE s.student_id = :student_id AND ar.status = 'absent'
                     ORDER BY ats.session_date DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get absent sessions error: " . $e->getMessage());
            return [];
        }
    }

    public function delete($session_id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE session_id = :session_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_id', $session_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete session error: " . $e->getMessage());
            return false;
        }
    }
}
?>
