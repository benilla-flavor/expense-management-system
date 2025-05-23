<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Register a new user
    public function register($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        return $stmt->execute(['username' => $username, 'email' => $email, 'password' => $hashedPassword]);
    }

    // Authenticate a user
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debugging: Check if the user exists
        if (!$user) {
            error_log("User not found for email: $email");
            return false;
        }

        // Debugging: Check password verification
        if (!password_verify($password, $user['password'])) {
            error_log("Password verification failed for email: $email");
            return false;
        }

        // Debugging: Log successful login
        error_log("User logged in successfully: " . $user['username']);
        return $user;
    }
}
?>