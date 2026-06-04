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

// Redirect admin to built-in admin panel (or VB.NET app on localhost)
if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin') {
    if (!getenv('RAILWAY_PUBLIC_DOMAIN')) {
        $admin_app = "http://localhost:8080/ADSSU_LAMS";
        // Only redirect if admin app is accessible
        $ch = @fsockopen("localhost", 8080, $errno, $errstr, 1);
        if ($ch) { fclose($ch); header("Location: $admin_app"); exit(); }
    }
    header("Location: " . SITE_URL . "admin/index.php");
    exit();
}
?>
