<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isBorrower() {
    return isset($_SESSION['role']) && $_SESSION['role'] != 'Admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

function requireBorrower() {
    requireLogin();
    if (!isBorrower()) {
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

function getTableColumns($table_name, $conn) {
    $columns = [];
    $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $table_name);
    $result = $conn->query("DESCRIBE `$table_name`");
    if ($result) {
        while($col = $result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
    }
    return $columns;
}

function getUserStats($user_id, $conn) {
    $stats = ['total_borrowed' => 0, 'active_borrowed' => 0, 'overdue' => 0, 'total_returned' => 0];
    $user_id = (int)$user_id;

    $table_check = $conn->query("SHOW TABLES LIKE 'borrow_history'");
    if (!$table_check || $table_check->num_rows == 0) {
        return $stats;
    }

    $columns = getTableColumns('borrow_history', $conn);
    $status_col = in_array('status', $columns) ? 'status' : (in_array('Status', $columns) ? 'Status' : 'status');

    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id");
    if ($result) $stats['total_borrowed'] = (int)$result->fetch_assoc()['count'];

    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND `$status_col` IN ('approved', 'borrowed')");
    if ($result) $stats['active_borrowed'] = (int)$result->fetch_assoc()['count'];

    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND `$status_col` = 'overdue'");
    if ($result) $stats['overdue'] = (int)$result->fetch_assoc()['count'];

    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND `$status_col` = 'returned'");
    if ($result) $stats['total_returned'] = (int)$result->fetch_assoc()['count'];

    return $stats;
}

function getUnreadNotificationsCount($user_id, $conn) {
    $user_id = (int)$user_id;
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if (!$table_check || $table_check->num_rows == 0) return 0;

    $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    if ($result) return (int)$result->fetch_assoc()['count'];
    return 0;
}

function addNotification($user_id, $message, $type, $conn) {
    $user_id = (int)$user_id;
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if (!$table_check || $table_check->num_rows == 0) return false;

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $message, $type);
        return $stmt->execute();
    }
    return false;
}

function canBorrowMore($user_id, $conn) {
    $user_id = (int)$user_id;
    $table_check = $conn->query("SHOW TABLES LIKE 'borrow_history'");
    if (!$table_check || $table_check->num_rows == 0) return true;

    $columns = getTableColumns('borrow_history', $conn);
    $status_col = in_array('status', $columns) ? 'status' : (in_array('Status', $columns) ? 'Status' : 'status');

    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND `$status_col` IN ('approved', 'borrowed')");
    if ($result) {
        return (int)$result->fetch_assoc()['count'] < MAX_BORROW_ITEMS;
    }
    return true;
}

function getRemainingBorrowSlots($user_id, $conn) {
    $user_id = (int)$user_id;
    $table_check = $conn->query("SHOW TABLES LIKE 'borrow_history'");
    if (!$table_check || $table_check->num_rows == 0) return MAX_BORROW_ITEMS;

    $columns = getTableColumns('borrow_history', $conn);
    $status_col = in_array('status', $columns) ? 'status' : (in_array('Status', $columns) ? 'Status' : 'status');

    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND `$status_col` IN ('approved', 'borrowed')");
    if ($result) {
        return MAX_BORROW_ITEMS - (int)$result->fetch_assoc()['count'];
    }
    return MAX_BORROW_ITEMS;
}

function getCategories($conn) {
    $result = $conn->query("SELECT DISTINCT category FROM assets WHERE available_quantity > 0 ORDER BY category");
    $categories = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    }
    return $categories;
}

function getAssetNameColumn($conn) {
    $columns = getTableColumns('assets', $conn);
    if (in_array('asset_name', $columns)) return 'asset_name';
    if (in_array('AssetName', $columns)) return 'AssetName';
    if (in_array('name', $columns)) return 'name';
    return 'id';
}
?>
