<?php
class Logger {
    private static $log_file = __DIR__ . '/../logs/app.log';

    public static function logError($message, $context = []) {
        self::log('ERROR', $message, $context);
    }

    public static function logInfo($message, $context = []) {
        self::log('INFO', $message, $context);
    }

    public static function logWarning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }

    private static function log($level, $message, $context = []) {
        $log_dir = dirname(self::$log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $log_entry = "[{$timestamp}] [{$level}] {$message}{$context_str}\n";

        error_log($log_entry, 3, self::$log_file);
    }

    public static function logActivity($user_id, $action, $details = '') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $message = "User {$user_id} performed action: {$action}";
        $context = [
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip' => $ip
        ];
        
        self::logInfo($message, $context);
    }
}
?>
