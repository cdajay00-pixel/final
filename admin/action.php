<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$id = (int)$_POST['id'];
$action = $_POST['action'];

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

switch ($action) {
    case 'approve':
        $stmt = $conn->prepare("UPDATE borrow_history SET status = 'approved' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $item = $conn->query("SELECT asset_id, quantity, user_id FROM borrow_history WHERE id = $id")->fetch_assoc();
            if ($item) {
                $conn->query("UPDATE assets SET available_quantity = available_quantity - {$item['quantity']} WHERE id = {$item['asset_id']}");
                $asset = $conn->query("SELECT asset_name FROM assets WHERE id = {$item['asset_id']}")->fetch_assoc();
                addNotification($item['user_id'], "Your borrow request for {$asset['asset_name']} has been approved! You can now pick up the item.", 'success', $conn);
            }
            echo json_encode(['success' => true, 'message' => 'Request approved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not approve or already processed']);
        }
        break;

    case 'deny':
        $stmt = $conn->prepare("UPDATE borrow_history SET status = 'denied' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $item = $conn->query("SELECT user_id, asset_id FROM borrow_history WHERE id = $id")->fetch_assoc();
            if ($item) {
                $asset = $conn->query("SELECT asset_name FROM assets WHERE id = {$item['asset_id']}")->fetch_assoc();
                addNotification($item['user_id'], "Your borrow request for {$asset['asset_name']} has been denied.", 'error', $conn);
            }
            echo json_encode(['success' => true, 'message' => 'Request denied']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not deny or already processed']);
        }
        break;

    case 'confirm_return':
        $stmt = $conn->prepare("UPDATE borrow_history SET status = 'returned', actual_return_date = NOW() WHERE id = ? AND status = 'return_requested'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $item = $conn->query("SELECT asset_id, quantity, user_id FROM borrow_history WHERE id = $id")->fetch_assoc();
            if ($item) {
                $conn->query("UPDATE assets SET available_quantity = available_quantity + {$item['quantity']} WHERE id = {$item['asset_id']}");
                $asset = $conn->query("SELECT asset_name FROM assets WHERE id = {$item['asset_id']}")->fetch_assoc();
                addNotification($item['user_id'], "Your return for {$asset['asset_name']} has been confirmed. Item returned successfully.", 'success', $conn);
            }
            echo json_encode(['success' => true, 'message' => 'Return confirmed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not confirm return or already processed']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
