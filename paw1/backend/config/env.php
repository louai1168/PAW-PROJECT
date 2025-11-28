<?php

class Env {
    private static $loaded = false;
    
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = dirname(__DIR__, 2) . '/.env';
        }
        
        if (!file_exists($path)) {
            $altPaths = [
                dirname(__DIR__) . '/.env',
                dirname(__DIR__, 2) . '/.env',
                $_SERVER['DOCUMENT_ROOT'] . '/paw1/.env',
                $_SERVER['DOCUMENT_ROOT'] . '/../.env',
            ];
            
            foreach ($altPaths as $altPath) {
                if (file_exists($altPath)) {
                    $path = $altPath;
                    break;
                }
            }
        }
        
        if (!file_exists($path)) {
            error_log("Warning: .env file not found at {$path}. Current directory: " . getcwd());
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        self::load();
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
