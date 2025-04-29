<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header
require_once '../classes/Budget.php'; // Budget class

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$budget = new Budget($pdo);

// Check for budget alerts
$alerts = $budget->checkBudgetAlerts($_SESSION['user_id']);
foreach ($alerts as $alert) {
    $message = "You have exceeded your budget for {$alert['category']}. Spent: \${$alert['spent']}, Limit: \${$alert['limit_amount']}";
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
        <ul>
            <?php foreach ($notifications as $notification): ?>
                <li class="<?= strpos($notification['message'], 'exceeded') ? 'alert' : 'success' ?>">
                    <p><?= $notification['message'] ?></p>
                    <span><?= $notification['created_at'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>