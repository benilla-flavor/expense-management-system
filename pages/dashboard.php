<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header with dark mode toggle
require_once '../includes/helpers.php'; // Helper functions (e.g., formatCurrency)
require_once '../classes/Transaction.php'; // Transaction class
require_once '../classes/Budget.php'; // Budget class

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Optional Enhancement: Cache the Preferred Currency
if (!isset($_SESSION['preferred_currency'])) {
    $stmt = $pdo->prepare("SELECT currency FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['preferred_currency'] = $user['currency'] ?? 'USD'; // Default to USD if no currency is set
}

$preferredCurrency = $_SESSION['preferred_currency'];

// Initialize classes
$transaction = new Transaction($pdo);
$budget = new Budget($pdo);

// Fetch data
$transactions = $transaction->getTransactions($_SESSION['user_id']);
$budgetProgress = $budget->getBudgetProgress($_SESSION['user_id']);

// Calculate total income, expenses, and savings
$totalIncome = 0;
$totalExpenses = 0;

foreach ($transactions as $t) {
    if ($t['type'] === 'income') {
        $totalIncome += $t['amount'];
    } elseif ($t['type'] === 'expense') {
        $totalExpenses += $t['amount'];
    }
}

$savings = $totalIncome - $totalExpenses;

// Fetch spending data grouped by category (all-time)
$stmt = $pdo->prepare("
    SELECT c.name AS category_name, SUM(t.amount) AS total 
    FROM transactions t
    INNER JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = :user_id 
    GROUP BY c.name
");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$monthlySpendingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format data for JavaScript
if (empty($monthlySpendingData)) {
    $categories = ['No Data'];
    $totals = [0]; // Default value for testing
} else {
    $categories = array_column($monthlySpendingData, 'category_name');
    $totals = array_map('floatval', array_column($monthlySpendingData, 'total')); // Ensure numeric values
}
?>

<div class="container">
    <h2>Welcome Back!</h2>

    <!-- Dashboard Widgets -->
    <div class="dashboard-widgets">
        <div class="widget">
            <h3>Total Income</h3>
            <p><?= formatCurrency($totalIncome, $preferredCurrency) ?></p>
        </div>
        <div class="widget">
            <h3>Total Expenses</h3>
            <p><?= formatCurrency($totalExpenses, $preferredCurrency) ?></p>
        </div>
        <div class="widget">
            <h3>Savings</h3>
            <p><?= formatCurrency($savings, $preferredCurrency) ?></p>
        </div>
    </div>

    <!-- Budget Progress Section -->
    <h3>Budget Progress</h3>
    <div id="budget-progress">
        <?php foreach ($budgetProgress as $progress): ?>
            <div class="budget-item">
                <strong><?= htmlspecialchars($progress['category_name']) ?>:</strong> <!-- Use category_name -->
                <div class="progress-bar">
                    <div class="progress" style="width: <?= min(($progress['spent'] / $progress['limit_amount']) * 100, 100) ?>%;"></div>
                </div>
                <span>Spent: <?= formatCurrency($progress['spent'], $preferredCurrency) ?> / Limit: <?= formatCurrency($progress['limit_amount'], $preferredCurrency) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Monthly Spending Chart -->
    <h3>Spending by Category</h3>
    <div class="chart-container">
        <?php if (empty($monthlySpendingData)): ?>
            <p>No spending data available.</p>
        <?php else: ?>
            <canvas id="expenseChart" width="200" height="75"></canvas>
        <?php endif; ?>
    </div>

    <!-- Recent Transactions Table -->
    <div class="table-container">
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
                    <td><?= formatCurrency($t['amount'], $preferredCurrency) ?></td>
                    <td><?= htmlspecialchars($t['category_name']) ?></td> <!-- Use category_name -->
                    <td><?= htmlspecialchars($t['date']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js "></script>

<!-- Render Spending Chart -->
<script>
    <?php if (!empty($monthlySpendingData)): ?>
        const categories = <?= json_encode($categories) ?>;
        const totals = <?= json_encode($totals) ?>;

        // Calculate percentages for display
        const totalAmount = totals.reduce((sum, value) => sum + value, 0); // Ensure numeric addition
        console.log("Total Amount:", totalAmount); // Debugging: Log totalAmount
        console.log("Totals Array:", totals); // Debugging: Log totals array

        const percentages = totals.map(value => {
            if (totalAmount > 0) {
                return ((value / totalAmount) * 100).toFixed(1) + '%';
            } else {
                return '0%'; // Handle cases where totalAmount is 0
            }
        });

        console.log("Percentages:", percentages); // Debugging: Log percentages array

        const ctx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(ctx, {
            type: 'doughnut', // Switched from pie to doughnut for better readability
            data: {
                labels: categories,
                datasets: [{
                    label: 'Total Spending',
                    data: totals,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'], // Improved color palette
                    borderColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right', // Move legend to the side for better readability
                        labels: {
                            font: {
                                size: 14 // Increase font size for better visibility
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const percentage = percentages[context.dataIndex];
                                return `${label}: ${value} (${percentage})`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Spending by Category',
                        font: {
                            size: 18 // Larger title for emphasis
                        }
                    }
                },
                animation: {
                    animateRotate: true, // Smooth rotation animation
                    animateScale: true   // Smooth scaling animation
                }
            }
        });
    <?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>