<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Attendance.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$attendance = new Attendance($db);

$action = $_GET['action'] ?? 'summary';

try {
    switch ($action) {
        case 'summary':
            $course_id = $_GET['course_id'] ?? null;
            $group_id = $_GET['group_id'] ?? null;
            $date_from = $_GET['date_from'] ?? null;
            $date_to = $_GET['date_to'] ?? null;
            $professor_id = $_SESSION['role'] === 'professor' ? $_SESSION['user_id'] : null;

            $summary = $attendance->getSummary($course_id, $group_id, $date_from, $date_to, $professor_id);

            echo json_encode([
                'success' => true,
                'data' => $summary
            ]);
            break;

        case 'dashboard_stats':
            if ($_SESSION['role'] !== 'administrator') {
                throw new Exception("Unauthorized");
            }

            $stats_query = "SELECT 
                (SELECT COUNT(*) FROM students s JOIN users u ON s.student_id = u.user_id WHERE u.is_active = 1) as total_students,
                (SELECT COUNT(*) FROM professors p JOIN users u ON p.professor_id = u.user_id WHERE u.is_active = 1) as total_professors,
                (SELECT COUNT(*) FROM courses) as total_courses,
                (SELECT COUNT(*) FROM attendance_sessions WHERE session_date = CURDATE()) as sessions_today,
                (SELECT COUNT(*) FROM justifications WHERE status = 'pending') as pending_justifications,
                (SELECT COUNT(*) FROM attendance_sessions WHERE status = 'open') as open_sessions,
                (SELECT COUNT(*) FROM attendance_sessions) as total_sessions";
            
            $stmt = $db->prepare($stats_query);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            $recent_sessions_query = "SELECT 
                ats.session_id, ats.session_date, ats.status, ats.session_type,
                c.course_name, g.group_name, u.full_name as professor_name
            FROM attendance_sessions ats
            JOIN courses c ON ats.course_id = c.course_id
            JOIN `groups` g ON ats.group_id = g.group_id
            JOIN users u ON ats.professor_id = u.user_id
            ORDER BY ats.session_date DESC, ats.created_at DESC
            LIMIT 5";
            $stmt_sessions = $db->query($recent_sessions_query);
            $recent_sessions = $stmt_sessions->fetchAll(PDO::FETCH_ASSOC);

            $recent_activity_query = "SELECT 
                al.action, al.timestamp as created_at, al.table_affected,
                u.full_name as user_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.timestamp DESC
            LIMIT 5";
            $stmt_activity = $db->query($recent_activity_query);
            $recent_activity = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);

            $courses_query = "SELECT 
                c.course_id, c.course_name,
                u.full_name as professor_name,
                (SELECT COUNT(DISTINCT s.student_id) 
                 FROM course_groups cg2 
                 JOIN students s ON s.group_id = cg2.group_id 
                 WHERE cg2.course_id = c.course_id) as student_count,
                COUNT(DISTINCT ats.session_id) as session_count,
                CASE 
                    WHEN COUNT(ar.record_id) = 0 THEN 0
                    ELSE ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.record_id)) * 100, 0)
                END as avg_attendance
            FROM courses c
            JOIN users u ON c.professor_id = u.user_id
            LEFT JOIN attendance_sessions ats ON c.course_id = ats.course_id
            LEFT JOIN attendance_records ar ON ats.session_id = ar.session_id
            GROUP BY c.course_id
            ORDER BY c.course_name";
            $stmt_courses = $db->query($courses_query);
            $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $stats,
                'recent_sessions' => $recent_sessions,
                'recent_activity' => $recent_activity,
                'courses' => $courses
            ]);
            break;

        case 'statistics':

            if ($_SESSION['role'] !== 'administrator') {
                throw new Exception("Unauthorized");
            }

            $period = isset($_GET['period']) ? intval($_GET['period']) : 30;
            $date_to = date('Y-m-d');
            $date_from = date('Y-m-d', strtotime("-{$period} days"));

            $trend_query = "SELECT 
                ats.session_date,
                COUNT(ar.record_id) as total_records,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN ar.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.record_id)) * 100, 2) as attendance_rate
            FROM attendance_sessions ats
            LEFT JOIN attendance_records ar ON ats.session_id = ar.session_id
            WHERE ats.session_date BETWEEN :date_from AND :date_to
            GROUP BY ats.session_date
            ORDER BY ats.session_date";
            
            $stmt_trend = $db->prepare($trend_query);
            $stmt_trend->bindParam(':date_from', $date_from);
            $stmt_trend->bindParam(':date_to', $date_to);
            $stmt_trend->execute();
            $attendance_trend = $stmt_trend->fetchAll(PDO::FETCH_ASSOC);

            $course_query = "SELECT 
                c.course_name,
                COUNT(DISTINCT ats.session_id) as session_count,
                COUNT(ar.record_id) as total_records,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                CASE 
                    WHEN COUNT(ar.record_id) = 0 THEN 0
                    ELSE ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.record_id)) * 100, 2)
                END as attendance_rate
            FROM courses c
            LEFT JOIN attendance_sessions ats ON c.course_id = ats.course_id AND ats.session_date BETWEEN :date_from AND :date_to
            LEFT JOIN attendance_records ar ON ats.session_id = ar.session_id
            GROUP BY c.course_id
            HAVING session_count > 0
            ORDER BY c.course_name";
            
            $stmt_course = $db->prepare($course_query);
            $stmt_course->bindParam(':date_from', $date_from);
            $stmt_course->bindParam(':date_to', $date_to);
            $stmt_course->execute();
            $course_stats = $stmt_course->fetchAll(PDO::FETCH_ASSOC);

            $top_students_query = "SELECT 
                u.full_name,
                s.matricule,
                g.group_name,
                COUNT(ar.record_id) as total_sessions,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
                ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.record_id)) * 100, 2) as attendance_rate
            FROM students s
            JOIN users u ON s.student_id = u.user_id
            JOIN `groups` g ON s.group_id = g.group_id
            JOIN attendance_records ar ON s.student_id = ar.student_id
            JOIN attendance_sessions ats ON ar.session_id = ats.session_id
            WHERE ats.session_date BETWEEN :date_from AND :date_to
            GROUP BY s.student_id
            HAVING total_sessions >= 2
            ORDER BY attendance_rate DESC, present_count DESC
            LIMIT 10";
            
            $stmt_top = $db->prepare($top_students_query);
            $stmt_top->bindParam(':date_from', $date_from);
            $stmt_top->bindParam(':date_to', $date_to);
            $stmt_top->execute();
            $top_students = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

            $low_attendance_query = "SELECT 
                u.full_name,
                s.matricule,
                g.group_name,
                COUNT(ar.record_id) as total_sessions,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.record_id)) * 100, 2) as attendance_rate
            FROM students s
            JOIN users u ON s.student_id = u.user_id
            JOIN `groups` g ON s.group_id = g.group_id
            JOIN attendance_records ar ON s.student_id = ar.student_id
            JOIN attendance_sessions ats ON ar.session_id = ats.session_id
            WHERE ats.session_date BETWEEN :date_from AND :date_to
            GROUP BY s.student_id
            HAVING attendance_rate < 75 AND total_sessions >= 2
            ORDER BY attendance_rate ASC
            LIMIT 10";
            
            $stmt_low = $db->prepare($low_attendance_query);
            $stmt_low->bindParam(':date_from', $date_from);
            $stmt_low->bindParam(':date_to', $date_to);
            $stmt_low->execute();
            $low_attendance = $stmt_low->fetchAll(PDO::FETCH_ASSOC);

            $active_students_query = "SELECT COUNT(DISTINCT ar.student_id) as count 
                FROM attendance_records ar 
                JOIN attendance_sessions ats ON ar.session_id = ats.session_id 
                WHERE ats.session_date BETWEEN :date_from AND :date_to";
            $stmt_active = $db->prepare($active_students_query);
            $stmt_active->bindParam(':date_from', $date_from);
            $stmt_active->bindParam(':date_to', $date_to);
            $stmt_active->execute();
            $active_students = $stmt_active->fetch(PDO::FETCH_ASSOC)['count'];

            echo json_encode([
                'success' => true,
                'attendance_trend' => $attendance_trend,
                'course_stats' => $course_stats,
                'top_students' => $top_students,
                'low_attendance' => $low_attendance,
                'active_students' => $active_students
            ]);
            break;

        case 'export':
            if (!isset($_GET['course_id'])) {
                throw new Exception("Course ID is required");
            }

            $course_id = $_GET['course_id'];
            $group_id = $_GET['group_id'] ?? null;
            $format = $_GET['format'] ?? 'csv';

            $summary = $attendance->getSummary($course_id, $group_id);

            if ($format === 'csv' || $format === 'excel') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="attendance_report.csv"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Student Name', 'Matricule', 'Total Sessions', 'Present', 'Absent', 'Late', 'Excused', 'Attendance Rate %']);
                
                foreach ($summary as $record) {
                    fputcsv($output, [
                        $record['full_name'],
                        $record['matricule'],
                        $record['total_sessions'] ?? 0,
                        $record['present_count'] ?? 0,
                        $record['absent_count'] ?? 0,
                        $record['late_count'] ?? 0,
                        $record['excused_count'] ?? 0,
                        $record['attendance_rate'] ?? 0
                    ]);
                }
                
                fclose($output);
                exit;
            } else {
                throw new Exception("Invalid format. Use 'csv' or 'excel'");
            }
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
