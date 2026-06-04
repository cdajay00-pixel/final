<?php
session_start();
require_once 'db.php';
require_once 'config.php';
require_once 'functions.php';

// Auto-check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: " . SITE_URL . "login.php");
    exit();
}
$_SESSION['last_activity'] = time();

// Check if user is admin (from VB.NET) - redirect to VB.NET admin panel
if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin') {
    // Redirect to VB.NET admin application
    header("Location: http://localhost:8080/ADSSU_LAMS"); // Your VB.NET app URL
    exit();
}
?>