<?php
session_start(); // Start the session
require_once '../includes/config.php'; // Database connection
require_once '../classes/User.php'; // User class

$error = ''; // Initialize error message
$success = ''; // Initialize success message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Attempt to register the user
        $user = new User($pdo);
        $registrationResult = $user->register($username, $email, $password);

        if ($registrationResult === true) {
            $success = "Registration successful! Please <a href='login.php'>login</a>.";
        } else {
            $error = $registrationResult; // Error message from the User class
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Expense Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Link to global CSS -->
</head>
<body>
<div class="container">
    <div class="form-container">
        <h2>Register</h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p> <!-- Display error messages -->
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="success"><?= $success ?></p> <!-- Display success message -->
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>

            <!-- Button Container -->
            <div class="button-container">
                <button type="submit">Register</button>
            </div>
        </form>

        <!-- Centered Text with Link -->
        <div class="button-container">
            <p>Already have an account? 
                <a href="login.php" class="button">Login here</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>