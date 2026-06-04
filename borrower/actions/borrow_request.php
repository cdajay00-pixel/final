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
    $asset_id = $_POST['asset_id'];
    $user_id = $_SESSION['user_id'];
    $quantity = $_POST['quantity'];
    $expected_return_date = $_POST['expected_return_date'];
    $purpose = $_POST['purpose'];
    
    // Validate
    if (!canBorrowMore($user_id, $conn)) {
        $_SESSION['error'] = "Maximum borrow limit reached (" . MAX_BORROW_ITEMS . " items)";
        header("Location: " . BORROWER_URL . "available_assets.php");
        exit();
    }
    
    // Check availability
    $check = $conn->query("SELECT available_quantity, asset_name FROM assets WHERE id = $asset_id");
    $asset = $check->fetch_assoc();
    
    if ($asset && $asset['available_quantity'] >= $quantity) {
        // Create borrow request
        $stmt = $conn->prepare("INSERT INTO borrow_history (asset_id, user_id, quantity, expected_return_date, purpose, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiiss", $asset_id, $user_id, $quantity, $expected_return_date, $purpose);
        
        if ($stmt->execute()) {
            // Update available quantity
            $conn->query("UPDATE assets SET available_quantity = available_quantity - $quantity WHERE id = $asset_id");
            
            // Add notification for user
            addNotification($user_id, "Your borrow request for {$asset['asset_name']} has been submitted. Waiting for approval.", 'info', $conn);
            
            $_SESSION['message'] = "Borrow request submitted! Please wait for admin approval.";
        } else {
            $_SESSION['error'] = "Failed to submit request.";
        }
    } else {
        $_SESSION['error'] = "Not enough quantity available.";
    }
    
    header("Location: " . BORROWER_URL . "available_assets.php");
}
?>