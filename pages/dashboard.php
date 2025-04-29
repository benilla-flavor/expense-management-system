<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../classes/Budget.php';
require_once '../classes/Transaction.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$transaction = new Transaction($pdo);
$budget = new Budget($pdo);
$transactions = $transaction->getTransactions($_SESSION['user_id']);
$budgetProgress = $budget->getBudgetProgress($_SESSION['user_id']);
$recurringTransactions = $transaction->generateFutureRecurringTransactions($_SESSION['user_id'], date('Y-m-d', strtotime('+1 year')));
?>

<h2>Welcome Back!</h2>

<h3>Budget Progress</h3>
<div id="budget-progress">
    <?php foreach ($budgetProgress as $progress): ?>
        <div>
            <strong><?= $progress['category'] ?>:</strong>
            <div class="progress-bar">
                <div class="progress" style="width: <?= ($progress['spent'] / $progress['limit_amount']) * 100 ?>%;"></div>
            </div>
            <span>Spent: $<?= number_format($progress['spent'], 2) ?> / Limit: $<?= number_format($progress['limit_amount'], 2) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<h3>Recent Transactions</h3>
<table>
    <tr>
        <th>Type</th>
        <th>Amount</th>
        <th>Category</th>
        <th>Date</th>
    </tr>
    <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= ucfirst($t['type']) ?></td>
            <td>$<?= number_format($t['amount'], 2) ?></td>
            <td><?= $t['category'] ?></td>
            <td><?= $t['date'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require_once '../includes/footer.php'; ?>