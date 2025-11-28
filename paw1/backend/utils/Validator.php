<?php
class Validator {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }

    public static function validatePassword($password) {
        return strlen($password) >= 6;
    }

    public static function validateMatricule($matricule) {
        return preg_match('/^\d{12}$/', $matricule);
    }

    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
            return $data;
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function validateRequired($data, $required_fields) {
        $missing = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }

    public static function validateEnum($value, $allowed_values) {
        return in_array($value, $allowed_values);
    }

    public static function validateNumeric($value) {
        return is_numeric($value);
    }

    public static function validatePositiveInt($value) {
        return is_numeric($value) && (int)$value > 0;
    }
}
?>
