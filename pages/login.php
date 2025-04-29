<?php
session_start(); // Start the session
require_once '../includes/config.php'; // Database connection
require_once '../classes/User.php'; // User class

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
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Link to global CSS -->
</head>
<body>
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

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php" class="button">Register here</a></p>
    </div>
</div>
</body>
</html>