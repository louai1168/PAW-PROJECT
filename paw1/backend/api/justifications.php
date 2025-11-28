<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Justification.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/FileUploader.php';
require_once __DIR__ . '/../utils/Logger.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$justification = new Justification($db);
$fileUploader = new FileUploader();

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'submit':
            if ($_SESSION['role'] !== 'student') {
                throw new Exception("Only students can submit justifications");
            }

            $required = ['session_id', 'reason'];
            $missing = Validator::validateRequired($_POST, $required);
            
            if (!empty($missing)) {
                throw new Exception("Missing required fields: " . implode(', ', $missing));
            }

            $file_path = null;

            if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors = $fileUploader->validateFile($_FILES['file']);
                
                if (!empty($errors)) {
                    throw new Exception("File validation failed: " . implode(', ', $errors));
                }

                try {
                    $filename = $fileUploader->upload($_FILES['file']);
                    $file_path = 'uploads/justifications/' . $filename;
                } catch (Exception $e) {
                    throw new Exception("File upload failed: " . $e->getMessage());
                }
            }

            $justification_id = $justification->submit(
                $_SESSION['user_id'],
                $_POST['session_id'],
                Validator::sanitize($_POST['reason']),
                $file_path
            );

            if ($justification_id) {
                Logger::logActivity($_SESSION['user_id'], 'SUBMIT_JUSTIFICATION', "Justification ID: {$justification_id}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Justification submitted successfully',
                    'justification_id' => $justification_id
                ]);
            } else {
                throw new Exception("Failed to submit justification");
            }
            break;

        case 'list':
            if (isset($_GET['student_id'])) {
                $justifications = $justification->getByStudent($_GET['student_id']);
            } elseif (isset($_GET['status']) && $_GET['status'] === 'pending') {
                $professor_id = ($_SESSION['role'] === 'professor') ? $_SESSION['user_id'] : null;
                $justifications = $justification->getPending($professor_id);
            } else {
                throw new Exception("Student ID or status parameter is required");
            }

            echo json_encode([
                'success' => true,
                'data' => $justifications
            ]);
            break;

        case 'get':
            if (!isset($_GET['justification_id'])) {
                throw new Exception("Justification ID is required");
            }

            $data = $justification->getById($_GET['justification_id']);

            if ($data) {
                echo json_encode([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                throw new Exception("Justification not found");
            }
            break;

        case 'review':
            if ($_SESSION['role'] !== 'professor' && $_SESSION['role'] !== 'administrator') {
                throw new Exception("Only professors can review justifications");
            }

            $required = ['justification_id', 'decision'];
            $missing = Validator::validateRequired($_POST, $required);
            
            if (!empty($missing)) {
                throw new Exception("Missing required fields: " . implode(', ', $missing));
            }

            $decision = $_POST['decision'];
            $reviewer_notes = Validator::sanitize($_POST['reviewer_notes'] ?? '');

            if ($decision === 'approve') {
                $result = $justification->approve($_POST['justification_id'], $_SESSION['user_id'], $reviewer_notes);
                $message = 'Justification approved';
            } elseif ($decision === 'reject') {
                $result = $justification->reject($_POST['justification_id'], $_SESSION['user_id'], $reviewer_notes);
                $message = 'Justification rejected';
            } else {
                throw new Exception("Invalid decision. Must be 'approve' or 'reject'");
            }

            if ($result) {
                Logger::logActivity($_SESSION['user_id'], 'REVIEW_JUSTIFICATION', "Justification ID: {$_POST['justification_id']}, Decision: {$decision}");

                echo json_encode([
                    'success' => true,
                    'message' => $message
                ]);
            } else {
                throw new Exception("Failed to process justification");
            }
            break;

        case 'delete':
            if (!isset($_POST['justification_id']) && !isset($_GET['justification_id'])) {
                throw new Exception("Justification ID is required");
            }

            $justification_id = $_POST['justification_id'] ?? $_GET['justification_id'];

            $just_data = $justification->getById($justification_id);
            
            if (!$just_data) {
                throw new Exception("Justification not found");
            }

            if ($_SESSION['role'] === 'student' && $just_data['student_id'] != $_SESSION['user_id']) {
                throw new Exception("Unauthorized");
            }

            if ($_SESSION['role'] === 'student' && $just_data['status'] !== 'pending') {
                throw new Exception("Cannot delete reviewed justification");
            }

            $result = $justification->delete($justification_id);

            if ($result) {
                Logger::logActivity($_SESSION['user_id'], 'DELETE_JUSTIFICATION', "Justification ID: {$justification_id}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Justification deleted successfully'
                ]);
            } else {
                throw new Exception("Failed to delete justification");
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
