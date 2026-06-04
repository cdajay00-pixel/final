<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'Admin') {
    header("Location: " . SITE_URL . "login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $asset_id = (int)$_POST['asset_id'];
    $user_id = (int)$_SESSION['user_id'];
    $quantity = (int)$_POST['quantity'];
    $expected_return_date = $_POST['expected_return_date'];
    $purpose = $_POST['purpose'];

    if ($quantity < 1) {
        header("Location: " . BORROWER_URL . "available_assets.php?error=" . urlencode("Invalid quantity."));
        exit();
    }

    if (!canBorrowMore($user_id, $conn)) {
        header("Location: " . BORROWER_URL . "available_assets.php?error=" . urlencode("Maximum borrow limit reached (" . MAX_BORROW_ITEMS . " items)"));
        exit();
    }

    $stmt = $conn->prepare("SELECT available_quantity, asset_name FROM assets WHERE id = ?");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();

    if ($asset && $asset['available_quantity'] >= $quantity) {
        $stmt = $conn->prepare("INSERT INTO borrow_history (asset_id, user_id, quantity, expected_return_date, purpose, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiiss", $asset_id, $user_id, $quantity, $expected_return_date, $purpose);

        if ($stmt->execute()) {
            addNotification($user_id, "Your borrow request for {$asset['asset_name']} has been submitted. Waiting for approval.", 'info', $conn);
            header("Location: " . BORROWER_URL . "available_assets.php?message=" . urlencode("Borrow request submitted! Please wait for admin approval."));
        } else {
            header("Location: " . BORROWER_URL . "available_assets.php?error=" . urlencode("Failed to submit request."));
        }
    } else {
        header("Location: " . BORROWER_URL . "available_assets.php?error=" . urlencode("Not enough quantity available."));
    }
    exit();
}
?>
