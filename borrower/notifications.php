<?php
require_once '../includes/auth.php';
requireBorrower();

$user_id = (int)$_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}

// Auto-generate notifications for status changes detected in borrow_history
$borrow_records = $conn->query("
    SELECT bh.id, bh.status, a.asset_name, bh.borrow_date
    FROM borrow_history bh
    JOIN assets a ON bh.asset_id = a.id
    WHERE bh.user_id = $user_id
    ORDER BY bh.borrow_date DESC
");

$seen_status = [];
while ($row = $borrow_records->fetch_assoc()) {
    $key = $row['id'] . '_' . $row['status'];
    if (isset($seen_status[$key])) continue;
    $seen_status[$key] = true;

    $asset_name = $row['asset_name'];
    $status = $row['status'];

    // Check if notification for this status already exists
    $check = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND message LIKE ?");
    $likeMsg = "%{$asset_name}%{$status}%";
    $check->bind_param("is", $user_id, $likeMsg);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows == 0) {
        $message = '';
        $type = 'info';

        switch ($status) {
            case 'approved':
                $message = "Your borrow request for {$asset_name} has been approved! You can now pick up the item.";
                $type = 'success';
                break;
            case 'denied':
                $message = "Your borrow request for {$asset_name} has been denied.";
                $type = 'error';
                break;
            case 'returned':
                $message = "Your return for {$asset_name} has been confirmed. Item returned successfully.";
                $type = 'success';
                break;
            case 'overdue':
                $message = "The item {$asset_name} is now overdue. Please return it as soon as possible.";
                $type = 'warning';
                break;
        }

        if ($message) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("iss", $user_id, $message, $type);
            $stmt->execute();
        }
    }
}

// Get notifications
$notifications = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC
");

$notif_count = getUnreadNotificationsCount($user_id, $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo SITE_NAME; ?></title>
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="available_assets.php"><i class="fas fa-boxes"></i> Available Assets</a></li>
                <li><a href="my_borrowed.php"><i class="fas fa-hand-holding"></i> My Borrowed</a></li>
                <li><a href="borrow_history.php"><i class="fas fa-history"></i> History</a></li>
                <li class="active"><a href="notifications.php"><i class="fas fa-bell"></i> Notifications
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
                <h2>Notifications</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-bell"></i> All Notifications</h3>
                    <a href="?mark_all_read=1" class="view-all" onclick="return confirm('Mark all notifications as read?')">Mark all as read</a>
                </div>
                
                <?php if($notifications->num_rows > 0): ?>
                    <div class="notifications-list" id="notifications-list">
                        <?php while($notif = $notifications->fetch_assoc()): ?>
                        <div class="notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="notification-item-left" onclick="markAsRead(<?php echo $notif['id']; ?>)">
                                <div class="notification-message">
                                    <?php 
                                    $icon = 'info-circle';
                                    $color = '';
                                    if ($notif['type'] == 'success') { $icon = 'check-circle'; $color = 'color: #22c55e;'; }
                                    elseif ($notif['type'] == 'error') { $icon = 'times-circle'; $color = 'color: #ef4444;'; }
                                    elseif ($notif['type'] == 'warning') { $icon = 'exclamation-triangle'; $color = 'color: #f59e0b;'; }
                                    elseif ($notif['type'] == 'return_request') { $icon = 'undo'; $color = 'color: #3b82f6;'; }
                                    elseif ($notif['type'] == 'cancelled') { $icon = 'ban'; $color = 'color: #ef4444;'; }
                                    ?>
                                    <i class="fas fa-<?php echo $icon; ?>" style="<?php echo $color; ?> margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </div>
                                <div class="notification-date">
                                    <i class="far fa-clock"></i> <?php echo date('F d, Y h:i A', strtotime($notif['created_at'])); ?>
                                </div>
                            </div>
                            <button class="notif-delete" onclick="deleteNotification(<?php echo $notif['id']; ?>)" title="Delete notification"><i class="fas fa-trash"></i></button>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/borrower.js"></script>
</body>
</html>
