<?php
require_once '../includes/auth.php';
requireBorrower();

$user_id = $_SESSION['user_id'];

// Get active borrowed items
$borrowed_items = $conn->query("
    SELECT bh.*, a.asset_name, a.category, a.available_quantity
    FROM borrow_history bh 
    JOIN assets a ON bh.asset_id = a.id 
    WHERE bh.user_id = $user_id AND bh.status IN ('approved', 'borrowed', 'pending', 'return_requested')
    ORDER BY 
        CASE bh.status 
            WHEN 'return_requested' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'borrowed' THEN 3
            WHEN 'pending' THEN 4
        END,
        bh.expected_return_date ASC
");

$notif_count = getUnreadNotificationsCount($user_id, $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowed Items - <?php echo SITE_NAME; ?></title>
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
                <li class="active"><a href="my_borrowed.php"><i class="fas fa-hand-holding"></i> My Borrowed</a></li>
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
                <h2>My Borrowed Items</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
            
            <div class="section-header">
                <h3><i class="fas fa-hand-holding"></i> My Borrowed Items</h3>
                <div class="export-buttons">
                    <button onclick="printTable()" class="btn-print"><i class="fas fa-print"></i> Print</button>
                    <button onclick="exportToExcel()" class="btn-excel"><i class="fas fa-file-excel"></i> Export to Excel</button>
                </div>
            </div>
            
            <div class="table-container">
                <?php if($borrowed_items->num_rows > 0): ?>
                <table class="data-table" id="data-table">
                    <thead>
                        <tr>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Borrow Date</th>
                            <th>Expected Return</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $borrowed_items->fetch_assoc()): 
                            $today = new DateTime();
                            $expected = new DateTime($item['expected_return_date']);
                            $diff = $today->diff($expected);
                            $days_left = $expected > $today ? $diff->days : -$diff->days;
                            $is_overdue = $expected < $today && $item['status'] == 'approved';
                            
                            if($is_overdue && $item['status'] != 'overdue') {
                                $conn->query("UPDATE borrow_history SET status = 'overdue' WHERE id = " . $item['id']);
                                $item['status'] = 'overdue';
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['asset_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($item['borrow_date'])); ?></td>
                            <td class="<?php echo $is_overdue ? 'text-danger' : ''; ?>">
                                <?php echo date('M d, Y', strtotime($item['expected_return_date'])); ?>
                                <?php if(!$is_overdue && $item['status'] == 'approved'): ?>
                                    <small>(<?php echo $days_left; ?> days left)</small>
                                <?php elseif($is_overdue): ?>
                                    <small class="text-danger">(Overdue by <?php echo abs($days_left); ?> days)</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><span class="status-badge status-<?php echo $item['status']; ?>">
                                <?php 
                                    $status_labels = [
                                        'pending' => 'Pending Approval',
                                        'approved' => 'Approved',
                                        'borrowed' => 'Borrowed',
                                        'return_requested' => 'Return Requested',
                                        'overdue' => 'Overdue'
                                    ];
                                    echo $status_labels[$item['status']] ?? ucfirst($item['status']);
                                ?>
                            </span></td>
                            <td>
                                <?php if($item['status'] == 'approved' || $item['status'] == 'borrowed'): ?>
                                    <button class="btn-return" onclick="requestReturn(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-undo"></i> Request Return
                                    </button>
                                <?php elseif($item['status'] == 'pending'): ?>
                                    <button class="btn-cancel" onclick="cancelRequest(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-times"></i> Cancel Request
                                    </button>
                                <?php elseif($item['status'] == 'return_requested'): ?>
                                    <span class="text-warning">Waiting for approval</span>
                                <?php endif; ?>
                              </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-hand-holding"></i>
                    <p>You haven't borrowed any items yet.</p>
                    <a href="available_assets.php" class="btn-borrow">Browse Assets</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/borrower.js"></script>
    <script>
        function printTable() {
            const table = document.getElementById('data-table');
            if (!table) {
                alert('No data to print');
                return;
            }
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Print - My Borrowed Items</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #3b82f6; color: white; }
                        .print-header { text-align: center; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h2>ADSSU LAMS - My Borrowed Items</h2>
                        <p>Printed on: ${new Date().toLocaleString()}</p>
                        <p>User: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                    </div>
                    ${table.outerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
            printWindow.close();
        }
        
        function exportToExcel() {
            const table = document.getElementById('data-table');
            if (!table) {
                alert('No data to export');
                return;
            }
            
            // Clone the table
            const exportTable = table.cloneNode(true);
            
            // Remove the Action column (last column)
            const headers = exportTable.querySelectorAll('thead th');
            if (headers.length > 0) {
                headers[headers.length - 1].remove();
            }
            
            // Remove Action buttons from each row
            const rows = exportTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    cells[cells.length - 1].remove();
                }
            });
            
            // Create HTML table with proper formatting
            const htmlContent = `
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>My Borrowed Items</title>
                    <style>
                        th { background-color: #3b82f6; color: white; }
                        td, th { border: 1px solid #ddd; padding: 8px; }
                        table { border-collapse: collapse; width: 100%; }
                    </style>
                </head>
                <body>
                    <h2>ADSSU LAMS - My Borrowed Items</h2>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                    <p>User: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                    ${exportTable.outerHTML}
                </body>
                </html>
            `;
            
            const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute('download', `my_borrowed_items_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.xls`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            alert('Export completed successfully!');
        }
    </script>
</body>
</html>