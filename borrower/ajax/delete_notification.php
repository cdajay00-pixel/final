<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Not found']);
}
?>
