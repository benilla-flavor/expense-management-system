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

$summary = $transaction->getMonthlySummary($_SESSION['user_id'], $year, $month);
?>

<div class="container">
    <div class="reports">
        <h2>Reports</h2>

        <!-- Report Form -->
        <form method="GET" class="report-form">
            <label for="year">Year:</label>
            <input type="number" name="year" id="year" value="<?= $year ?>" required>

            <label for="month">Month:</label>
            <input type="number" name="month" id="month" value="<?= $month ?>" min="1" max="12" required>

            <button type="submit">Generate Report</button>
        </form>

        <!-- Monthly Summary -->
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