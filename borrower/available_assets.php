<?php
require_once '../includes/auth.php';
requireBorrower();

$user_id = (int)$_SESSION['user_id'];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

$query = "SELECT * FROM assets WHERE available_quantity > 0";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (asset_name LIKE ? OR category LIKE ?)";
    $likeSearch = "%$search%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= "ss";
}
if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
$query .= " ORDER BY category, asset_name";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$assets = $stmt->get_result();

// Get categories
$categories = $conn->query("SELECT DISTINCT category FROM assets WHERE available_quantity > 0 ORDER BY category");

$notif_count = getUnreadNotificationsCount($user_id, $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Assets - <?php echo SITE_NAME; ?></title>
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
                <li class="active"><a href="available_assets.php"><i class="fas fa-boxes"></i> Available Assets</a></li>
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
                <h2>Available Assets</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
            
            <div class="filters-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search" placeholder="Search assets..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="searchAssets()">
                </div>
                <div class="category-filter">
                    <select id="category" onchange="filterByCategory()">
                        <option value="">All Categories</option>
                        <?php if($categories): while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
            </div>
            
            <div class="assets-grid" id="assets-grid">
                <?php if($assets->num_rows > 0): while($asset = $assets->fetch_assoc()): ?>
                <div class="asset-card">
                    <div class="asset-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="asset-info">
                        <h4><?php echo htmlspecialchars($asset['asset_name']); ?></h4>
                        <p class="category"><?php echo htmlspecialchars($asset['category']); ?></p>
                        <div class="asset-stats">
                            <span>Available: <strong><?php echo $asset['available_quantity']; ?></strong></span>
                            <span>Total: <strong><?php echo $asset['quantity']; ?></strong></span>
                        </div>
                        <button class="btn-borrow" onclick="borrowAsset(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_name'], ENT_QUOTES); ?>', <?php echo $asset['available_quantity']; ?>)">
                            <i class="fas fa-hand-holding"></i> Borrow
                        </button>
                    </div>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-box-open"></i>
                    <p>No assets found matching your criteria.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="borrowModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request to Borrow</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="borrowForm" action="actions/borrow_request.php" method="POST">
                <input type="hidden" name="asset_id" id="asset_id">
                <div class="form-group">
                    <label>Asset Name</label>
                    <input type="text" id="asset_name" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Quantity (Max: <span id="max_qty"></span>)</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label>Expected Return Date</label>
                    <input type="date" name="expected_return_date" id="expected_return_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Purpose</label>
                    <textarea name="purpose" class="form-control" rows="3" required placeholder="Why do you need this asset?"></textarea>
                </div>
                <button type="submit" class="btn-submit">Submit Request</button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/borrower.js"></script>
    <script>
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dateInput = document.getElementById('expected_return_date');
        if (dateInput) {
            dateInput.min = tomorrow.toISOString().split('T')[0];
            dateInput.value = tomorrow.toISOString().split('T')[0];
        }
    </script>
</body>
</html>
