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
    $borrow_id = (int)$_POST['borrow_id'];
    $user_id = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT a.id as asset_id, bh.quantity, a.asset_name FROM borrow_history bh JOIN assets a ON bh.asset_id = a.id WHERE bh.id = ? AND bh.user_id = ? AND bh.status = 'pending'");
    $stmt->bind_param("ii", $borrow_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel this request']);
        exit();
    }

    $asset = $result->fetch_assoc();

    $stmt = $conn->prepare("UPDATE borrow_history SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $borrow_id, $user_id);

    if ($stmt->execute()) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'cancelled')");
        $message = "Your borrow request for {$asset['asset_name']} has been cancelled.";
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Request cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel request']);
    }
}
?>
