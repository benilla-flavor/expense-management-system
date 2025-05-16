<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header
require_once '../includes/helpers.php'; // Helper functions (e.g., formatCurrency)
require_once '../classes/Budget.php'; // Budget class

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch the user's preferred currency
$stmt = $pdo->prepare("SELECT currency FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$preferredCurrency = $user['currency'] ?? 'USD';

$budget = new Budget($pdo);

// Handle "Mark as Read" action
if (isset($_GET['mark_as_read'])) {
    $notificationId = $_GET['mark_as_read'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $notificationId, 'user_id' => $_SESSION['user_id']]);
    header('Location: notifications.php');
    exit;
}

// Handle "Clear All" action
if (isset($_GET['clear_all'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    header('Location: notifications.php');
    exit;
}

// Check for budget alerts and create notifications
$alerts = $budget->checkBudgetAlerts($_SESSION['user_id']);
foreach ($alerts as $alert) {
    // Ensure the key 'category_name' exists in the $alert array
    if (!isset($alert['category_name'])) {
        error_log("Missing 'category_name' in alert array: " . print_r($alert, true));
        continue; // Skip this alert if 'category_name' is missing
    }

    $message = "You have exceeded your budget for {$alert['category_name']}. Spent: " .
               formatCurrency($alert['spent'], $preferredCurrency) .
               ", Limit: " . formatCurrency($alert['limit_amount'], $preferredCurrency);
    $budget->addNotification($_SESSION['user_id'], $message);
}

// Fetch all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="notifications">
        <h2>Notifications</h2>

        <!-- Buttons for Actions -->
        <div class="button-container">
            <a href="?clear_all=1" class="button" onclick="return confirm('Are you sure you want to clear all notifications?')">Clear All</a>
        </div>

        <!-- Notifications List -->
        <ul>
            <?php if (empty($notifications)): ?>
                <li><p>No notifications available.</p></li>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <li class="<?= strpos($notification['message'], 'exceeded') ? 'alert' : 'success' ?> <?= $notification['is_read'] ? 'read' : '' ?>">
                        <div class="notification-content">
                            <p><?= htmlspecialchars($notification['message']) ?></p>
                            <span><?= $notification['created_at'] ?></span>
                        </div>
                        <div class="notification-actions">
                            <?php if (!$notification['is_read']): ?>
                                <a href="?mark_as_read=<?= $notification['id'] ?>" class="button small">Mark as Read</a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>