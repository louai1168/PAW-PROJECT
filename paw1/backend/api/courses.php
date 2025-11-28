<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../utils/Validator.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$course = new Course($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['professor_id'])) {
            $courses = $course->getByProfessor($_GET['professor_id']);
            
            echo json_encode([
                'success' => true,
                'data' => $courses
            ]);
        } elseif (isset($_GET['student_id'])) {
            $courses = $course->getByStudent($_GET['student_id']);
            
            echo json_encode([
                'success' => true,
                'data' => $courses
            ]);
        } elseif (isset($_GET['course_id'])) {
            $course_data = $course->getById($_GET['course_id']);
            
            if ($course_data) {
                $groups = $course->getGroups($_GET['course_id']);
                $course_data['groups'] = $groups;
                
                echo json_encode([
                    'success' => true,
                    'data' => $course_data
                ]);
            } else {
                throw new Exception("Course not found");
            }
        } else {
            $courses = $course->getAll();
            
            echo json_encode([
                'success' => true,
                'data' => $courses
            ]);
        }
    } elseif ($method === 'POST') {
        if ($_SESSION['role'] !== 'administrator') {
            throw new Exception("Unauthorized");
        }

        $required = ['course_code', 'course_name', 'professor_id', 'semester', 'year'];
        $missing = Validator::validateRequired($_POST, $required);
        
        if (!empty($missing)) {
            throw new Exception("Missing required fields: " . implode(', ', $missing));
        }

        $course_id = $course->create(
            Validator::sanitize($_POST['course_code']),
            Validator::sanitize($_POST['course_name']),
            $_POST['professor_id'],
            $_POST['semester'],
            $_POST['year'],
            $_POST['credits'] ?? 1
        );

        if ($course_id) {
            echo json_encode([
                'success' => true,
                'message' => 'Course created successfully',
                'course_id' => $course_id
            ]);
        } else {
            throw new Exception("Failed to create course");
        }
    } else {
        throw new Exception("Invalid request method");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
