<?php
session_start();

// If already logged in, redirect to home (optional)
if (isset($_SESSION['user_id'])) {
    header('Location: client/Index6.php');
    exit();
}

// Database connection
$dbAvailable = false;
$itemsToSell = $itemsOrdered = $adminSales = $inventory = [];

try {
    // REAL database settings from your working shop code
    $host = "127.0.0.1";
    $port = 3307;
    $dbname = "stylenwear";
    $username = "root";
    $password = "";

    // PDO Connection
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbAvailable = true;

    /*******************************
     *  ITEMS TO SELL (from products table)
     ********************************/
    $stmt = $pdo->query("
        SELECT 
            id,
            title AS item_name,
            price AS item_price,
            image,
            1 AS stock
        FROM products
        ORDER BY id ASC
    ");
    $itemsToSell = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     *  ITEMS ORDERED */
    $itemsOrdered = [];

    /*
     *  ADMIN SALES (Empty until you add orders) */
    $adminSales = [];

    /*
     *  INVENTORY (based on products table)**/
    $stmt = $pdo->query("
        SELECT 
            title AS item,
            1 AS stock,
            0 AS sold,
            1 AS remaining
        FROM products
        ORDER BY id ASC
    ");
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $dbAvailable = false;
}

// Helper escape function
function e($s){ return htmlspecialchars($s, ENT_QUOTES,'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Reports - Style n Wear</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .content {
        margin-left: 20px;
        padding: 20px;
    }
    .nav-tabs .nav-link.active {
        font-weight: bold;
        background-color: #fff;
    }
    .table th {
        background-color: #f8f9fa;
    }
    img.item-img {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 6px;
        margin-right: 8px;
    }
</style>
</head>

<body class="bg-light">

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Style n Wear - Reports</h2>
            <p class="text-muted">Real-time data from your database</p>
        </div>
        <div class="text-end">
            <?php if($dbAvailable): ?>
                <span class="badge bg-success">Database Connected</span>
            <?php else: ?>
                <span class="badge bg-danger">Database Connection Failed</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sell">
                Items to Sell (<?= count($itemsToSell) ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ordered">
                Items Ordered (<?= count($itemsOrdered) ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ordered">
                Sales Performance (<?= count($itemsOrdered) ?>)
            </button>
        </li>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventory">
                Inventory Report
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3">

        <!-- ITEMS TO SELL -->
        <div class="tab-pane fade show active" id="sell">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Items Available for Sale</span>
                    <small class="text-muted">Total: <?= count($itemsToSell) ?> items</small>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price (PHP)</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($itemsToSell)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No items available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($itemsToSell as $item): ?>
                                <tr>
                                    <td>
                                        <img src="<?= e($item['image']) ?>" class="item-img">
                                        <?= e($item['item_name']) ?>
                                    </td>
                                    <td>₱<?= number_format($item['item_price'], 2) ?></td>
                                    <td><span class="badge bg-success">1</span></td>
                                    <td><span class="badge bg-success">In Stock</span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ITEMS ORDERED -->
        <div class="tab-pane fade" id="ordered">
            <div class="card">
                <div class="card-header">Items Ordered</div>
                <div class="card-body">
                    <p class="text-muted text-center mb-0">No orders tracking system found.</p>
                </div>
            </div>
        </div>

        <!-- SALES -->
        <div class="tab-pane fade" id="sales">
            <div class="card">
                <div class="card-header">Sales Performance</div>
                <div class="card-body">
                    <p class="text-muted text-center mb-0">No sales data available.</p>
                </div>
            </div>
        </div>

        <!-- INVENTORY -->
        <div class="tab-pane fade" id="inventory">
            <div class="card">
                <div class="card-header">Inventory Report</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Stock</th>
                                <th>Sold</th>
                                <th>Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?= e($item['item']) ?></td>
                                <td>1</td>
                                <td>0</td>
                                <td>1</td>
                                <td><span class="badge bg-success">Good</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
