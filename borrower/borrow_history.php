<?php
require_once '../includes/auth.php';
requireBorrower();

$user_id = (int)$_SESSION['user_id'];

$history = $conn->query("
    SELECT bh.*, a.asset_name, a.category 
    FROM borrow_history bh 
    JOIN assets a ON bh.asset_id = a.id 
    WHERE bh.user_id = $user_id 
    ORDER BY bh.borrow_date DESC
");

$notif_count = getUnreadNotificationsCount($user_id, $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow History - <?php echo SITE_NAME; ?></title>
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
                <li class="active"><a href="borrow_history.php"><i class="fas fa-history"></i> History</a></li>
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
                <h2>Borrow History</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
            
            <div class="section-header">
                <h3><i class="fas fa-history"></i> Borrow History</h3>
                <div class="export-buttons">
                    <button onclick="printHistory()" class="btn-print"><i class="fas fa-print"></i> Print</button>
                    <button onclick="exportHistoryToExcel()" class="btn-excel"><i class="fas fa-file-excel"></i> Export to Excel</button>
                </div>
            </div>
            
            <div class="table-container">
                <?php if($history->num_rows > 0): ?>
                <table class="data-table" id="history-table">
                    <thead>
                        <tr>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Borrow Date</th>
                            <th>Expected Return</th>
                            <th>Actual Return</th>
                            <th>Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['asset_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($item['borrow_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($item['expected_return_date'])); ?></td>
                            <td><?php echo $item['actual_return_date'] ? date('M d, Y', strtotime($item['actual_return_date'])) : '-'; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><span class="status-badge status-<?php echo $item['status']; ?>">
                                <?php echo ucfirst($item['status']); ?>
                            </span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No borrow history found.</p>
                    <a href="available_assets.php" class="btn-borrow">Browse Assets</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/borrower.js"></script>
    <script>
        function printHistory() {
            const table = document.getElementById('history-table');
            if (!table) {
                alert('No data to print');
                return;
            }
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Print - Borrow History</title>
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
                        <h2>ADSSU LAMS - Borrow History</h2>
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
        
        function exportHistoryToExcel() {
            const table = document.getElementById('history-table');
            if (!table) {
                alert('No data to export');
                return;
            }
            
            let csvData = [];
            const headers = [];
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach(th => {
                headers.push(th.innerText.trim());
            });
            csvData.push(headers);
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    rowData.push(cell.innerText.trim());
                });
                csvData.push(rowData);
            });
            
            let csvContent = csvData.map(row => 
                row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',')
            ).join('\n');
            
            const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute('download', `borrow_history_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            alert('Export completed successfully!');
        }
    </script>
</body>
</html>