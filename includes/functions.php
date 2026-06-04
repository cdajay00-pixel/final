<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is borrower (not admin)
function isBorrower() {
    return isset($_SESSION['role']) && $_SESSION['role'] != 'Admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

// Redirect if not borrower
function requireBorrower() {
    requireLogin();
    if (!isBorrower()) {
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

// Get column names from a table
function getTableColumns($table_name, $conn) {
    $columns = [];
    $result = $conn->query("DESCRIBE $table_name");
    if ($result) {
        while($col = $result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
    }
    return $columns;
}

// Get user statistics with error handling
function getUserStats($user_id, $conn) {
    $stats = [
        'total_borrowed' => 0,
        'active_borrowed' => 0,
        'overdue' => 0,
        'total_returned' => 0
    ];
    
    // Check if borrow_history table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'borrow_history'");
    if (!$table_check || $table_check->num_rows == 0) {
        return $stats;
    }
    
    // Get status column name
    $columns = getTableColumns('borrow_history', $conn);
    $status_col = in_array('status', $columns) ? 'status' : (in_array('Status', $columns) ? 'Status' : 'status');
    
    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id");
    if ($result) {
        $stats['total_borrowed'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND $status_col IN ('approved', 'borrowed')");
    if ($result) {
        $stats['active_borrowed'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND $status_col = 'overdue'");
    if ($result) {
        $stats['overdue'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND $status_col = 'returned'");
    if ($result) {
        $stats['total_returned'] = $result->fetch_assoc()['count'];
    }
    
    return $stats;
}

// Get unread notifications count with error handling
function getUnreadNotificationsCount($user_id, $conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if (!$table_check || $table_check->num_rows == 0) {
        return 0;
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    if ($result) {
        return $result->fetch_assoc()['count'];
    }
    return 0;
}

// Add notification with error handling
function addNotification($user_id, $message, $type, $conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if (!$table_check || $table_check->num_rows == 0) {
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $message, $type);
        return $stmt->execute();
    }
    return false;
}

// Check if user can borrow more items
function canBorrowMore($user_id, $conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'borrow_history'");
    if (!$table_check || $table_check->num_rows == 0) {
        return true;
    }
    
    $columns = getTableColumns('borrow_history', $conn);
    $status_col = in_array('status', $columns) ? 'status' : (in_array('Status', $columns) ? 'Status' : 'status');
    
    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND $status_col IN ('approved', 'borrowed')");
    if ($result) {
        $current_borrowed = $result->fetch_assoc()['count'];
        return $current_borrowed < MAX_BORROW_ITEMS;
    }
    return true;
}

// Get remaining borrow slots
function getRemainingBorrowSlots($user_id, $conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'borrow_history'");
    if (!$table_check || $table_check->num_rows == 0) {
        return MAX_BORROW_ITEMS;
    }
    
    $columns = getTableColumns('borrow_history', $conn);
    $status_col = in_array('status', $columns) ? 'status' : (in_array('Status', $columns) ? 'Status' : 'status');
    
    $result = $conn->query("SELECT COUNT(*) as count FROM borrow_history WHERE user_id = $user_id AND $status_col IN ('approved', 'borrowed')");
    if ($result) {
        $current_borrowed = $result->fetch_assoc()['count'];
        return MAX_BORROW_ITEMS - $current_borrowed;
    }
    return MAX_BORROW_ITEMS;
}

// Get asset categories
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

// Get asset name column
function getAssetNameColumn($conn) {
    $columns = getTableColumns('assets', $conn);
    if (in_array('asset_name', $columns)) return 'asset_name';
    if (in_array('AssetName', $columns)) return 'AssetName';
    if (in_array('name', $columns)) return 'name';
    return 'id';
}
?>