<?php
require_once '../includes/auth.php';
requireBorrower();

$user_id = (int)$_SESSION['user_id'];
$stats = getUserStats($user_id, $conn);

$tables_exist = $conn->query("SHOW TABLES LIKE 'borrow_history'")->num_rows > 0;

$borrowed_items = null;
if ($tables_exist) {
    $columns = $conn->query("DESCRIBE borrow_history");
    $borrow_columns = [];
    while($col = $columns->fetch_assoc()) {
        $borrow_columns[] = $col['Field'];
    }
    
    $asset_cols = $conn->query("DESCRIBE assets");
    $asset_columns = [];
    while($col = $asset_cols->fetch_assoc()) {
        $asset_columns[] = $col['Field'];
    }
    
    $asset_name_col = in_array('asset_name', $asset_columns) ? 'asset_name' : (in_array('AssetName', $asset_columns) ? 'AssetName' : 'name');
    $category_col = in_array('category', $asset_columns) ? 'category' : (in_array('Category', $asset_columns) ? 'Category' : 'cat');
    $status_col = in_array('status', $borrow_columns) ? 'status' : (in_array('Status', $borrow_columns) ? 'Status' : 'borrow_status');
    
    $borrowed_items = $conn->query("
        SELECT bh.*, a.$asset_name_col as asset_name, a.$category_col as category 
        FROM borrow_history bh 
        JOIN assets a ON bh.asset_id = a.id 
        WHERE bh.user_id = $user_id AND bh.$status_col IN ('approved', 'borrowed')
        ORDER BY bh.borrow_date DESC LIMIT 5
    ");
}

$available_count = 0;
$assets_table_exists = $conn->query("SHOW TABLES LIKE 'assets'")->num_rows > 0;
if ($assets_table_exists) {
    $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE available_quantity > 0");
    if ($result) {
        $available_count = (int)$result->fetch_assoc()['count'];
    }
}

$notif_count = getUnreadNotificationsCount($user_id, $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/borrower-style.css">
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3><?php echo SITE_NAME; ?></h3>
                <p>Borrower Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li class="active"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="available_assets.php"><i class="fas fa-boxes"></i> Available Assets</a></li>
                <li><a href="my_borrowed.php"><i class="fas fa-hand-holding"></i> My Borrowed</a></li>
                <li><a href="borrow_history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications 
                    <?php if($notif_count > 0): ?>
                        <span class="badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        
        <div class="main-content">
            <div class="top-bar">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Dashboard</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                    <div class="stat-info">
                        <h3>Total Borrowed</h3>
                        <div class="stat-number"><?php echo $stats['total_borrowed']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-hand-holding"></i></div>
                    <div class="stat-info">
                        <h3>Currently Borrowed</h3>
                        <div class="stat-number"><?php echo $stats['active_borrowed']; ?></div>
                        <small class="text-muted">Remaining slots: <?php echo getRemainingBorrowSlots($user_id, $conn); ?>/<?php echo MAX_BORROW_ITEMS; ?></small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3>Overdue</h3>
                        <div class="stat-number"><?php echo $stats['overdue']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3>Returned</h3>
                        <div class="stat-number"><?php echo $stats['total_returned']; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-hand-holding"></i> Currently Borrowed</h3>
                    <a href="my_borrowed.php" class="view-all">View All →</a>
                </div>
                <div class="table-container">
                    <?php if($borrowed_items && $borrowed_items->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Asset</th><th>Category</th><th>Borrow Date</th><th>Expected Return</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php while($item = $borrowed_items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['asset_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($item['borrow_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($item['expected_return_date'])); ?></td>
                                <td><span class="status-badge status-<?php echo $item[$status_col] ?? $item['status']; ?>"><?php echo ucfirst($item[$status_col] ?? $item['status']); ?></span></td>
                                <td><button class="btn-return" onclick="requestReturn(<?php echo $item['id']; ?>)">Return</button></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-hand-holding"></i>
                        <p>No borrowed items.</p>
                        <a href="available_assets.php" class="btn-borrow">Browse Assets</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/borrower.js"></script>
</body>
</html>
