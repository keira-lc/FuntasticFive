<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Check if invoice ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Invoice ID is required");
}

$invoice_id = intval($_GET['id']);

// Connect to database
$host = 'localhost';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Cannot connect to database");
}

// Get invoice details with payment information
$sql = "SELECT 
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
            p.status as payment_status
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN shipping_addresses sa ON o.shipping_address_id = sa.address_id
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.order_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("Error: Invoice not found");
}

// Get order items with item details
$sql = "SELECT 
            oi.*,
            i.item_name,
            i.item_description,
            i.image_url,
            i.item_sku
        FROM order_items oi
        JOIN items i ON oi.item_id = i.item_id
        WHERE oi.order_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['unit_price'] * $item['quantity'];
}

$tax = $subtotal * 0.12; // 12% tax
$shipping = 50.00; // Standard shipping
$grand_total = $subtotal + $tax + $shipping;

// Generate invoice number
$invoice_number = "INV-" . date('mdY', strtotime($invoice['placed_at'])) . "-" . $invoice_id;

function e($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

// Function to get status badge class
function getStatusBadge($status) {
    $classes = [
        'pending' => 'status-pending',
        'paid' => 'status-paid',
        'shipped' => 'status-shipped',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled'
    ];
    return $classes[$status] ?? 'status-pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo $invoice_number; ?> - Style'n Wear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/jpg" href="../image/stylenwear.png">
    <style>
        :root {
            --gold: #d4af37;
            --gold-light: #f4e4a6;
            --gold-dark: #b8860b;
            --gold-glow: rgba(212, 175, 55, 0.3);
            --charcoal: #1a1a1a;
            --off-white: #f8f5f0;
            --cream: #fff8e1;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            color: var(--off-white);
            min-height: 100vh;
            padding: 20px;
        }

        /* Invoice Container */
        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .invoice-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
        }

        /* Header */
        .invoice-header {
            padding-bottom: 30px;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(212, 175, 55, 0.2);
        }

        .company-logo {
            font-family: 'Cinzel', serif;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .company-logo::after {
            content: '™';
            font-size: 1rem;
            color: var(--gold);
            vertical-align: super;
            margin-left: 5px;
        }

        .company-info {
            color: var(--gold-light);
            line-height: 1.6;
        }

        .invoice-title {
            font-family: 'Cinzel', serif;
            font-size: 2.2rem;
            color: var(--gold);
            text-align: right;
            margin-bottom: 10px;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-paid {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-shipped {
            background: rgba(13, 202, 240, 0.2);
            color: #0dcaf0;
            border: 1px solid rgba(13, 202, 240, 0.3);
        }

        .status-completed {
            background: rgba(13, 110, 253, 0.2);
            color: #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.3);
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Customer Info */
        .info-card {
            background: rgba(212, 175, 55, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-title {
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }

        /* Items Table */
        .invoice-table {
            background: rgba(212, 175, 55, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .invoice-table th {
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold);
            font-family: 'Cinzel', serif;
            padding: 15px;
            border-bottom: 2px solid var(--gold);
            font-weight: 600;
        }

        .invoice-table td {
            color: var(--off-white);
            padding: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            vertical-align: middle;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .item-name {
            color: white;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .item-sku {
            color: var(--gold-light);
            font-size: 0.85rem;
        }

        /* Totals */
        .totals-table {
            background: rgba(212, 175, 55, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .grand-total {
            border-bottom: none;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gold);
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid rgba(212, 175, 55, 0.2);
        }

        /* Footer */
        .invoice-footer {
            padding-top: 30px;
            margin-top: 30px;
            border-top: 2px solid rgba(212, 175, 55, 0.2);
            color: var(--gold-light);
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .action-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .action-btn {
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            color: var(--charcoal);
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.6);
            color: white;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
        }

        /* Print Styles */
        @media print {
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
            }
            
            .invoice-container {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                margin: 0 !important;
                padding: 20px !important;
            }
            
            .action-buttons {
                display: none !important;
            }
            
            .company-logo {
                -webkit-text-fill-color: #000 !important;
                background: none !important;
            }
            
            .invoice-table th {
                background: #f8f9fa !important;
                color: #000 !important;
            }
            
            .totals-table {
                background: #f8f9fa !important;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .invoice-container {
                padding: 20px;
            }
            
            .invoice-title {
                text-align: left;
                margin-top: 20px;
            }
            
            .action-buttons {
                position: static;
                margin-top: 20px;
                justify-content: center;
            }
            
            .invoice-table {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .company-logo {
                font-size: 2rem;
            }
            
            .invoice-title {
                font-size: 1.8rem;
            }
            
            .info-card {
                padding: 15px;
            }
            
            .invoice-table th,
            .invoice-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Invoice Container -->
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <div class="company-logo">Style'n Wear</div>
                    <div class="company-info">
                        <p class="mb-1">123 Fashion Street, Ligao City</p>
                        <p class="mb-1">Albay 4501, Philippines</p>
                        <p class="mb-1"><i class="fas fa-phone"></i> (123) 456-7890</p>
                        <p class="mb-0"><i class="fas fa-envelope"></i> info@stylenwear.com</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <h2 class="invoice-title">INVOICE</h2>
                    <div class="text-end">
                        <p class="mb-1"><strong>Invoice #:</strong> <?php echo e($invoice_number); ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($invoice['placed_at'])); ?></p>
                        <p class="mb-1"><strong>Status:</strong> 
                            <span class="status-badge <?php echo getStatusBadge($invoice['status']); ?>">
                                <?php echo ucfirst(e($invoice['status'])); ?>
                            </span>
                        </p>
                        <?php if (!empty($invoice['payment_status'])): ?>
                        <p class="mb-0"><strong>Payment:</strong> 
                            <span class="status-badge status-<?php echo e($invoice['payment_status']); ?>">
                                <?php echo ucfirst(e($invoice['payment_status'])); ?>
                            </span>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="info-title"><i class="fas fa-user me-2"></i>Bill To</h5>
                    <p class="mb-1"><strong><?php echo e($invoice['fullname']); ?></strong></p>
                    <p class="mb-1"><?php echo e($invoice['email']); ?></p>
                    <p class="mb-1">Username: <?php echo e($invoice['username']); ?></p>
                    <?php if (!empty($invoice['address_line'])): ?>
                    <p class="mb-1"><i class="fas fa-map-marker-alt"></i> <?php echo e($invoice['address_line']); ?></p>
                    <p class="mb-1"><?php echo e($invoice['city'] . ', ' . $invoice['province']); ?></p>
                    <p class="mb-0"><i class="fas fa-phone"></i> <?php echo e($invoice['phone'] ?? 'N/A'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="info-title"><i class="fas fa-file-invoice me-2"></i>Invoice Details</h5>
                    <p class="mb-1"><strong>Order ID:</strong> #<?php echo e($invoice['order_id']); ?></p>
                    <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F d, Y h:i A', strtotime($invoice['placed_at'])); ?></p>
                    <p class="mb-1"><strong>Invoice Generated:</strong> <?php echo date('F d, Y h:i A'); ?></p>
                    <?php if (!empty($invoice['payment_method'])): ?>
                    <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucfirst(e($invoice['payment_method'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <h5 class="info-title mb-3"><i class="fas fa-shopping-cart me-2"></i>Order Items</h5>
        <div class="invoice-table">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th width="50px">#</th>
                            <th>Item</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): 
                            $item_total = $item['unit_price'] * $item['quantity'];
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($item['image_url'])): ?>
                                    <img src="../<?php echo e($item['image_url']); ?>" 
                                         alt="<?php echo e($item['item_name']); ?>" 
                                         class="item-image me-3">
                                    <?php endif; ?>
                                    <div>
                                        <div class="item-name"><?php echo e($item['item_name']); ?></div>
                                        <div class="item-sku">SKU: <?php echo e($item['item_sku']); ?></div>
                                        <?php if (!empty($item['item_description'])): ?>
                                        <small class="text-muted"><?php echo e(substr($item['item_description'], 0, 100)); ?>...</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><?php echo e($item['quantity']); ?></td>
                            <td class="text-end">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="text-end text-gold">₱<?php echo number_format($item_total, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totals -->
        <div class="row justify-content-end">
            <div class="col-md-5">
                <div class="totals-table">
                    <div class="totals-row">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span>Shipping Fee:</span>
                        <span>₱<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span>Tax (12%):</span>
                        <span>₱<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="totals-row grand-total">
                        <span>GRAND TOTAL:</span>
                        <span class="text-gold">₱<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-gold mb-3"><i class="fas fa-handshake me-2"></i>Thank you for your business!</h6>
                    <p class="mb-1">For any questions regarding this invoice, please contact:</p>
                    <p class="mb-0"><strong>Style'n Wear Customer Support</strong></p>
                    <p class="mb-0"><i class="fas fa-envelope"></i> support@stylenwear.com</p>
                    <p class="mb-0"><i class="fas fa-phone"></i> (123) 456-7890</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-2"><strong>Terms & Conditions:</strong></p>
                    <p class="mb-1 small">1. All prices are in Philippine Peso (PHP)</p>
                    <p class="mb-1 small">2. Payment is due within 30 days</p>
                    <p class="mb-1 small">3. Late payments are subject to 5% monthly interest</p>
                    <p class="mb-0 small">4. Goods sold are non-refundable unless defective</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="invoice_list.php" class="action-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <button onclick="window.print()" class="action-btn">
            <i class="fas fa-print"></i> Print
        </button>
        <button onclick="downloadInvoice()" class="action-btn">
            <i class="fas fa-download"></i> Download
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Download invoice as PDF
        function downloadInvoice() {
            // In a real application, this would generate a PDF
            // For now, we'll just trigger the print dialog
            window.print();
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P for print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Escape to go back
            if (e.key === 'Escape') {
                window.location.href = 'invoice_list.php';
            }
        });

        // Auto-select invoice number on click
        document.querySelector('.invoice-number').addEventListener('click', function() {
            const range = document.createRange();
            range.selectNodeContents(this);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        });
    </script>
</body>
</html>