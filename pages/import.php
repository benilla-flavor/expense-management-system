<?php
session_start();
require_once '../includes/config.php';
require_once '../classes/Transaction.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $transaction = new Transaction($pdo);
    $file = $_FILES['csv_file']['tmp_name'];

    if ($transaction->importTransactions($_SESSION['user_id'], $file)) {
        echo "Import successful!";
    } else {
        echo "Import failed!";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit">Import</button>
</form>