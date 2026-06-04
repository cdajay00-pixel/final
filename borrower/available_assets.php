<?php
require_once '../includes/auth.php';
requireBorrower();

$user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Build query
$query = "SELECT * FROM assets WHERE available_quantity > 0";
if($search) {
    $query .= " AND (asset_name LIKE '%$search%' OR category LIKE '%$search%')";
}
if($category) {
    $query .= " AND category = '$category'";
}
$query .= " ORDER BY category, asset_name";

$assets = $conn->query($query);

// Get categories
$categories = $conn->query("SELECT DISTINCT category FROM assets WHERE available_quantity > 0 ORDER BY category");
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
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
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
            
            <!-- Filters -->
            <div class="filters-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search" placeholder="Search assets..." onkeyup="searchAssets()">
                </div>
                <div class="category-filter">
                    <select id="category" onchange="filterByCategory()">
                        <option value="">All Categories</option>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category']; ?>"><?php echo $cat['category']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <!-- Assets Grid -->
            <div class="assets-grid" id="assets-grid">
                <?php while($asset = $assets->fetch_assoc()): ?>
                <div class="asset-card">
                    <div class="asset-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="asset-info">
                        <h4><?php echo htmlspecialchars($asset['asset_name']); ?></h4>
                        <p class="category"><?php echo htmlspecialchars($asset['category']); ?></p>
                        <div class="asset-stats">
                            <span>Available: <?php echo $asset['available_quantity']; ?></span>
                            <span>Total: <?php echo $asset['quantity']; ?></span>
                        </div>
                        <button class="btn-borrow" onclick="borrowAsset(<?php echo $asset['id']; ?>, '<?php echo addslashes($asset['asset_name']); ?>', <?php echo $asset['available_quantity']; ?>)">
                            <i class="fas fa-hand-holding"></i> Borrow
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Borrow Modal -->
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
        // Set min date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('expected_return_date').min = tomorrow.toISOString().split('T')[0];
    </script>
</body>
</html>