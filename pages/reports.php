<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header
require_once '../classes/Transaction.php'; // Transaction class

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$transaction = new Transaction($pdo);

// Default year and month
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');

// Fetch monthly summary
$summary = $transaction->getMonthlySummary($_SESSION['user_id'], $year, $month);

// Calculate total income, expenses, and savings
$totalIncome = 0;
$totalExpenses = 0;

foreach ($summary as $row) {
    if ($row['type'] === 'income') {
        $totalIncome += $row['total'];
    } elseif ($row['type'] === 'expense') {
        $totalExpenses += $row['total'];
    }
}

$savings = $totalIncome - $totalExpenses;
?>

<div class="container">
    <h1>Reports</h1>

    <!-- Report Summary Widgets -->
    <div class="dashboard-widgets">
        <div class="widget">
            <h3>Total Income</h3>
            <p>$<?= number_format($totalIncome, 2) ?></p>
        </div>
        <div class="widget">
            <h3>Total Expenses</h3>
            <p>$<?= number_format($totalExpenses, 2) ?></p>
        </div>
        <div class="widget">
            <h3>Savings</h3>
            <p>$<?= number_format($savings, 2) ?></p>
        </div>
    </div>

    <!-- Report Form -->
    <div class="form-container">
        <h2>Generate Report</h2>
        <form method="GET" class="report-form">
            <label for="year">Year:</label>
            <input type="number" name="year" id="year" value="<?= $year ?>" required>

            <label for="month">Month:</label>
            <input type="number" name="month" id="month" value="<?= $month ?>" min="1" max="12" required>

            <!-- Button Container -->
            <div class="button-container">
                <button type="submit">Generate Report</button>
            </div>
        </form>
    </div>

    <!-- Monthly Summary -->
    <div class="table-container">
        <h3>Monthly Summary (<?= date('F Y', strtotime("$year-$month-01")) ?>)</h3>
        <table>
            <tr>
                <th>Type</th>
                <th>Total Amount</th>
            </tr>
            <?php foreach ($summary as $row): ?>
                <tr>
                    <td><?= ucfirst($row['type']) ?></td>
                    <td>$<?= number_format($row['total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>