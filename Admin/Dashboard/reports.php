<?php
session_start();


// Optional DB
$dbAvailable = false;
if (file_exists(__DIR__ . "/config.php")) {
    require_once __DIR__ . "/config.php";
    if (isset($pdo) && $pdo instanceof PDO) $dbAvailable = true;
}

// Helper escape
function e($s){ return htmlspecialchars($s, ENT_QUOTES,'UTF-8'); }

// --- SAMPLE DATA (fallback if no database is connected) ---
if (!$dbAvailable) {
    $itemsToSell = [
        ['name' => 'Product A', 'price' => 120, 'stock' => 15],
        ['name' => 'Product B', 'price' => 90, 'stock' => 8],
    ];

    $itemsOrdered = [
        ['order_id'=>101,'item'=>'Product A','qty'=>2,'date'=>'2025-01-10'],
        ['order_id'=>102,'item'=>'Product B','qty'=>1,'date'=>'2025-01-11'],
    ];

    $adminSales = [
        ['admin'=>'Alice','item'=>'Product A','qty'=>3,'total'=>360],
        ['admin'=>'Bob','item'=>'Product B','qty'=>1,'total'=>90],
    ];

    $inventory = [
        ['item'=>'Product A','stock'=>15,'sold'=>30,'remaining'=>15],
        ['item'=>'Product B','stock'=>8,'sold'=>12,'remaining'=>8],
    ];
}
// ------------------------------------------------------------
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="content" style="margin-left:220px; padding:20px;">
    <h2>Reports</h2>
    <p class="text-muted">Select a report category below.</p>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sell">Items to Sell</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ordered">Items Ordered</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#sales">Admin Sales</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventory">Inventory Report</button></li>
    </ul>

    <div class="tab-content mt-3">

        <!-- ITEMS TO SELL -->
        <div class="tab-pane fade show active" id="sell">
            <div class="card">
                <div class="card-header">Items to Sell</div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>Item</th><th>Price</th><th>Stock</th></tr></thead>
                        <tbody>
                            <?php foreach ($itemsToSell as $i): ?>
                            <tr>
                                <td><?= e($i['name']) ?></td>
                                <td><?= e($i['price']) ?></td>
                                <td><?= e($i['stock']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ITEMS ORDERED -->
        <div class="tab-pane fade" id="ordered">
            <div class="card">
                <div class="card-header">Items Ordered</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Order ID</th><th>Item</th><th>Qty</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($itemsOrdered as $o): ?>
                            <tr>
                                <td><?= e($o['order_id']) ?></td>
                                <td><?= e($o['item']) ?></td>
                                <td><?= e($o['qty']) ?></td>
                                <td><?= e($o['date']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ADMIN SALES -->
        <div class="tab-pane fade" id="sales">
            <div class="card">
                <div class="card-header">Admin Sales</div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>Admin</th><th>Item</th><th>Qty</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($adminSales as $s): ?>
                            <tr>
                                <td><?= e($s['admin']) ?></td>
                                <td><?= e($s['item']) ?></td>
                                <td><?= e($s['qty']) ?></td>
                                <td><?= e($s['total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- INVENTORY REPORT -->
        <div class="tab-pane fade" id="inventory">
            <div class="card">
                <div the="card-header">Inventory Report</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Item</th><th>Stock</th><th>Sold</th><th>Remaining</th></tr></thead>
                        <tbody>
                            <?php foreach ($inventory as $inv): ?>
                            <tr>
                                <td><?= e($inv['item']) ?></td>
                                <td><?= e($inv['stock']) ?></td>
                                <td><?= e($inv['sold']) ?></td>
                                <td><?= e($inv['remaining']) ?></td>
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
