<?php
require_once __DIR__ . '/../config/database.php';

class Attendance {
    private $conn;
    private $table = 'attendance_records';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function mark($session_id, $student_id, $status, $participation_score = 0, $behavior_notes = '') {
        try {
            $check = "SELECT record_id FROM " . $this->table . " 
                     WHERE session_id = :session_id AND student_id = :student_id";
            $stmt_check = $this->conn->prepare($check);
            $stmt_check->bindParam(':session_id', $session_id);
            $stmt_check->bindParam(':student_id', $student_id);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                $query = "UPDATE " . $this->table . " 
                         SET status = :status, 
                             participation_score = :participation_score, 
                             behavior_notes = :behavior_notes,
                             marked_at = NOW()
                         WHERE session_id = :session_id AND student_id = :student_id";
            } else {
                $query = "INSERT INTO " . $this->table . " 
                         (session_id, student_id, status, participation_score, behavior_notes) 
                         VALUES (:session_id, :student_id, :status, :participation_score, :behavior_notes)";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_id', $session_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':participation_score', $participation_score);
            $stmt->bindParam(':behavior_notes', $behavior_notes);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Mark attendance error: " . $e->getMessage());
            throw new Exception("Failed to mark attendance");
        }
    }

    public function bulkMark($session_id, $attendance_data) {
        try {
            $this->conn->beginTransaction();
            
            foreach ($attendance_data as $data) {
                $this->mark(
                    $session_id, 
                    $data['student_id'], 
                    $data['status'], 
                    $data['participation_score'] ?? 0,
                    $data['behavior_notes'] ?? ''
                );
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Bulk mark attendance error: " . $e->getMessage());
            return false;
        }
    }

    public function getBySession($session_id) {
        try {
            $query = "SELECT ar.*, u.full_name, s.matricule
                     FROM " . $this->table . " ar
                     JOIN students s ON ar.student_id = s.student_id
                     JOIN users u ON s.student_id = u.user_id
                     WHERE ar.session_id = :session_id
                     ORDER BY u.full_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_id', $session_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get attendance by session error: " . $e->getMessage());
            return [];
        }
    }

    public function getByStudent($student_id, $course_id = null) {
        try {
            $where = "ar.student_id = :student_id";
            $params = [':student_id' => $student_id];
            
            if ($course_id !== null) {
                $where .= " AND ats.course_id = :course_id";
                $params[':course_id'] = $course_id;
            }
            
            $query = "SELECT ar.*, ats.session_date, ats.session_type, 
                            c.course_name, c.course_code
                     FROM " . $this->table . " ar
                     JOIN attendance_sessions ats ON ar.session_id = ats.session_id
                     JOIN courses c ON ats.course_id = c.course_id
                     WHERE {$where}
                     ORDER BY ats.session_date DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get attendance by student error: " . $e->getMessage());
            return [];
        }
    }

    public function getStatistics($student_id, $course_id) {
        try {
            $query = "SELECT 
                        COUNT(*) as total_sessions,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                        ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate,
                        AVG(participation_score) as avg_participation
                     FROM " . $this->table . " ar
                     JOIN attendance_sessions ats ON ar.session_id = ats.session_id
                     WHERE ar.student_id = :student_id AND ats.course_id = :course_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get attendance statistics error: " . $e->getMessage());
            return false;
        }
    }

    public function getSummary($course_id = null, $group_id = null, $date_from = null, $date_to = null, $professor_id = null) {
        try {
            $params = [];
            $studentFilters = [];
            $statsFilters = [];

            if ($course_id !== null) {
                $params[':course_id'] = $course_id;
                $params[':course_id_sub'] = $course_id;
                $studentFilters[] = "cg.course_id = :course_id";
                $statsFilters[] = "ats.course_id = :course_id_sub";
            }

            if ($professor_id !== null && $course_id === null) {
                $params[':professor_id'] = $professor_id;
                $params[':professor_id_sub'] = $professor_id;
                $studentFilters[] = "c.professor_id = :professor_id";
                $statsFilters[] = "ats.professor_id = :professor_id_sub";
            }

            if ($group_id !== null) {
                $params[':group_id'] = $group_id;
                $params[':group_id_sub'] = $group_id;
                $studentFilters[] = "cg.group_id = :group_id";
                $statsFilters[] = "ats.group_id = :group_id_sub";
            }

            if ($date_from !== null) {
                $statsFilters[] = "ats.session_date >= :date_from";
                $params[':date_from'] = $date_from;
            }

            if ($date_to !== null) {
                $statsFilters[] = "ats.session_date <= :date_to";
                $params[':date_to'] = $date_to;
            }

            $studentWhere = count($studentFilters) > 0 ? 'WHERE ' . implode(' AND ', $studentFilters) : '';
            $statsWhere = count($statsFilters) > 0 ? 'WHERE ' . implode(' AND ', $statsFilters) : '';

            $query = "SELECT 
                    s.student_id,
                    u.full_name,
                    s.matricule,
                    g.group_name,
                    c.course_name,
                    COALESCE(stats.total_sessions, 0) as total_sessions,
                    COALESCE(stats.present_count, 0) as present_count,
                    COALESCE(stats.absent_count, 0) as absent_count,
                    COALESCE(stats.late_count, 0) as late_count,
                    COALESCE(stats.excused_count, 0) as excused_count,
                    CASE 
                        WHEN COALESCE(stats.total_sessions, 0) = 0 THEN 0
                        ELSE ROUND((COALESCE(stats.present_count, 0) / stats.total_sessions) * 100, 2)
                    END as attendance_rate
                FROM course_groups cg
                JOIN courses c ON cg.course_id = c.course_id
                JOIN `groups` g ON cg.group_id = g.group_id
                JOIN students s ON s.group_id = g.group_id
                JOIN users u ON s.student_id = u.user_id
                LEFT JOIN (
                    SELECT 
                        ar.student_id,
                        COUNT(DISTINCT ar.session_id) as total_sessions,
                        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN ar.status = 'excused' THEN 1 ELSE 0 END) as excused_count
                    FROM attendance_records ar
                    JOIN attendance_sessions ats ON ar.session_id = ats.session_id
                    {$statsWhere}
                    GROUP BY ar.student_id
                ) stats ON stats.student_id = s.student_id
                {$studentWhere}
                ORDER BY u.full_name";

            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get attendance summary error: " . $e->getMessage());
            return [];
        }
    }
}
?>
