<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/header.php'; // Header file
require_once '../classes/Budget.php'; // Budget class
require_once '../classes/Transaction.php'; // Transaction class

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

// Initialize classes
$transaction = new Transaction($pdo);
$budget = new Budget($pdo);

// Fetch data
$transactions = $transaction->getTransactions($_SESSION['user_id']);
$budgetProgress = $budget->getBudgetProgress($_SESSION['user_id']);

// Fetch spending data grouped by category (all-time)
$stmt = $pdo->prepare("
    SELECT category, SUM(amount) AS total 
    FROM transactions 
    WHERE user_id = :user_id 
    GROUP BY category
");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$monthlySpendingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format data for JavaScript
if (empty($monthlySpendingData)) {
    $categories = ['No Data'];
    $totals = [1]; // Default value for testing
} else {
    $categories = array_column($monthlySpendingData, 'category');
    $totals = array_column($monthlySpendingData, 'total');
}
?>

<div class="container">
    <h2>Welcome Back!</h2>

    <!-- Budget Progress Section -->
    <h3>Budget Progress</h3>
    <div id="budget-progress">
        <?php foreach ($budgetProgress as $progress): ?>
            <div class="budget-item">
                <strong><?= htmlspecialchars($progress['category']) ?>:</strong>
                <div class="progress-bar">
                    <div class="progress" style="width: <?= min(($progress['spent'] / $progress['limit_amount']) * 100, 100) ?>%;"></div>
                </div>
                <span>Spent: $<?= number_format($progress['spent'], 2) ?> / Limit: $<?= number_format($progress['limit_amount'], 2) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Monthly Spending Chart -->
    <h3>Spending by Category</h3>
    <div class="chart-container">
        <canvas id="expenseChart" width="200" height="75"></canvas>
    </div>

    <!-- Recent Transactions Table -->
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
                <td><?= ucfirst(htmlspecialchars($t['type'])) ?></td>
                <td>$<?= number_format($t['amount'], 2) ?></td>
                <td><?= htmlspecialchars($t['category']) ?></td>
                <td><?= htmlspecialchars($t['date']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Render Spending Chart -->
<script>
    const categories = <?= json_encode($categories) ?>;
    const totals = <?= json_encode($totals) ?>;

    const ctx = document.getElementById('expenseChart').getContext('2d');
    const expenseChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: categories,
            datasets: [{
                label: 'Total Spending ($)',
                data: totals,
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                borderColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Spending by Category'
                }
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>