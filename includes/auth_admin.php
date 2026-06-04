<?php
session_start();
require_once 'db.php';
require_once 'config.php';
require_once 'functions.php';

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: " . SITE_URL . "login.php");
    exit();
}
$_SESSION['last_activity'] = time();

function requireAdmin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
    if ($_SESSION['role'] != 'Admin') {
        header("Location: " . SITE_URL . "borrower/dashboard.php");
        exit();
    }
}
?>
