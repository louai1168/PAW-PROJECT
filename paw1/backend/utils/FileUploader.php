<?php
require_once __DIR__ . '/../config/constants.php';

class FileUploader {
    private $upload_dir;
    private $allowed_types;
    private $max_size;

    public function __construct($upload_dir = UPLOAD_DIR, $allowed_types = ALLOWED_FILE_TYPES, $max_size = MAX_FILE_SIZE) {
        $this->upload_dir = $upload_dir;
        $this->allowed_types = $allowed_types;
        $this->max_size = $max_size;
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    public function upload($file, $custom_name = null) {
        try {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                throw new Exception("No file was uploaded");
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload error: " . $this->getUploadErrorMessage($file['error']));
            }

            if ($file['size'] > $this->max_size) {
                throw new Exception("File size exceeds maximum allowed size");
            }

            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($file_ext, $this->allowed_types)) {
                throw new Exception("File type not allowed. Allowed types: " . implode(', ', $this->allowed_types));
            }

            if ($custom_name) {
                $filename = $custom_name . '.' . $file_ext;
            } else {
                $filename = uniqid() . '_' . time() . '.' . $file_ext;
            }

            $destination = $this->upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return $filename;
            } else {
                throw new Exception("Failed to move uploaded file");
            }
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($filename) {
        $filepath = $this->upload_dir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    public function getFilePath($filename) {
        return $this->upload_dir . $filename;
    }

    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "File size exceeds maximum allowed size";
            case UPLOAD_ERR_PARTIAL:
                return "File was only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "A PHP extension stopped the file upload";
            default:
                return "Unknown upload error";
        }
    }

    public function validateFile($file) {
        $errors = [];

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = "No file was uploaded";
            return $errors;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
        }

        if ($file['size'] > $this->max_size) {
            $errors[] = "File size exceeds maximum allowed size (" . ($this->max_size / 1048576) . "MB)";
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $this->allowed_types)) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', $this->allowed_types);
        }

        return $errors;
    }
}
?>
