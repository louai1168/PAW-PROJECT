<?php
header('Content-Type: application/json');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Logger.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            $username = Validator::sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                throw new Exception("Username and password are required");
            }

            $user_data = $user->login($username, $password);

            if ($user_data) {
                $_SESSION['user_id'] = $user_data['user_id'];
                $_SESSION['username'] = $user_data['username'];
                $_SESSION['full_name'] = $user_data['full_name'];
                $_SESSION['email'] = $user_data['email'];
                $_SESSION['role'] = $user_data['role'];
                $_SESSION['last_activity'] = time();

                Logger::logActivity($user_data['user_id'], 'LOGIN');

                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user_id' => $user_data['user_id'],
                        'username' => $user_data['username'],
                        'full_name' => $user_data['full_name'],
                        'email' => $user_data['email'],
                        'role' => $user_data['role']
                    ]
                ]);
            } else {
                throw new Exception("Invalid username or password");
            }
            break;

        case 'logout':
            $user_id = $_SESSION['user_id'] ?? null;
            
            if ($user_id) {
                Logger::logActivity($user_id, 'LOGOUT');
            }

            session_destroy();
            
            echo json_encode([
                'success' => true,
                'message' => 'Logout successful'
            ]);
            break;

        case 'check':
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Not authenticated");
            }

            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
                session_destroy();
                throw new Exception("Session expired");
            }

            $_SESSION['last_activity'] = time();

            echo json_encode([
                'success' => true,
                'data' => [
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'full_name' => $_SESSION['full_name'],
                    'email' => $_SESSION['email'],
                    'role' => $_SESSION['role']
                ]
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
