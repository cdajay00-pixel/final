<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode([]));
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("
    SELECT bh.*, a.asset_name, a.category 
    FROM borrow_history bh 
    JOIN assets a ON bh.asset_id = a.id 
    WHERE bh.user_id = $user_id 
    ORDER BY bh.borrow_date DESC LIMIT 50
");

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

header('Content-Type: application/json');
echo json_encode($history);
?>