<?php
require_once __DIR__ . '/sql_utils.php';
require_once __DIR__ . '/cart.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
if (!$user_id) { echo "Invalid user!"; exit; }
$addresses = db_query_all("SELECT * FROM shipping_addresses WHERE user_id = ?", [$user_id]);

$items = get_cart_items_db($user_id);
$total = 0;
foreach ($items as &$it) {
    $it['subtotal'] = $it['item_price'] * $it['quantity'];
    $total += $it['subtotal'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Summary</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background: #f8f9fa; }
.summary-box { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 3px 12px rgba(0,0,0,0.1); }
.section-title { font-weight: 600; margin-bottom: 10px; }
.cart-item { border-bottom: 1px solid #eee; padding: 10px 0; }
@media(max-width: 576px) {
    .cart-item div { text-align: center; }
}
</style>
</head>
<body>

<div class="container py-4">
    <h2 class="mb-4 text-center">Order Summary</h2>

    <div class="summary-box mb-4">

        <h5 class="section-title">Shipping Address</h5>
        <select id="shipping_id" class="form-select mb-3">
            <?php foreach($addresses as $a): ?>
                <option value="<?= $a['address_id'] ?>">
                    <?= htmlspecialchars($a['recipient_name']) ?> - 
                    <?= htmlspecialchars($a['address_line']) ?>, 
                    <?= htmlspecialchars($a['city']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <h5 class="section-title mt-4">Cart Items</h5>

        <?php foreach($items as $c): ?>
            <div class="row cart-item">
                <div class="col-6"><?= htmlspecialchars($c['item_name']) ?></div>
                <div class="col-2">x<?= $c['quantity'] ?></div>
                <div class="col-4 text-end">₱<?= number_format($c['subtotal'], 2) ?></div>
            </div>
        <?php endforeach; ?>

        <h4 class="text-end mt-3">Total: ₱<?= number_format($total, 2) ?></h4>

        <h5 class="section-title mt-4">Payment Method</h5>
        <select id="payment_method" class="form-select">
            <option value="cash_on_delivery">Cash on Delivery</option>
            <option value="gcash">GCash</option>
            <option value="card">Credit/Debit Card</option>
        </select>

        <div class="mt-4 text-end">
            <button class="btn btn-success px-4" onclick="placeOrder()">
                Place Order
            </button>
        </div>
    </div>
</div>

<script>
function placeOrder() {
    const shipping_id = document.getElementById("shipping_id").value;
    const method = document.getElementById("payment_method").value;

    fetch("place_order.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            user_id: <?= $user_id ?>,
            shipping_address_id: shipping_id,
            payment_method: method
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            window.location = "invoice.php?order_id=" + data.order_id;
        } else {
            alert("Error placing order.");
        }
    });
}
</script>

</body>
</html>
