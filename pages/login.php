<?php
session_start(); // Start the session
require_once '../includes/config.php'; // Database connection
require_once '../classes/User.php'; // User class

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect logged-in users to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = ''; // Initialize error message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Attempt to log in
        $user = new User($pdo);
        $loggedInUser = $user->login($email, $password);

        if ($loggedInUser) {
            $_SESSION['user_id'] = $loggedInUser['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Expense Management System</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Link to global CSS -->
</head>
<body>

<header class="navbar">
    <div class="logo">
        <img src="../assets/images/logo.png" alt="Expense Manager Logo">
        <h1>Expense Management System</h1>
    </div>
    <div class="theme-toggle">
        <label for="dark-mode-toggle">Dark Mode</label>
        <label class="switch">
            <input type="checkbox" id="dark-mode-toggle">
            <span class="slider"></span>
        </label>
    </div>
</header>

<script>
    // Dark Mode Toggle Script
    document.addEventListener('DOMContentLoaded', () => {
        const toggleSwitch = document.getElementById('dark-mode-toggle');

        if (toggleSwitch) {
            // Load saved theme preference
            const savedTheme = localStorage.getItem('darkMode');
            if (savedTheme === 'true') {
                document.body.classList.add('dark-mode');
                toggleSwitch.checked = true;
            }

            // Toggle dark mode on switch change
            toggleSwitch.addEventListener('change', () => {
                document.body.classList.toggle('dark-mode');
                const isDarkMode = document.body.classList.contains('dark-mode');
                localStorage.setItem('darkMode', isDarkMode);
            });
        }
    });
</script>

<div class="container">
    <div class="form-container">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p> <!-- Display error messages -->
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>

            <!-- Button Container -->
            <div class="button-container">
                <button type="submit">Login</button>
            </div>
        </form>

        <!-- Centered Text with Link -->
        <div class="button-container">
            <p>Don't have an account? 
                <a href="register.php" class="button">Register here</a>
            </p>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> Expense Management System. All rights reserved.</p>
    </div>
</footer>
</body>
</html>