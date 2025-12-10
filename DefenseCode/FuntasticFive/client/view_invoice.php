<?php
session_start();

// Check if client is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Order ID is required");
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Database connection
$host = '127.0.0.1';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get order details - verify it belongs to the logged-in user
$sql = "
    SELECT 
        o.*,
        u.fullname,
        u.email,
        u.username,
        sa.recipient_name,
        sa.address_line,
        sa.city,
        sa.province,
        sa.postal_code,
        sa.phone,
        p.method as payment_method,
        p.status as payment_status,
        p.amount as payment_amount,
        p.transaction_ref,
        p.paid_at
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN shipping_addresses sa ON o.shipping_address_id = sa.address_id
    LEFT JOIN payments p ON o.order_id = p.order_id
    WHERE o.order_id = ? AND o.user_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Error: Order not found or you don't have permission to view it");
}

// Get order items
$sql = "
    SELECT 
        oi.*,
        i.item_name,
        i.item_description,
        i.item_price as original_price,
        i.image_url
    FROM order_items oi
    JOIN items i ON oi.item_id = i.item_id
    WHERE oi.order_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['unit_price'] * $item['quantity'];
}

// Determine payment status based on order status if payment status is NULL
$payment_status = $order['payment_status'];
if (!$payment_status) {
    // Set payment status based on order status
    switch($order['status']) {
        case 'pending':
            $payment_status = 'pending';
            break;
        case 'paid':
            $payment_status = 'paid';
            break;
        case 'completed':
        case 'shipped':
            // If order is shipped or completed, payment should be paid
            $payment_status = 'paid';
            break;
        case 'cancelled':
            $payment_status = 'cancelled';
            break;
        default:
            $payment_status = 'pending';
    }
}

// Generate invoice number
$invoice_number = "INV-" . date('Ymd', strtotime($order['placed_at'])) . "-" . str_pad($order_id, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice_number; ?> - Style'n Wear</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
    body {
        background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
        font-family: "Poppins", sans-serif;
        color: var(--off-white);
        padding-top: 20px;
        padding-bottom: 20px;
    }
    
    .invoice-container {
        max-width: 900px;
        margin: 0 auto;
        background: linear-gradient(145deg, #1e1e1e, #151515);
        padding: 40px;
        border-radius: 20px;
        border: 2px solid var(--gold);
        box-shadow: 0 15px 50px rgba(212, 175, 55, 0.2);
    }
    
    .invoice-header {
        border-bottom: 3px solid var(--gold);
        padding-bottom: 25px;
        margin-bottom: 30px;
    }
    
    .company-info {
        color: var(--off-white);
        line-height: 1.6;
    }
    
    .status-badge {
        padding: 6px 18px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .status-pending { 
        background: linear-gradient(45deg, #ffc107, #ff9800);
        color: black; 
    }
    
    .status-paid { 
        background: linear-gradient(45deg, #198754, #2dce89);
        color: white; 
    }
    
    .status-shipped { 
        background: linear-gradient(45deg, #0dcaf0, #17a2b8);
        color: black; 
    }
    
    .status-completed { 
        background: linear-gradient(45deg, var(--gold), var(--gold-light));
        color: var(--charcoal); 
    }
    
    .status-cancelled { 
        background: linear-gradient(45deg, #dc3545, #ff6b6b);
        color: white; 
    }
    
    .section-card {
        background: rgba(30, 30, 30, 0.9);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        color: var(--off-white);
    }
    
    .table-custom {
        background: transparent;
        border-color: rgba(212, 175, 55, 0.3);
        color: var(--off-white);
    }
    
    .table-custom th {
        background: rgba(212, 175, 55, 0.15);
        border-color: rgba(212, 175, 55, 0.3);
        color: var(--gold-light);
        font-weight: 600;
    }
    
    .table-custom td {
        border-color: rgba(212, 175, 55, 0.2);
        color: var(--off-white);
        background: transparent;
    }
    
    .table-custom tbody tr:hover {
        background: rgba(212, 175, 55, 0.08);
    }
    
    .badge-custom {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .item-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid var(--gold);
    }
    
    .total-highlight {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--gold);
    }
    
    /* Ensure all text is readable */
    .customer-info p,
    .payment-info .row div,
    .section-card p,
    .section-card div:not(.gold-text) {
        color: var(--off-white) !important;
    }
    
    /* Item description text */
    .table-custom tbody tr td small {
        color: #ccc !important;
    }
    
    /* Simple fix for Order Summary table */
    .table-sm.table-borderless {
        background: transparent !important;
    }
    
    .table-sm.table-borderless tr {
        background: transparent !important;
    }
    
    .table-sm.table-borderless td {
        background: transparent !important;
        color: var(--off-white) !important;
        padding: 6px 0;
    }
    
    .table-sm.table-borderless td:first-child {
        color: #ccc !important;
    }
    
    .table-sm.table-borderless td:last-child {
        color: var(--gold-light) !important;
        font-weight: 600;
        text-align: right;
    }
    
    .table-sm.table-borderless tr.border-top {
        border-top: 1px solid rgba(212, 175, 55, 0.3) !important;
    }
    
    .table-sm.table-borderless tr.border-top td:first-child {
        color: var(--gold) !important;
        font-weight: 700;
    }
    
    .table-sm.table-borderless tr.border-top td:last-child {
        color: var(--gold) !important;
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    /* Print styles */
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background: white !important;
            color: black !important;
            font-family: Arial, sans-serif !important;
        }
        .invoice-container {
            box-shadow: none !important;
            margin: 0 !important;
            padding: 20px !important;
            border: 1px solid #ddd !important;
            background: white !important;
            color: black !important;
            border-radius: 0 !important;
        }
        .section-card {
            background: white !important;
            border: 1px solid #ddd !important;
            color: black !important;
        }
        .table-custom {
            border-color: #ddd !important;
            color: black !important;
        }
        .table-custom th {
            background: #f8f9fa !important;
            border-color: #ddd !important;
            color: #333 !important;
        }
        .table-custom td {
            border-color: #ddd !important;
            color: #333 !important;
            background: white !important;
        }
        .table-custom tbody tr td small {
            color: #666 !important;
        }
        .gold-text {
            color: #333 !important;
            background: none !important;
            -webkit-text-fill-color: #333 !important;
        }
        .status-badge,
        .badge-custom {
            background: #f8f9fa !important;
            color: #333 !important;
            border: 1px solid #ddd !important;
        }
        .customer-info p,
        .payment-info .row div,
        .section-card p,
        .section-card div {
            color: #333 !important;
        }
        
        .table-sm.table-borderless td {
            color: #333 !important;
        }
        .table-sm.table-borderless td:first-child {
            color: #555 !important;
        }
        .table-sm.table-borderless td:last-child {
            color: #222 !important;
        }
        .table-sm.table-borderless tr.border-top {
            border-top-color: #333 !important;
        }
        .table-sm.table-borderless tr.border-top td:first-child {
            color: #000 !important;
        }
        .table-sm.table-borderless tr.border-top td:last-child {
            color: #000 !important;
        }
    }
</style>
</head>
<body>
    <!-- Navigation for non-print -->
    <nav class="luxury-header no-print" style="position: relative; margin-bottom: 30px;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Brand -->
                <a href="my_orders.php" class="brand-title gold-text">
                    <i class="fas fa-arrow-left me-2"></i>
                    Style<span style="color: var(--gold)">'n</span>Wear
                </a>
                
                <div>
                    <button onclick="window.print()" class="btn" style="background: var(--gold); color: var(--charcoal);">
                        <i class="fas fa-print me-2"></i> Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <h1 class="gold-text mb-2">Style'n Wear</h1>
                    <div class="company-info">
                        <p class="mb-1">
                            <i class="fas fa-map-marker-alt me-2" style="color: var(--gold);"></i>
                            123 Fashion Street, Style District
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-city me-2" style="color: var(--gold);"></i>
                            Manila, Philippines 1000
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-phone me-2" style="color: var(--gold);"></i>
                            +63 2 8123 4567
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2" style="color: var(--gold);"></i>
                            info@stylenwear.com
                        </p>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <h2 class="gold-text mb-3">INVOICE</h2>
                    <p class="mb-1"><strong style="color: var(--gold-light);">Invoice #:</strong> <?php echo $invoice_number; ?></p>
                    <p class="mb-1"><strong style="color: var(--gold-light);">Date:</strong> <?php echo date('F d, Y', strtotime($order['placed_at'])); ?></p>
                    <p class="mb-0">
                        <strong style="color: var(--gold-light);">Status:</strong> 
                        <span class="status-badge status-<?php echo $order['status']; ?> ms-2">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Customer & Order Info -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="section-card">
                    <h5 class="gold-text mb-3"><i class="fas fa-user me-2"></i>Bill To:</h5>
                    <div class="customer-info">
                        <p class="mb-2">
                            <strong style="color: var(--gold-light);"><?php echo htmlspecialchars($order['fullname']); ?></strong>
                        </p>
                        <p class="mb-1" style="color: #aaa;">
                            <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($order['email']); ?>
                        </p>
                        <?php if ($order['address_line']): ?>
                        <p class="mb-1" style="color: #aaa;">
                            <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($order['address_line']); ?>
                        </p>
                        <p class="mb-1" style="color: #aaa;">
                            <i class="fas fa-city me-2"></i><?php echo htmlspecialchars($order['city'] . ', ' . $order['province']); ?>
                        </p>
                        <p class="mb-0" style="color: #aaa;">
                            <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <h5 class="gold-text mb-3"><i class="fas fa-credit-card me-2"></i>Payment Information:</h5>
                    <div class="payment-info">
                        <div class="row mb-2">
                            <div class="col-6" style="color: #aaa;">Order Total:</div>
                            <div class="col-6 text-end total-highlight">
                                ₱<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6" style="color: #aaa;">Currency:</div>
                            <div class="col-6 text-end" style="color: #aaa;"><?php echo $order['currency']; ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6" style="color: #aaa;">Payment Method:</div>
                            <div class="col-6 text-end" style="color: #aaa;">
                                <?php echo ucfirst($order['payment_method'] ?? 'Cash on Delivery'); ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6" style="color: #aaa;">Payment Status:</div>
                            <div class="col-6 text-end">
                                <span class="badge-custom" style="background: 
                                    <?php 
                                        if ($payment_status == 'paid') echo 'rgba(45, 206, 137, 0.2)';
                                        elseif ($payment_status == 'cancelled') echo 'rgba(255, 107, 107, 0.2)';
                                        elseif ($payment_status == 'refunded') echo 'rgba(13, 202, 240, 0.2)';
                                        else echo 'rgba(255, 193, 7, 0.2)';
                                    ?>; 
                                    color: 
                                    <?php 
                                        if ($payment_status == 'paid') echo '#2dce89';
                                        elseif ($payment_status == 'cancelled') echo '#ff6b6b';
                                        elseif ($payment_status == 'refunded') echo '#0dcaf0';
                                        else echo '#ffc107';
                                    ?>;">
                                    <?php echo ucfirst($payment_status); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($order['paid_at']): ?>
                        <div class="row mb-2">
                            <div class="col-6" style="color: #aaa;">Payment Date:</div>
                            <div class="col-6 text-end" style="color: #aaa;">
                                <?php echo date('F d, Y h:i A', strtotime($order['paid_at'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($order['transaction_ref']): ?>
                        <div class="row">
                            <div class="col-6" style="color: #aaa;">Transaction Ref:</div>
                            <div class="col-6 text-end" style="color: #aaa;">
                                <?php echo htmlspecialchars($order['transaction_ref']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="section-card mb-4">
            <h5 class="gold-text mb-3"><i class="fas fa-boxes me-2"></i>Order Items</h5>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="10%"></th>
                            <th width="35%">Item Description</th>
                            <th width="15%" class="text-center">Quantity</th>
                            <th width="15%" class="text-end">Unit Price</th>
                            <th width="20%" class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $item_counter = 1;
                        foreach ($items as $item): 
                            $item_total = $item['unit_price'] * $item['quantity'];
                            $image_url = !empty($item['image_url']) ? $item['image_url'] : 'default.jpg';
                            $image_filename = basename($image_url);
                        ?>
                        <tr>
                            <td><?php echo $item_counter++; ?></td>
                            <td>
                                <img src="../image/<?php echo htmlspecialchars($image_filename); ?>" 
                                     class="item-image" 
                                     alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                            </td>
                            <td>
                                <strong style="color: var(--off-white);"><?php echo htmlspecialchars($item['item_name']); ?></strong><br>
                                <small style="color: #777;"><?php echo htmlspecialchars(substr($item['item_description'], 0, 100)); ?>...</small>
                            </td>
                            <td class="text-center" style="color: var(--off-white);"><?php echo $item['quantity']; ?></td>
                            <td class="text-end" style="color: var(--gold-light);">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="text-end" style="color: var(--gold); font-weight: 600;">₱<?php echo number_format($item_total, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totals -->
        <div class="row mb-4">
            <div class="col-md-8 mb-4 mb-md-0">
                <div class="section-card">
                    <h6 class="gold-text mb-2"><i class="fas fa-info-circle me-2"></i>Important Notes</h6>
                    <div style="color: #aaa; font-size: 0.9rem;">
                        <p class="mb-2">• This invoice is automatically generated for your order.</p>
                        <p class="mb-2">• Please keep this invoice for your records.</p>
                        <p class="mb-2">• For any questions or concerns regarding your order, contact our customer support.</p>
                        <p class="mb-0">• Returns and exchanges are subject to our return policy (available on our website).</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="section-card">
                    <h6 class="gold-text mb-3"><i class="fas fa-calculator me-2"></i>Order Summary</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end">₱<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Shipping:</td>
                            <td class="text-end">FREE</td>
                        </tr>
                        <tr>
                            <td>Tax (12% VAT):</td>
                            <td class="text-end">₱<?php echo number_format($subtotal * 0.12, 2); ?></td>
                        </tr>
                        <tr class="border-top" style="border-color: var(--gold) !important;">
                            <td style="color: var(--gold-light); font-weight: 600;">GRAND TOTAL:</td>
                            <td class="text-end total-highlight">
                                ₱<?php echo number_format($order['total_amount'], 2); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Status Summary -->
        <div class="section-card mb-4">
            <h6 class="gold-text mb-3"><i class="fas fa-clipboard-check me-2"></i>Order Status Summary</h6>
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="text-center">
                        <div class="mb-2">
                            <i class="fas fa-shopping-bag fa-2x" style="color: var(--gold);"></i>
                        </div>
                        <p class="mb-1" style="color: #aaa;">Order Status</p>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="text-center">
                        <div class="mb-2">
                            <i class="fas fa-credit-card fa-2x" style="color: 
                                <?php 
                                    if ($payment_status == 'paid') echo '#2dce89';
                                    elseif ($payment_status == 'cancelled') echo '#ff6b6b';
                                    elseif ($payment_status == 'refunded') echo '#0dcaf0';
                                    else echo '#ffc107';
                                ?>;">
                            </i>
                        </div>
                        <p class="mb-1" style="color: #aaa;">Payment Status</p>
                        <span class="badge-custom" style="background: 
                            <?php 
                                if ($payment_status == 'paid') echo 'rgba(45, 206, 137, 0.2)';
                                elseif ($payment_status == 'cancelled') echo 'rgba(255, 107, 107, 0.2)';
                                elseif ($payment_status == 'refunded') echo 'rgba(13, 202, 240, 0.2)';
                                else echo 'rgba(255, 193, 7, 0.2)';
                            ?>; 
                            color: 
                            <?php 
                                if ($payment_status == 'paid') echo '#2dce89';
                                elseif ($payment_status == 'cancelled') echo '#ff6b6b';
                                elseif ($payment_status == 'refunded') echo '#0dcaf0';
                                else echo '#ffc107';
                            ?>;">
                            <?php echo ucfirst($payment_status); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="mb-2">
                            <i class="fas fa-money-check-alt fa-2x" style="color: var(--gold);"></i>
                        </div>
                        <p class="mb-1" style="color: #aaa;">Amount Due</p>
                        <?php if ($payment_status == 'paid'): ?>
                        <span style="color: #2dce89; font-weight: 600;">
                            ₱0.00 <small class="d-block">(Fully Paid)</small>
                        </span>
                        <?php else: ?>
                        <span class="total-highlight d-block">
                            ₱<?php echo number_format($order['total_amount'], 2); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="row pt-4 border-top" style="border-color: rgba(212, 175, 55, 0.3);">
            <div class="col-md-6">
                <h6 class="gold-text mb-2">Thank you for shopping with Style'n Wear!</h6>
                <p style="color: #777; font-size: 0.9rem;">
                    Your satisfaction is our priority.<br>
                    We hope to see you again soon!
                </p>
            </div>
            <div class="col-md-6 text-end">
                <p style="color: #777; font-size: 0.9rem;">
                    <span class="d-block">Invoice ID: <?php echo $invoice_number; ?></span>
                    <span class="d-block">Order ID: <?php echo $order['order_id']; ?></span>
                    <span class="d-block">Generated: <?php echo date('F d, Y h:i A'); ?></span>
                </p>
            </div>
        </div>
        
        <!-- Watermark -->
        <div class="text-center mt-5 pt-3" style="border-top: 1px dashed rgba(212, 175, 55, 0.2);">
            <p style="color: rgba(212, 175, 55, 0.3); font-size: 0.8rem;">
                STYLE'N WEAR - OFFICIAL INVOICE - ORDER #<?php echo $order_id; ?>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>