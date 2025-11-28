<?php
require_once __DIR__ . '/../config/database.php';

class Justification {
    private $conn;
    private $table = 'justifications';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function submit($student_id, $session_id, $reason, $file_path = null) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (student_id, session_id, reason, file_path, status) 
                     VALUES (:student_id, :session_id, :reason, :file_path, 'pending')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':session_id', $session_id);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':file_path', $file_path);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Submit justification error: " . $e->getMessage());
            throw new Exception("Failed to submit justification");
        }
    }

    public function getById($justification_id) {
        try {
            $query = "SELECT j.*, u.full_name as student_name, s.matricule,
                            ats.session_date, c.course_name,
                            rev.full_name as reviewer_name
                     FROM " . $this->table . " j
                     JOIN students s ON j.student_id = s.student_id
                     JOIN users u ON s.student_id = u.user_id
                     JOIN attendance_sessions ats ON j.session_id = ats.session_id
                     JOIN courses c ON ats.course_id = c.course_id
                     LEFT JOIN users rev ON j.reviewed_by = rev.user_id
                     WHERE j.justification_id = :justification_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':justification_id', $justification_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get justification error: " . $e->getMessage());
            return false;
        }
    }

    public function getByStudent($student_id) {
        try {
            $query = "SELECT j.*, ats.session_date, c.course_name, c.course_code,
                            rev.full_name as reviewer_name
                     FROM " . $this->table . " j
                     JOIN attendance_sessions ats ON j.session_id = ats.session_id
                     JOIN courses c ON ats.course_id = c.course_id
                     LEFT JOIN users rev ON j.reviewed_by = rev.user_id
                     WHERE j.student_id = :student_id
                     ORDER BY j.submitted_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get justifications by student error: " . $e->getMessage());
            return [];
        }
    }

    public function getPending($professor_id = null) {
        try {
            $where = "j.status = 'pending'";
            $params = [];
            
            if ($professor_id !== null) {
                $where .= " AND ats.professor_id = :professor_id";
                $params[':professor_id'] = $professor_id;
            }
            
            $query = "SELECT j.*, u.full_name as student_name, s.matricule,
                            ats.session_date, c.course_name, c.course_code
                     FROM " . $this->table . " j
                     JOIN students s ON j.student_id = s.student_id
                     JOIN users u ON s.student_id = u.user_id
                     JOIN attendance_sessions ats ON j.session_id = ats.session_id
                     JOIN courses c ON ats.course_id = c.course_id
                     WHERE {$where}
                     ORDER BY j.submitted_at ASC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get pending justifications error: " . $e->getMessage());
            return [];
        }
    }

    public function approve($justification_id, $reviewer_id, $reviewer_notes = '') {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = 'approved', 
                         reviewed_at = NOW(), 
                         reviewed_by = :reviewer_id,
                         reviewer_notes = :reviewer_notes
                     WHERE justification_id = :justification_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':justification_id', $justification_id);
            $stmt->bindParam(':reviewer_id', $reviewer_id);
            $stmt->bindParam(':reviewer_notes', $reviewer_notes);
            
            if ($stmt->execute()) {
                $get_session = "SELECT session_id, student_id FROM " . $this->table . " 
                               WHERE justification_id = :justification_id";
                $stmt_get = $this->conn->prepare($get_session);
                $stmt_get->bindParam(':justification_id', $justification_id);
                $stmt_get->execute();
                $data = $stmt_get->fetch(PDO::FETCH_ASSOC);
                
                if ($data) {
                    $update_attendance = "UPDATE attendance_records 
                                        SET status = 'excused' 
                                        WHERE session_id = :session_id AND student_id = :student_id";
                    $stmt_update = $this->conn->prepare($update_attendance);
                    $stmt_update->bindParam(':session_id', $data['session_id']);
                    $stmt_update->bindParam(':student_id', $data['student_id']);
                    $stmt_update->execute();
                }
                
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Approve justification error: " . $e->getMessage());
            return false;
        }
    }

    public function reject($justification_id, $reviewer_id, $reviewer_notes = '') {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = 'rejected', 
                         reviewed_at = NOW(), 
                         reviewed_by = :reviewer_id,
                         reviewer_notes = :reviewer_notes
                     WHERE justification_id = :justification_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':justification_id', $justification_id);
            $stmt->bindParam(':reviewer_id', $reviewer_id);
            $stmt->bindParam(':reviewer_notes', $reviewer_notes);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Reject justification error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($justification_id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE justification_id = :justification_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':justification_id', $justification_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete justification error: " . $e->getMessage());
            return false;
        }
    }
}
?>
