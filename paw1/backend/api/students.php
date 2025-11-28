<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
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
$user_model = new User($db);
$student = new Student($db);

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'list':

            $search = $_GET['search'] ?? '';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $students = $student->getAll($search, $page, $per_page);
            $total = $student->count($search);

            echo json_encode([
                'success' => true,
                'data' => $students,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'pages' => ceil($total / $per_page)
                ]
            ]);
            break;

        case 'get':
            if (!isset($_GET['student_id'])) {
                throw new Exception("Student ID is required");
            }

            $student_data = $student->getById($_GET['student_id']);

            if ($student_data) {
                echo json_encode([
                    'success' => true,
                    'data' => $student_data
                ]);
            } else {
                throw new Exception("Student not found");
            }
            break;

        case 'create':
            if ($_SESSION['role'] !== 'administrator') {
                throw new Exception("Unauthorized");
            }

            $required = ['username', 'password', 'email', 'full_name', 'matricule', 'group_id', 'enrollment_year', 'level'];
            $missing = Validator::validateRequired($_POST, $required);
            
            if (!empty($missing)) {
                throw new Exception("Missing required fields: " . implode(', ', $missing));
            }

            if (!Validator::validateEmail($_POST['email'])) {
                throw new Exception("Invalid email address");
            }

            if (!Validator::validateUsername($_POST['username'])) {
                throw new Exception("Invalid username. Must be 3-50 alphanumeric characters");
            }

            if (!Validator::validatePassword($_POST['password'])) {
                throw new Exception("Password must be at least 6 characters");
            }

            if (!Validator::validateMatricule($_POST['matricule'])) {
                throw new Exception("Invalid matricule. Must be 12 digits");
            }

            $db->beginTransaction();

            try {
                $user_id = $user_model->create(
                    Validator::sanitize($_POST['username']),
                    $_POST['password'],
                    Validator::sanitize($_POST['email']),
                    Validator::sanitize($_POST['full_name']),
                    'student'
                );

                if (!$user_id) {
                    throw new Exception("Failed to create user account");
                }

                $result = $student->create(
                    $user_id,
                    Validator::sanitize($_POST['matricule']),
                    $_POST['group_id'],
                    $_POST['enrollment_year'],
                    Validator::sanitize($_POST['level'])
                );

                if (!$result) {
                    throw new Exception("Failed to create student record");
                }

                $db->commit();

                Logger::logActivity($_SESSION['user_id'], 'CREATE_STUDENT', "Student ID: {$user_id}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Student created successfully',
                    'student_id' => $user_id
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'update':
            if ($_SESSION['role'] !== 'administrator') {
                throw new Exception("Unauthorized");
            }

            if (!isset($_POST['student_id'])) {
                throw new Exception("Student ID is required");
            }

            $student_data = [];
            $user_data = [];

            if (isset($_POST['matricule'])) {
                $student_data['matricule'] = Validator::sanitize($_POST['matricule']);
            }
            if (isset($_POST['group_id'])) {
                $student_data['group_id'] = $_POST['group_id'];
            }
            if (isset($_POST['level'])) {
                $student_data['level'] = Validator::sanitize($_POST['level']);
            }
            if (isset($_POST['email'])) {
                $user_data['email'] = Validator::sanitize($_POST['email']);
            }
            if (isset($_POST['full_name'])) {
                $user_data['full_name'] = Validator::sanitize($_POST['full_name']);
            }

            $success = true;

            if (!empty($student_data)) {
                $success = $student->update($_POST['student_id'], $student_data) && $success;
            }

            if (!empty($user_data)) {
                $success = $user_model->update($_POST['student_id'], $user_data) && $success;
            }

            if ($success) {
                Logger::logActivity($_SESSION['user_id'], 'UPDATE_STUDENT', "Student ID: {$_POST['student_id']}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Student updated successfully'
                ]);
            } else {
                throw new Exception("Failed to update student");
            }
            break;

        case 'delete':
            if ($_SESSION['role'] !== 'administrator') {
                throw new Exception("Unauthorized");
            }

            if (!isset($_POST['student_id']) && !isset($_GET['student_id'])) {
                throw new Exception("Student ID is required");
            }

            $student_id = $_POST['student_id'] ?? $_GET['student_id'];

            $result = $student->delete($student_id);

            if ($result) {
                Logger::logActivity($_SESSION['user_id'], 'DELETE_STUDENT', "Student ID: {$student_id}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Student deleted successfully'
                ]);
            } else {
                throw new Exception("Failed to delete student");
            }
            break;

        case 'import':
            if ($_SESSION['role'] !== 'administrator') {
                throw new Exception("Unauthorized");
            }

            throw new Exception("Excel import feature requires PhpSpreadsheet library");
            break;

        case 'export':
            if ($_SESSION['role'] !== 'administrator') {
                throw new Exception("Unauthorized");
            }

            $group_id = $_GET['group_id'] ?? null;
            
            if ($group_id) {
                $students = $student->getByGroup($group_id);
            } else {
                $students = $student->getAll('', 1, 10000);
            }

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="students_export.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Matricule', 'Full Name', 'Email', 'Level', 'Group']);
            
            foreach ($students as $s) {
                fputcsv($output, [
                    $s['matricule'],
                    $s['full_name'],
                    $s['email'],
                    $s['level'],
                    $s['group_name'] ?? ''
                ]);
            }
            
            fclose($output);
            exit;

        case 'get_groups':
            $query = "SELECT group_id, group_name, level, department FROM `groups` ORDER BY level, group_name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $groups
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
