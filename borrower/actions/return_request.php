<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $borrow_id = (int)$_POST['borrow_id'];
    $user_id = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE borrow_history SET status = 'return_requested' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $borrow_id, $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt2 = $conn->prepare("SELECT a.asset_name FROM borrow_history bh JOIN assets a ON bh.asset_id = a.id WHERE bh.id = ?");
        $stmt2->bind_param("i", $borrow_id);
        $stmt2->execute();
        $asset = $stmt2->get_result()->fetch_assoc();

        $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'return_request')");
        $message = "Return request submitted for {$asset['asset_name']}. Awaiting admin confirmation.";
        $stmt3->bind_param("is", $user_id, $message);
        $stmt3->execute();

        echo json_encode(['success' => true, 'message' => 'Return request submitted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit return request']);
    }
}
?>
