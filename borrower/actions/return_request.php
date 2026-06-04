<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $borrow_id = $_POST['borrow_id'];
    $user_id = $_SESSION['user_id'];
    
    // Update status to return requested
    $stmt = $conn->prepare("UPDATE borrow_history SET status = 'return_requested' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $borrow_id, $user_id);
    
    if ($stmt->execute()) {
        // Get asset info for notification
        $result = $conn->query("SELECT a.asset_name FROM borrow_history bh JOIN assets a ON bh.asset_id = a.id WHERE bh.id = $borrow_id");
        $asset = $result->fetch_assoc();
        
        // Add notification
        $message = "Return request submitted for {$asset['asset_name']}. Awaiting admin confirmation.";
        $conn->query("INSERT INTO notifications (user_id, message, type) VALUES ($user_id, '$message', 'return_request')");
        
        echo json_encode(['success' => true, 'message' => 'Return request submitted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit return request']);
    }
}
?>