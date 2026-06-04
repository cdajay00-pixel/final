<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $borrow_id = $_POST['borrow_id'];
    $user_id = $_SESSION['user_id'];
    
    // Get asset info
    $result = $conn->query("
        SELECT a.id as asset_id, bh.quantity, a.asset_name 
        FROM borrow_history bh 
        JOIN assets a ON bh.asset_id = a.id 
        WHERE bh.id = $borrow_id AND bh.user_id = $user_id AND bh.status = 'pending'
    ");
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel this request']);
        exit();
    }
    
    $asset = $result->fetch_assoc();
    
    // Update status to cancelled
    $stmt = $conn->prepare("UPDATE borrow_history SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $borrow_id, $user_id);
    
    if ($stmt->execute()) {
        // Restore available quantity
        $conn->query("UPDATE assets SET available_quantity = available_quantity + {$asset['quantity']} WHERE id = {$asset['asset_id']}");
        
        // Add notification
        $message = "Your borrow request for {$asset['asset_name']} has been cancelled.";
        $conn->query("INSERT INTO notifications (user_id, message, type) VALUES ($user_id, '$message', 'cancelled')");
        
        echo json_encode(['success' => true, 'message' => 'Request cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel request']);
    }
}
?>