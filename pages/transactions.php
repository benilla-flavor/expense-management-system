<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header
require_once '../classes/Transaction.php'; // Transaction class

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$transaction = new Transaction($pdo);

// Handle form submission to add or edit a transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_transaction'])) {
        // Edit an existing transaction
        $transactionId = $_POST['transaction_id'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $category = $_POST['category'];
        $date = $_POST['date'];
        $notes = $_POST['notes'];
        $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
        $frequency = $isRecurring ? $_POST['frequency'] : null;

        if ($transaction->editTransaction($transactionId, $type, $amount, $category, $date, $notes, $isRecurring, $frequency)) {
            echo "<p style='color: green;'>Transaction updated successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to update transaction.</p>";
        }
    } elseif (isset($_POST['add_transaction'])) {
        // Add a new transaction
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $category = $_POST['category'];
        $date = $_POST['date'];
        $notes = $_POST['notes'];
        $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
        $frequency = $isRecurring ? $_POST['frequency'] : null;

        if ($transaction->addTransaction($_SESSION['user_id'], $type, $amount, $category, $date, $notes, $isRecurring, $frequency)) {
            echo "<p style='color: green;'>Transaction added successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to add transaction.</p>";
        }
    }
}

// Handle delete action
if (isset($_GET['delete_transaction'])) {
    $transactionId = $_GET['delete_transaction'];
    if ($transaction->deleteTransaction($transactionId)) {
        echo "<p style='color: green;'>Transaction deleted successfully!</p>";
    } else {
        echo "<p style='color: red;'>Failed to delete transaction.</p>";
    }
}

// Fetch all transactions for the logged-in user
$transactions = $transaction->getTransactions($_SESSION['user_id']);
?>

<h1>Manage Transactions</h1>

<!-- Form to Add/Edit a Transaction -->
<h2>Add/Edit a Transaction</h2>
<form method="POST">
    <input type="hidden" name="transaction_id" id="transaction_id" value="<?= $_GET['edit_transaction'] ?? '' ?>">

    <label for="type">Type:</label>
    <select name="type" id="type" required>
        <option value="income">Income</option>
        <option value="expense">Expense</option>
    </select>

    <label for="amount">Amount:</label>
    <input type="number" name="amount" id="amount" step="0.01" min="0" required>

    <label for="category">Category:</label>
    <input type="text" name="category" id="category" placeholder="e.g., Food, Rent" required>

    <label for="date">Date:</label>
    <input type="date" name="date" id="date" required>

    <label for="notes">Notes:</label>
    <textarea name="notes" id="notes" placeholder="Optional notes"></textarea>

    <label for="is_recurring">Recurring:</label>
    <input type="checkbox" name="is_recurring" id="is_recurring">

    <div id="frequency-field" style="display: none;">
        <label for="frequency">Frequency:</label>
        <select name="frequency" id="frequency">
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
        </select>
    </div>

    <button type="submit" name="<?= isset($_GET['edit_transaction']) ? 'edit_transaction' : 'add_transaction' ?>">
        <?= isset($_GET['edit_transaction']) ? 'Update Transaction' : 'Add Transaction' ?>
    </button>
</form>

<script>
    // Show/hide frequency field based on "Recurring" checkbox
    const recurringCheckbox = document.getElementById('is_recurring');
    const frequencyField = document.getElementById('frequency-field');
    recurringCheckbox.addEventListener('change', () => {
        frequencyField.style.display = recurringCheckbox.checked ? 'block' : 'none';
    });

    // Pre-fill form fields for editing
    <?php if (isset($_GET['edit_transaction'])): ?>
        const transactionToEdit = <?= json_encode($transactions[array_search($_GET['edit_transaction'], array_column($transactions, 'id'))]) ?>;
        document.getElementById('type').value = transactionToEdit.type;
        document.getElementById('amount').value = transactionToEdit.amount;
        document.getElementById('category').value = transactionToEdit.category;
        document.getElementById('date').value = transactionToEdit.date;
        document.getElementById('notes').value = transactionToEdit.notes;
        document.getElementById('is_recurring').checked = transactionToEdit.is_recurring;
        document.getElementById('frequency').value = transactionToEdit.frequency || '';
        document.getElementById('frequency-field').style.display = transactionToEdit.is_recurring ? 'block' : 'none';
    <?php endif; ?>
</script>

<!-- Display Existing Transactions -->
<h2>Existing Transactions</h2>
<table border="1">
    <tr>
        <th>Type</th>
        <th>Amount</th>
        <th>Category</th>
        <th>Date</th>
        <th>Notes</th>
        <th>Recurring</th>
        <th>Frequency</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= ucfirst($t['type']) ?></td>
            <td>$<?= number_format($t['amount'], 2) ?></td>
            <td><?= $t['category'] ?></td>
            <td><?= $t['date'] ?></td>
            <td><?= $t['notes'] ?: '-' ?></td>
            <td><?= $t['is_recurring'] ? 'Yes' : 'No' ?></td>
            <td><?= $t['frequency'] ?: '-' ?></td>
            <td>
                <a href="?edit_transaction=<?= $t['id'] ?>">Edit</a> |
                <a href="?delete_transaction=<?= $t['id'] ?>" onclick="return confirm('Are you sure you want to delete this transaction?')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require_once '../includes/footer.php'; ?>