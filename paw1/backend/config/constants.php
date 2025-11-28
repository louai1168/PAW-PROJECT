<?php
require_once __DIR__ . '/env.php';
Env::load();

define('DB_HOST', Env::get('DB_HOST', 'localhost'));
define('DB_NAME', Env::get('DB_NAME', 'attendance_system'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Student Attendance System');
define('APP_URL', Env::get('APP_URL', 'http://localhost/paw'));
define('APP_ENV', Env::get('APP_ENV', 'development'));
define('APP_DEBUG', Env::get('APP_DEBUG', 'true') === 'true');

define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/justifications/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);

define('SESSION_TIMEOUT', 3600); // 1 hour

date_default_timezone_set('Africa/Algiers');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
