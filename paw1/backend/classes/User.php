<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($username, $password, $email, $full_name, $role) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (username, password_hash, email, full_name, role) 
                     VALUES (:username, :password_hash, :email, :full_name, :role)";
            
            $stmt = $this->conn->prepare($query);
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            throw new Exception("Failed to create user");
        }
    }

    public function login($username, $password) {
        try {
            $query = "SELECT user_id, username, password_hash, email, full_name, role, is_active 
                     FROM " . $this->table . " 
                     WHERE username = :username AND is_active = 1 
                     LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password_hash'])) {
                    unset($user['password_hash']);
                    return $user;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            throw new Exception("Login failed");
        }
    }

    public function getById($user_id) {
        try {
            $query = "SELECT user_id, username, email, full_name, role, is_active, created_at 
                     FROM " . $this->table . " 
                     WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }

    public function getByRole($role) {
        try {
            $query = "SELECT user_id, username, email, full_name, is_active, created_at 
                     FROM " . $this->table . " 
                     WHERE role = :role AND is_active = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get users by role error: " . $e->getMessage());
            return [];
        }
    }

    public function update($user_id, $data) {
        try {
            $fields = [];
            $params = [':user_id' => $user_id];
            
            if (isset($data['email'])) {
                $fields[] = "email = :email";
                $params[':email'] = $data['email'];
            }
            if (isset($data['full_name'])) {
                $fields[] = "full_name = :full_name";
                $params[':full_name'] = $data['full_name'];
            }
            if (isset($data['password'])) {
                $fields[] = "password_hash = :password_hash";
                $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            throw new Exception("Failed to update user");
        }
    }

    public function delete($user_id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            throw new Exception("Failed to delete user");
        }
    }

    public function deactivate($user_id) {
        try {
            $query = "UPDATE " . $this->table . " SET is_active = 0 WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Deactivate user error: " . $e->getMessage());
            return false;
        }
    }
}
?>
