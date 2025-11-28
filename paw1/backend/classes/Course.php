<?php
require_once __DIR__ . '/../config/database.php';

class Course {
    private $conn;
    private $table = 'courses';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($course_code, $course_name, $professor_id, $semester, $year, $credits = 1) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (course_code, course_name, professor_id, semester, year, credits) 
                     VALUES (:course_code, :course_name, :professor_id, :semester, :year, :credits)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':course_code', $course_code);
            $stmt->bindParam(':course_name', $course_name);
            $stmt->bindParam(':professor_id', $professor_id);
            $stmt->bindParam(':semester', $semester);
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':credits', $credits);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Course creation error: " . $e->getMessage());
            throw new Exception("Failed to create course");
        }
    }

    public function getByProfessor($professor_id) {
        try {
            $query = "SELECT DISTINCT c.*, 
                            (SELECT COUNT(DISTINCT cg.group_id) FROM course_groups cg WHERE cg.course_id = c.course_id) as group_count,
                            (SELECT COUNT(*) FROM attendance_sessions ats WHERE ats.course_id = c.course_id) as session_count
                     FROM " . $this->table . " c
                     WHERE c.professor_id = :professor_id
                     ORDER BY c.course_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':professor_id', $professor_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get courses by professor error: " . $e->getMessage());
            return [];
        }
    }

    public function getByStudent($student_id) {
        try {
            $query = "SELECT DISTINCT c.*, u.full_name as professor_name,
                            g.group_name, g.group_id,
                            COALESCE((SELECT COUNT(*) FROM attendance_sessions ats 
                                      WHERE ats.course_id = c.course_id 
                                      AND ats.group_id = s.group_id), 0) as total_sessions,
                            COALESCE((SELECT COUNT(*) FROM attendance_records ar
                                      JOIN attendance_sessions ats2 ON ar.session_id = ats2.session_id
                                      WHERE ar.student_id = s.student_id
                                      AND ats2.course_id = c.course_id
                                      AND ats2.group_id = s.group_id
                                      AND ar.status = 'present'), 0) as present_count,
                            COALESCE((SELECT COUNT(*) FROM attendance_records ar
                                      JOIN attendance_sessions ats2 ON ar.session_id = ats2.session_id
                                      WHERE ar.student_id = s.student_id
                                      AND ats2.course_id = c.course_id
                                      AND ats2.group_id = s.group_id
                                      AND ar.status = 'absent'), 0) as absent_count,
                            COALESCE((SELECT COUNT(*) FROM attendance_records ar
                                      JOIN attendance_sessions ats2 ON ar.session_id = ats2.session_id
                                      WHERE ar.student_id = s.student_id
                                      AND ats2.course_id = c.course_id
                                      AND ats2.group_id = s.group_id
                                      AND ar.status = 'late'), 0) as late_count,
                            COALESCE((SELECT COUNT(*) FROM attendance_records ar
                                      JOIN attendance_sessions ats2 ON ar.session_id = ats2.session_id
                                      WHERE ar.student_id = s.student_id
                                      AND ats2.course_id = c.course_id
                                      AND ats2.group_id = s.group_id
                                      AND ar.status = 'excused'), 0) as excused_count
                     FROM " . $this->table . " c
                     JOIN course_groups cg ON c.course_id = cg.course_id
                     JOIN students s ON cg.group_id = s.group_id
                     JOIN users u ON c.professor_id = u.user_id
                     JOIN `groups` g ON cg.group_id = g.group_id
                     WHERE s.student_id = :student_id
                     ORDER BY c.course_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($courses as &$course) {
                $total_sessions = (int)($course['total_sessions'] ?? 0);
                $present = (int)($course['present_count'] ?? 0);
                $course['attendance_percentage'] = $total_sessions > 0
                    ? round(($present / $total_sessions) * 100)
                    : 0;
            }

            return $courses;
        } catch (PDOException $e) {
            error_log("Get courses by student error: " . $e->getMessage());
            return [];
        }
    }

    public function getById($course_id) {
        try {
            $query = "SELECT c.*, u.full_name as professor_name
                     FROM " . $this->table . " c
                     LEFT JOIN users u ON c.professor_id = u.user_id
                     WHERE c.course_id = :course_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get course error: " . $e->getMessage());
            return false;
        }
    }

    public function getGroups($course_id) {
        try {
            $query = "SELECT g.*, cg.schedule_day, cg.schedule_time
                     FROM `groups` g
                     JOIN course_groups cg ON g.group_id = cg.group_id
                     WHERE cg.course_id = :course_id
                     ORDER BY g.group_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get course groups error: " . $e->getMessage());
            return [];
        }
    }

    public function assignGroup($course_id, $group_id, $schedule_day = null, $schedule_time = null) {
        try {
            $query = "INSERT INTO course_groups (course_id, group_id, schedule_day, schedule_time) 
                     VALUES (:course_id, :group_id, :schedule_day, :schedule_time)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->bindParam(':schedule_day', $schedule_day);
            $stmt->bindParam(':schedule_time', $schedule_time);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Assign group to course error: " . $e->getMessage());
            return false;
        }
    }

    public function getAll() {
        try {
            $query = "SELECT c.*, u.full_name as professor_name
                     FROM " . $this->table . " c
                     LEFT JOIN users u ON c.professor_id = u.user_id
                     ORDER BY c.course_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all courses error: " . $e->getMessage());
            return [];
        }
    }
}
?>
