<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/Attendance.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Logger.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$session = new AttendanceSession($db);
$attendance = new Attendance($db);
$student = new Student($db);

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'create_session':
            if ($_SESSION['role'] !== 'professor') {
                throw new Exception("Only professors can create sessions");
            }

            $required = ['course_id', 'group_id', 'session_date'];
            $missing = Validator::validateRequired($_POST, $required);
            
            if (!empty($missing)) {
                throw new Exception("Missing required fields: " . implode(', ', $missing));
            }

            $session_id = $session->create(
                $_POST['course_id'],
                $_POST['group_id'],
                $_SESSION['user_id'],
                $_POST['session_date'],
                $_POST['session_type'] ?? 'lecture'
            );

            if ($session_id) {
                Logger::logActivity($_SESSION['user_id'], 'CREATE_SESSION', "Session ID: {$session_id}");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Session created successfully',
                    'session_id' => $session_id
                ]);
            } else {
                throw new Exception("Failed to create session");
            }
            break;

        case 'get_session':
            if (!isset($_GET['session_id'])) {
                throw new Exception("Session ID is required");
            }

            $session_data = $session->getById($_GET['session_id']);
            
            if (!$session_data) {
                throw new Exception("Session not found");
            }

            $students = $student->getByGroup($session_data['group_id']);
            
            $attendance_records = $attendance->getBySession($_GET['session_id']);

            $attendance_map = [];
            foreach ($attendance_records as $record) {
                $attendance_map[$record['student_id']] = $record;
            }
            
            foreach ($students as &$stud) {
                if (isset($attendance_map[$stud['student_id']])) {
                    $stud['attendance'] = $attendance_map[$stud['student_id']];
                } else {
                    $stud['attendance'] = null;
                }
            }
            
            $session_data['students'] = $students;
            
            echo json_encode([
                'success' => true,
                'data' => $session_data
            ]);
            break;

        case 'get_students_for_session':
            if (!isset($_GET['session_id'])) {
                throw new Exception("Session ID is required");
            }

            $session_data = $session->getById($_GET['session_id']);
            
            if (!$session_data) {
                throw new Exception("Session not found");
            }

            $students = $student->getByGroup($session_data['group_id']);
            
            $attendance_records = $attendance->getBySession($_GET['session_id']);
            
            $attendance_map = [];
            foreach ($attendance_records as $record) {
                $attendance_map[$record['student_id']] = [
                    'status' => $record['status'],
                    'marked_at' => $record['marked_at'],
                    'attendance_id' => $record['attendance_id'] ?? ($record['record_id'] ?? null)
                ];
            }
            
            foreach ($students as &$stud) {
                if (isset($attendance_map[$stud['student_id']])) {
                    $stud['status'] = $attendance_map[$stud['student_id']]['status'];
                    $stud['marked_at'] = $attendance_map[$stud['student_id']]['marked_at'];
                    $stud['attendance_id'] = $attendance_map[$stud['student_id']]['attendance_id'];
                } else {
                    $stud['status'] = null;
                    $stud['marked_at'] = null;
                    $stud['attendance_id'] = null;
                }
            }
            
            echo json_encode([
                'success' => true,
                'students' => $students
            ]);
            break;

        case 'mark':
            if ($_SESSION['role'] !== 'professor') {
                throw new Exception("Only professors can mark attendance");
            }

            $required = ['session_id', 'student_id', 'status'];
            $missing = Validator::validateRequired($_POST, $required);
            
            if (!empty($missing)) {
                throw new Exception("Missing required fields: " . implode(', ', $missing));
            }

            $valid_statuses = ['present', 'absent', 'late', 'excused'];
            if (!Validator::validateEnum($_POST['status'], $valid_statuses)) {
                throw new Exception("Invalid status. Must be one of: " . implode(', ', $valid_statuses));
            }

            $result = $attendance->mark(
                $_POST['session_id'],
                $_POST['student_id'],
                $_POST['status'],
                $_POST['participation_score'] ?? 0,
                $_POST['behavior_notes'] ?? ''
            );

            if ($result) {
                Logger::logActivity($_SESSION['user_id'], 'MARK_ATTENDANCE', "Session: {$_POST['session_id']}, Student: {$_POST['student_id']}");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Attendance marked successfully'
                ]);
            } else {
                throw new Exception("Failed to mark attendance");
            }
            break;

        case 'bulk_mark':
            if ($_SESSION['role'] !== 'professor') {
                throw new Exception("Only professors can mark attendance");
            }

            if (!isset($_POST['session_id']) || !isset($_POST['attendance_data'])) {
                throw new Exception("Session ID and attendance data are required");
            }

            $attendance_data = json_decode($_POST['attendance_data'], true);
            
            if (!is_array($attendance_data)) {
                throw new Exception("Invalid attendance data format");
            }

            $result = $attendance->bulkMark($_POST['session_id'], $attendance_data);

            if ($result) {
                Logger::logActivity($_SESSION['user_id'], 'BULK_MARK_ATTENDANCE', "Session: {$_POST['session_id']}");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Attendance marked for all students'
                ]);
            } else {
                throw new Exception("Failed to mark attendance");
            }
            break;

        case 'close_session':
            if ($_SESSION['role'] !== 'professor') {
                throw new Exception("Only professors can close sessions");
            }

            if (!isset($_POST['session_id'])) {
                throw new Exception("Session ID is required");
            }

            $result = $session->close($_POST['session_id']);

            if ($result) {
                Logger::logActivity($_SESSION['user_id'], 'CLOSE_SESSION', "Session ID: {$_POST['session_id']}");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Session closed successfully'
                ]);
            } else {
                throw new Exception("Failed to close session");
            }
            break;

        case 'get_sessions':
            if (isset($_GET['professor_id'])) {
                $status = $_GET['status'] ?? null;
                $sessions = $session->getByProfessor($_GET['professor_id'], $status);
            } elseif (isset($_GET['course_id'])) {
                $group_id = $_GET['group_id'] ?? null;
                $status = $_GET['status'] ?? null;
                $sessions = $session->getByCourse($_GET['course_id'], $group_id, $status);
            } else {
                throw new Exception("Professor ID or Course ID is required");
            }

            echo json_encode([
                'success' => true,
                'data' => $sessions
            ]);
            break;

        case 'get_student_attendance':
            if (!isset($_GET['student_id'])) {
                throw new Exception("Student ID is required");
            }

            $course_id = $_GET['course_id'] ?? null;
            $records = $attendance->getByStudent($_GET['student_id'], $course_id);

            echo json_encode([
                'success' => true,
                'data' => $records
            ]);
            break;

        case 'get_statistics':
            if (!isset($_GET['student_id']) || !isset($_GET['course_id'])) {
                throw new Exception("Student ID and Course ID are required");
            }

            $stats = $attendance->getStatistics($_GET['student_id'], $_GET['course_id']);

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        case 'my_absent_sessions':
            if ($_SESSION['role'] !== 'student') {
                throw new Exception("Only students can view their absent sessions");
            }

            $absent_sessions = $session->getAbsentSessionsForStudent($_SESSION['user_id']);

            echo json_encode([
                'success' => true,
                'data' => $absent_sessions
            ]);
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
