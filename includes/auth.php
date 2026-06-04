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

// Only redirect admin to VB.NET app if on localhost (not Railway)
if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin' && !getenv('RAILWAY_PUBLIC_DOMAIN')) {
    header("Location: http://localhost:8080/ADSSU_LAMS");
    exit();
}
?>
