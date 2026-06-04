<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode([]));
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("
    SELECT bh.id, bh.borrow_date, bh.expected_return_date, bh.status, 
           a.asset_name, a.category 
    FROM borrow_history bh 
    JOIN assets a ON bh.asset_id = a.id 
    WHERE bh.user_id = $user_id AND bh.status IN ('approved', 'borrowed')
    ORDER BY bh.borrow_date DESC
");

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

header('Content-Type: application/json');
echo json_encode($items);
?>

