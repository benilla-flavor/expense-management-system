<?php
session_start();
require_once './includes/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
} else {
    header("Location: pages/login.php");
}
?>