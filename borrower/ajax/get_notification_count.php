<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$count = getUnreadNotificationsCount($user_id, $conn);
echo json_encode(['count' => $count]);
?>
