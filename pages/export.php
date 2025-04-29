<?php
session_start();
require_once '../includes/config.php';
require_once '../classes/Transaction.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$transaction = new Transaction($pdo);
$transaction->exportTransactions($_SESSION['user_id']);
?>