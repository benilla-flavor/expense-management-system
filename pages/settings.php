<?php
session_start();
require_once '../includes/config.php'; // Database connection
require_once '../includes/header.php'; // Header
require_once '../includes/helpers.php'; // Flash message helper

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch the current user's data
$stmt = $pdo->prepare("SELECT currency FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$currentCurrency = $user['currency'] ?? 'USD'; // Default to USD if no currency is set

// Handle form submission to update currency preference
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newCurrency = $_POST['currency'];

    // Validate the selected currency
    $allowedCurrencies = ['USD', 'PHP', 'EUR', 'GBP']; // Add more currencies as needed
    if (!in_array($newCurrency, $allowedCurrencies)) {
        setFlashMessage('error', 'Invalid currency selected.');
    } else {
        // Update the user's preferred currency in the database
        $stmt = $pdo->prepare("UPDATE users SET currency = :currency WHERE id = :user_id");
        if ($stmt->execute(['currency' => $newCurrency, 'user_id' => $_SESSION['user_id']])) {
            // Update the session variable immediately
            $_SESSION['preferred_currency'] = $newCurrency;

            setFlashMessage('success', 'Currency updated successfully.');
        } else {
            setFlashMessage('error', 'Failed to update currency.');
        }
    }

    header('Location: settings.php'); // Redirect to avoid form resubmission
    exit;
}
?>

<div class="container">
    <h1>Settings</h1>

    <!-- Display Flash Messages -->
    <div class="flash-messages">
        <?php displayFlashMessage(); ?>
    </div>

    <!-- Form to Update Currency Preference -->
    <div class="form-container">
        <h2>Preferred Currency</h2>
        <form method="POST">
            <label for="currency">Select Currency:</label>
            <select name="currency" id="currency" required>
                <option value="USD" <?= $currentCurrency === 'USD' ? 'selected' : '' ?>>USD - United States Dollar</option>
                <option value="PHP" <?= $currentCurrency === 'PHP' ? 'selected' : '' ?>>PHP - Philippine Peso</option>
                <option value="EUR" <?= $currentCurrency === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                <option value="GBP" <?= $currentCurrency === 'GBP' ? 'selected' : '' ?>>GBP - British Pound</option>
                <!-- Add more currencies as needed -->
            </select>

            <!-- Button Container -->
            <div class="button-container">
                <button type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>