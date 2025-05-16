<?php
require_once 'helpers.php'; // Include the helpers file
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css ">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js "></script>
</head>
<body>
    <!-- Display Flash Messages -->
    <div class="flash-messages">
        <?php displayFlashMessage(); ?>
    </div>

    <header class="navbar">
        <div class="logo">
            <img src="../assets/images/logo.png" alt="Expense Manager Logo">
            <h1>Expense Management System</h1>
        </div>

        <nav>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="transactions.php" class="<?= basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : '' ?>"><i class="fas fa-list"></i> Transactions</a></li>
                    <li><a href="budget.php" class="<?= basename($_SERVER['PHP_SELF']) === 'budget.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Budget</a></li>
                    <li><a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i> Notifications</a></li>
                    <li><a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Dark Mode Toggle Switch -->
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

        // Auto-Hide Flash Messages
        document.addEventListener('DOMContentLoaded', () => {
            const flashMessages = document.querySelectorAll('.notification');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0'; // Fade out the message
                    setTimeout(() => message.remove(), 300); // Remove after fade-out
                }, 3000); // Hide after 3 seconds
            });
        });
    </script>
</body>
</html>