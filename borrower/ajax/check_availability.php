<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'available' => false]);
    exit();
}

// Get parameters
$asset_id = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
$quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

if ($asset_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid asset ID', 'available' => false]);
    exit();
}

// Check asset availability
$query = "SELECT asset_name, available_quantity, quantity FROM assets WHERE id = $asset_id";
$result = $conn->query($query);

if ($result && $row = $result->fetch_assoc()) {
    $available = $row['available_quantity'] >= $quantity;
    
    echo json_encode([
        'success' => true,
        'available' => $available,
        'asset_name' => $row['asset_name'],
        'available_quantity' => $row['available_quantity'],
        'total_quantity' => $row['quantity'],
        'requested_quantity' => $quantity,
        'message' => $available ? 'Asset is available' : 'Not enough quantity available'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Asset not found', 'available' => false]);
}
?>