<?php
require_once '../includes/auth_admin.php';
requireAdmin();

$pending_borrows = $conn->query("
    SELECT bh.*, a.asset_name, a.category, u.full_name, u.username
    FROM borrow_history bh
    JOIN assets a ON bh.asset_id = a.id
    JOIN users u ON bh.user_id = u.id
    WHERE bh.status = 'pending'
    ORDER BY bh.created_at ASC
");

$return_requests = $conn->query("
    SELECT bh.*, a.asset_name, a.category, u.full_name, u.username
    FROM borrow_history bh
    JOIN assets a ON bh.asset_id = a.id
    JOIN users u ON bh.user_id = u.id
    WHERE bh.status = 'return_requested'
    ORDER BY bh.created_at ASC
");

$overdue_items = $conn->query("
    SELECT bh.*, a.asset_name, a.category, u.full_name, u.username
    FROM borrow_history bh
    JOIN assets a ON bh.asset_id = a.id
    JOIN users u ON bh.user_id = u.id
    WHERE bh.status = 'approved' AND bh.expected_return_date < CURDATE()
    ORDER BY bh.expected_return_date ASC
");

$stats = [
    'pending' => $pending_borrows->num_rows,
    'returns' => $return_requests->num_rows,
    'overdue' => $overdue_items->num_rows,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/borrower-style.css">
    <style>
        .admin-header { background: linear-gradient(135deg, #1e3a8a, #0f172a); }
        .admin-badge { background: #6366f1; }
        .btn-approve { background: #22c55e; color: white; border: none; border-radius: 6px; padding: 8px 16px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; }
        .btn-approve:hover { background: #16a34a; transform: translateY(-1px); }
        .btn-deny { background: #ef4444; color: white; border: none; border-radius: 6px; padding: 8px 16px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; }
        .btn-deny:hover { background: #dc2626; transform: translateY(-1px); }
        .btn-confirm { background: #3b82f6; color: white; border: none; border-radius: 6px; padding: 8px 16px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; }
        .btn-confirm:hover { background: #2563eb; transform: translateY(-1px); }
        .action-group { display: flex; gap: 6px; flex-wrap: wrap; }
        .stat-card-admin .stat-number { font-size: 32px; }
        .toast { position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 14px 20px; border-radius: 10px; font-size: 14px; font-weight: 500; animation: slideIn 0.3s ease; }
        .toast-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
        .toast-error { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-header" style="border-bottom-color: #6366f1;">
                <h3 style="color: #818cf8;"><?php echo SITE_NAME; ?></h3>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li class="active"><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="main-content">
            <div class="top-bar">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Admin Dashboard</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="toast toast-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['err'])): ?>
                <div class="toast toast-error"><i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($_GET['err']); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3>Pending Approvals</h3>
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-undo"></i></div>
                    <div class="stat-info">
                        <h3>Return Requests</h3>
                        <div class="stat-number"><?php echo $stats['returns']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info">
                        <h3>Overdue Items</h3>
                        <div class="stat-number"><?php echo $stats['overdue']; ?></div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-clock"></i> Pending Borrow Requests</h3>
                </div>
                <div class="table-container">
                    <?php if ($pending_borrows->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Borrower</th><th>Asset</th><th>Category</th><th>Quantity</th><th>Purpose</th><th>Date</th><th>Expected Return</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $pending_borrows->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><small class="text-muted">@<?php echo htmlspecialchars($row['username']); ?></small></td>
                                <td><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($row['purpose'] ?: '-'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['expected_return_date'])); ?></td>
                                <td>
                                    <div class="action-group">
                                        <button class="btn-approve" onclick="action(<?php echo $row['id']; ?>, 'approve')"><i class="fas fa-check"></i> Approve</button>
                                        <button class="btn-deny" onclick="action(<?php echo $row['id']; ?>, 'deny')"><i class="fas fa-times"></i> Deny</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-check-circle"></i><p>No pending requests.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-undo"></i> Return Requests</h3>
                </div>
                <div class="table-container">
                    <?php if ($return_requests->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Borrower</th><th>Asset</th><th>Category</th><th>Quantity</th><th>Borrow Date</th><th>Expected Return</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $return_requests->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><small class="text-muted">@<?php echo htmlspecialchars($row['username']); ?></small></td>
                                <td><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['borrow_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['expected_return_date'])); ?></td>
                                <td>
                                    <div class="action-group">
                                        <button class="btn-confirm" onclick="action(<?php echo $row['id']; ?>, 'confirm_return')"><i class="fas fa-check-double"></i> Confirm Return</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-check-circle"></i><p>No return requests.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Overdue Items</h3>
                </div>
                <div class="table-container">
                    <?php if ($overdue_items->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Borrower</th><th>Asset</th><th>Category</th><th>Borrow Date</th><th>Expected Return</th><th>Overdue By</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $overdue_items->fetch_assoc()): 
                                $overdue_days = (new DateTime())->diff(new DateTime($row['expected_return_date']))->days;
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['borrow_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['expected_return_date'])); ?></td>
                                <td class="text-danger"><strong><?php echo $overdue_days; ?> days</strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-check-circle"></i><p>No overdue items.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/borrower.js"></script>
    <script>
        function action(id, type) {
            const labels = { approve: 'approve', deny: 'deny', confirm_return: 'confirm return' };
            if (!confirm(`Are you sure you want to ${labels[type]} this request?`)) return;

            fetch('action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id + '&action=' + type
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) location.reload();
                else alert(d.message);
            })
            .catch(e => alert('Error: ' + e));
        }
    </script>
</body>
</html>
