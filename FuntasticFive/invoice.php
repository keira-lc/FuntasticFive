<?php
require_once __DIR__ . '/sql_utils.php';
require_once __DIR__ . '/payments.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
if (!$order_id) {
    echo "Invalid order ID.";
    exit;
}

// Fetch order details
$order = db_query_one('SELECT o.*, u.fullname, u.email, sa.recipient_name, sa.address_line, sa.city, sa.province, sa.postal_code, sa.phone
                       FROM orders o
                       JOIN users u ON u.user_id = o.user_id
                       LEFT JOIN shipping_addresses sa ON sa.address_id = o.shipping_address_id
                       WHERE o.order_id = ?', [$order_id]);

if (!$order) {
    echo "Order not found.";
    exit;
}

// Fetch order items
$items = db_query_all('SELECT oi.*, i.item_name 
                       FROM order_items oi 
                       JOIN items i ON i.item_id = oi.item_id 
                       WHERE oi.order_id = ?', [$order_id]);

// Fetch payments
$payments = get_order_payments($order_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice #<?= $order['order_id'] ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; font-family: Arial, sans-serif; }
.invoice-box { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 3px 15px rgba(0,0,0,0.1); margin: 30px auto; max-width: 800px; }
.invoice-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.invoice-header h2 { margin: 0; }
.invoice-details, .payment-details { margin-bottom: 20px; }
.table th, .table td { vertical-align: middle !important; }
@media (max-width: 576px) {
    .invoice-header { flex-direction: column; text-align: center; gap: 10px; }
}
</style>
</head>
<body>
<div class="invoice-box">
    <div class="invoice-header">
        <h2>Invoice #<?= $order['order_id'] ?></h2>
        <small>Placed on: <?= date("M d, Y H:i", strtotime($order['placed_at'])) ?></small>
    </div>

    <div class="row invoice-details">
        <div class="col-md-6">
            <h5>Customer Info</h5>
            <p>
                <?= htmlspecialchars($order['fullname']) ?><br>
                <?= htmlspecialchars($order['email']) ?>
            </p>
        </div>
        <div class="col-md-6">
            <h5>Shipping Address</h5>
            <p>
                <?= htmlspecialchars($order['recipient_name'] ?? $order['fullname']) ?><br>
                <?= htmlspecialchars($order['address_line'] ?? 'N/A') ?><br>
                <?= htmlspecialchars($order['city'] ?? '') ?>, <?= htmlspecialchars($order['province'] ?? '') ?> <?= htmlspecialchars($order['postal_code'] ?? '') ?><br>
                <?= htmlspecialchars($order['phone'] ?? '') ?>
            </p>
        </div>
    </div>

    <h5>Order Items</h5>
    <div class="table-responsive mb-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= htmlspecialchars($it['item_name']) ?></td>
                    <td>₱<?= number_format($it['unit_price'], 2) ?></td>
                    <td><?= intval($it['quantity']) ?></td>
                    <td>₱<?= number_format($it['unit_price'] * $it['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total:</th>
                    <th>₱<?= number_format($order['total_amount'], 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="row payment-details">
        <div class="col-md-6">
            <h5>Payment Status</h5>
            <?php if (!empty($payments)): ?>
                <?php foreach ($payments as $p): ?>
                    <p>
                        Method: <?= htmlspecialchars($p['method']) ?><br>
                        Amount: ₱<?= number_format($p['amount'], 2) ?><br>
                        Status: <?= htmlspecialchars($p['status']) ?><br>
                        Ref: <?= htmlspecialchars($p['transaction_ref'] ?? '-') ?><br>
                        Paid At: <?= $p['paid_at'] ? date("M d, Y H:i", strtotime($p['paid_at'])) : 'Pending' ?>
                    </p>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Pending</p>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-end">
            <h5>Order Status</h5>
            <p><?= ucfirst($order['status']) ?></p>
        </div>
    </div>
</div>
</body>
</html>
