<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header
require_once '../classes/Budget.php'; // Budget class

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$budget = new Budget($pdo);

// Handle form submission to add or edit a budget
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_budget'])) {
        // Edit an existing budget
        $budgetId = $_POST['budget_id'];
        $category = $_POST['category'];
        $limitAmount = $_POST['limit_amount'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];

        if ($budget->editBudget($budgetId, $category, $limitAmount, $startDate, $endDate)) {
            echo "<p style='color: green;'>Budget updated successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to update budget.</p>";
        }
    } elseif (isset($_POST['add_budget'])) {
        // Add a new budget
        $category = $_POST['category'];
        $limitAmount = $_POST['limit_amount'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];

        if ($budget->addBudget($_SESSION['user_id'], $category, $limitAmount, $startDate, $endDate)) {
            echo "<p style='color: green;'>Budget added successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to add budget.</p>";
        }
    }
}

// Handle delete action
if (isset($_GET['delete_budget'])) {
    $budgetId = $_GET['delete_budget'];
    if ($budget->deleteBudget($budgetId)) {
        echo "<p style='color: green;'>Budget deleted successfully!</p>";
    } else {
        echo "<p style='color: red;'>Failed to delete budget.</p>";
    }
}

// Fetch all budgets for the logged-in user
$budgets = $budget->getBudgets($_SESSION['user_id']);
?>

<h1>Manage Budgets</h1>

<!-- Form to Add/Edit a Budget -->
<h2>Add/Edit a Budget</h2>
<form method="POST">
    <input type="hidden" name="budget_id" id="budget_id" value="<?= $_GET['edit_budget'] ?? '' ?>">

    <label for="category">Category:</label>
    <input type="text" name="category" id="category" placeholder="e.g., Food, Rent" required>

    <label for="limit_amount">Limit Amount:</label>
    <input type="number" name="limit_amount" id="limit_amount" step="0.01" min="0" required>

    <label for="start_date">Start Date:</label>
    <input type="date" name="start_date" id="start_date" required>

    <label for="end_date">End Date:</label>
    <input type="date" name="end_date" id="end_date" required>

    <button type="submit" name="<?= isset($_GET['edit_budget']) ? 'edit_budget' : 'add_budget' ?>">
        <?= isset($_GET['edit_budget']) ? 'Update Budget' : 'Add Budget' ?>
    </button>
</form>

<script>
    // Pre-fill form fields for editing
    <?php if (isset($_GET['edit_budget'])): ?>
        const budgetToEdit = <?= json_encode($budgets[array_search($_GET['edit_budget'], array_column($budgets, 'id'))]) ?>;
        document.getElementById('category').value = budgetToEdit.category;
        document.getElementById('limit_amount').value = budgetToEdit.limit_amount;
        document.getElementById('start_date').value = budgetToEdit.start_date;
        document.getElementById('end_date').value = budgetToEdit.end_date;
    <?php endif; ?>
</script>

<!-- Display Existing Budgets -->
<h2>Existing Budgets</h2>
<table border="1">
    <tr>
        <th>Category</th>
        <th>Limit Amount</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($budgets as $b): ?>
        <tr>
            <td><?= $b['category'] ?></td>
            <td>$<?= number_format($b['limit_amount'], 2) ?></td>
            <td><?= $b['start_date'] ?></td>
            <td><?= $b['end_date'] ?></td>
            <td>
                <a href="?edit_budget=<?= $b['id'] ?>">Edit</a> |
                <a href="?delete_budget=<?= $b['id'] ?>" onclick="return confirm('Are you sure you want to delete this budget?')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require_once '../includes/footer.php'; ?>