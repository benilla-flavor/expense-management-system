<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header
require_once '../includes/helpers.php'; // Flash message helper and formatCurrency()
require_once '../classes/Budget.php'; // Budget class

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch the user's preferred currency (cached in session if not already set)
if (!isset($_SESSION['preferred_currency'])) {
    $stmt = $pdo->prepare("SELECT currency FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['preferred_currency'] = $user['currency'] ?? 'USD'; // Default to USD if no currency is set
}

$preferredCurrency = $_SESSION['preferred_currency'];

$budget = new Budget($pdo);

// Handle form submission to add or edit a budget
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];

    // Validate inputs
    $categoryId = $_POST['category_id'] ?? null;
    $limitAmount = $_POST['limit_amount'] ?? null;
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;

    if (empty($categoryId) || !is_numeric($categoryId)) {
        $errors[] = "Category is required.";
    }
    if (empty($limitAmount) || !is_numeric($limitAmount) || $limitAmount <= 0) {
        $errors[] = "Limit amount must be a positive number.";
    }
    if (empty($startDate)) {
        $errors[] = "Start date is required.";
    }
    if (empty($endDate)) {
        $errors[] = "End date is required.";
    }

    if (empty($errors)) {
        if (isset($_POST['edit_budget'])) {
            // Edit an existing budget
            $budgetId = $_POST['budget_id'];
            if ($budget->editBudget($budgetId, $categoryId, $limitAmount, $startDate, $endDate)) {
                setFlashMessage('success', 'Budget updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update budget.');
            }
        } elseif (isset($_POST['add_budget'])) {
            // Add a new budget
            if ($budget->addBudget($_SESSION['user_id'], $categoryId, $limitAmount, $startDate, $endDate)) {
                setFlashMessage('success', 'Budget added successfully.');
            } else {
                setFlashMessage('error', 'Failed to add budget.');
            }
        }

        header('Location: budget.php'); // Redirect to avoid form resubmission
        exit;
    } else {
        // Display validation errors as flash messages
        foreach ($errors as $error) {
            setFlashMessage('error', $error);
        }
    }
}

// Handle delete action
if (isset($_GET['delete_budget'])) {
    $budgetId = $_GET['delete_budget'];
    if ($budget->deleteBudget($budgetId)) {
        setFlashMessage('success', 'Budget deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete budget.');
    }

    header('Location: budget.php'); // Redirect to avoid form resubmission
    exit;
}

// Fetch all budgets for the logged-in user
$budgets = $budget->getBudgets($_SESSION['user_id']);

// Calculate total budget and remaining budget
$totalBudget = 0;
$spentAmount = 0;

foreach ($budgets as $b) {
    $totalBudget += $b['limit_amount'];
    $spentAmount += $b['spent'] ?? 0; // Assuming 'spent' is fetched from the database
}

$remainingBudget = $totalBudget - $spentAmount;
?>

<div class="container">
    <h1>Manage Budgets</h1>

    <!-- Display Flash Messages -->
    <div class="flash-messages">
        <?php displayFlashMessage(); ?>
    </div>

    <!-- Budget Summary Widgets -->
    <div class="dashboard-widgets">
        <div class="widget">
            <h3>Total Budget</h3>
            <p><?= formatCurrency($totalBudget, $preferredCurrency) ?></p>
        </div>
        <div class="widget">
            <h3>Remaining Budget</h3>
            <p><?= formatCurrency($remainingBudget, $preferredCurrency) ?></p>
        </div>
        <div class="widget">
            <h3>Total Spent</h3>
            <p><?= formatCurrency($spentAmount, $preferredCurrency) ?></p>
        </div>
    </div>

    <!-- Form to Add/Edit a Budget -->
    <div class="form-container">
        <h2>Add/Edit a Budget</h2>
        <form method="POST">
            <input type="hidden" name="budget_id" id="budget_id" value="<?= $_GET['edit_budget'] ?? '' ?>">

            <label for="category_id">Category:</label>
            <select name="category_id" id="category_id" required>
                <?php
                // Fetch all categories from the database
                $categoriesQuery = "SELECT * FROM categories";
                $categoriesStmt = $pdo->query($categoriesQuery);
                $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($categories as $category) {
                    echo '<option value="' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</option>';
                }
                ?>
            </select>

            <label for="limit_amount">Limit Amount:</label>
            <input type="number" name="limit_amount" id="limit_amount" step="0.01" min="0" required>

            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" required>

            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" required>

            <!-- Button Container -->
            <div class="button-container">
                <button type="submit" name="<?= isset($_GET['edit_budget']) ? 'edit_budget' : 'add_budget' ?>">
                    <?= isset($_GET['edit_budget']) ? 'Update Budget' : 'Add Budget' ?>
                </button>
            </div>
        </form>
    </div>

    <script>
        // Pre-fill form fields for editing
        <?php if (isset($_GET['edit_budget'])): ?>
            const budgetToEdit = <?= json_encode($budgets[array_search($_GET['edit_budget'], array_column($budgets, 'id'))]) ?>;
            document.getElementById('category_id').value = budgetToEdit.category_id;
            document.getElementById('limit_amount').value = budgetToEdit.limit_amount;
            document.getElementById('start_date').value = budgetToEdit.start_date;
            document.getElementById('end_date').value = budgetToEdit.end_date;
        <?php endif; ?>
    </script>

    <!-- Display Existing Budgets -->
    <div class="table-container">
        <h2>Existing Budgets</h2>
        <table border="1">
            <tr>
                <th>Category</th>
                <th>Limit Amount</th>
                <th>Spent Amount</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($budgets as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['category_name']) ?></td> <!-- Use category_name -->
                    <td><?= formatCurrency($b['limit_amount'], $preferredCurrency) ?></td>
                    <td><?= formatCurrency($b['spent'] ?? 0, $preferredCurrency) ?></td>
                    <td><?= $b['start_date'] ?></td>
                    <td><?= $b['end_date'] ?></td>
                    <td>
                        <a href="?edit_budget=<?= $b['id'] ?>" class="button">Edit</a> |
                        <a href="?delete_budget=<?= $b['id'] ?>" onclick="return confirm('Are you sure you want to delete this budget?')" class="button">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>