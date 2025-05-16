<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header
require_once '../includes/helpers.php'; // Flash message helper and formatCurrency()
require_once '../classes/Transaction.php'; // Transaction class

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

$transaction = new Transaction($pdo);

// Handle form submission to add or edit a transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_transaction'])) {
        // Edit an existing transaction
        $transactionId = $_POST['transaction_id'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $categoryId = $_POST['category_id']; // Use category_id instead of category
        $date = $_POST['date'];
        $notes = $_POST['notes'];
        $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
        $frequency = $isRecurring ? $_POST['frequency'] : null;

        if ($transaction->editTransaction($transactionId, $type, $amount, $categoryId, $date, $notes, $isRecurring, $frequency)) {
            setFlashMessage('success', 'Transaction updated successfully.');
        } else {
            setFlashMessage('error', 'Failed to update transaction.');
        }

        header('Location: transactions.php'); // Redirect to avoid form resubmission
        exit;
    } elseif (isset($_POST['add_transaction'])) {
        // Add a new transaction
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $categoryId = $_POST['category_id']; // Use category_id instead of category
        $date = $_POST['date'];
        $notes = $_POST['notes'];
        $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
        $frequency = $isRecurring ? $_POST['frequency'] : null;

        if ($transaction->addTransaction($_SESSION['user_id'], $type, $amount, $categoryId, $date, $notes, $isRecurring, $frequency)) {
            setFlashMessage('success', 'Transaction added successfully.');
        } else {
            setFlashMessage('error', 'Failed to add transaction.');
        }

        header('Location: transactions.php'); // Redirect to avoid form resubmission
        exit;
    }
}

// Handle delete action
if (isset($_GET['delete_transaction'])) {
    $transactionId = $_GET['delete_transaction'];
    if ($transaction->deleteTransaction($transactionId)) {
        setFlashMessage('success', 'Transaction deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete transaction.');
    }

    header('Location: transactions.php'); // Redirect to avoid form resubmission
    exit;
}

// Fetch all transactions for the logged-in user
$transactions = $transaction->getTransactions($_SESSION['user_id']);

// Fetch all categories for the dropdown
$categoriesQuery = "SELECT * FROM categories";
$categoriesStmt = $pdo->query($categoriesQuery);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1>Manage Transactions</h1>

    <!-- Display Flash Messages -->
    <div class="flash-messages">
        <?php displayFlashMessage(); ?>
    </div>

    <!-- Form to Add/Edit a Transaction -->
    <div class="form-container">
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

            <label for="category_id">Category:</label>
            <select name="category_id" id="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>

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

            <!-- Button Container -->
            <div class="button-container">
                <button type="submit" name="<?= isset($_GET['edit_transaction']) ? 'edit_transaction' : 'add_transaction' ?>">
                    <?= isset($_GET['edit_transaction']) ? 'Update Transaction' : 'Add Transaction' ?>
                </button>
            </div>
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
                document.getElementById('category_id').value = transactionToEdit.category_id; // Use category_id
                document.getElementById('date').value = transactionToEdit.date;
                document.getElementById('notes').value = transactionToEdit.notes;
                document.getElementById('is_recurring').checked = transactionToEdit.is_recurring;
                document.getElementById('frequency').value = transactionToEdit.frequency || '';
                document.getElementById('frequency-field').style.display = transactionToEdit.is_recurring ? 'block' : 'none';
            <?php endif; ?>
        </script>
    </div>

    <!-- Display Existing Transactions -->
    <div class="table-container">
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
                    <td><?= formatCurrency($t['amount'], $preferredCurrency) ?></td>
                    <td><?= htmlspecialchars($t['category_name']) ?></td> <!-- Use category_name -->
                    <td><?= $t['date'] ?></td>
                    <td><?= $t['notes'] ?: '-' ?></td>
                    <td><?= $t['is_recurring'] ? 'Yes' : 'No' ?></td>
                    <td><?= $t['frequency'] ?: '-' ?></td>
                    <td>
                        <a href="?edit_transaction=<?= $t['id'] ?>" class="button">Edit</a> |
                        <a href="?delete_transaction=<?= $t['id'] ?>" onclick="return confirm('Are you sure you want to delete this transaction?')" class="button">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>