<?php
require_once __DIR__ . '/sql_utils.php';

$USER_ID = 1; // Replace with session later
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Your Orders</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="cartstyle.css?v=<?php echo time(); ?>">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

</head>

<body class="bg-light">

<div class="container py-4">

  <h2 class="mb-4">Order History</h2>

  <div class="section-box">
    <div class="table-responsive">
      <table class="table table-hover align-middle" id="orderTable">
        <thead class="table-dark">
          <tr>
            <th>Order ID</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Payment</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <!-- ORDER SUMMARY -->
  <div class="modal fade" id="orderSummaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">Order Summary</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body" id="orderSummaryBody">
          Loading...
        </div>
      </div>
    </div>
  </div>

</div>

<script>
const USER_ID = <?= $USER_ID ?>;

// Load order history
function loadOrders() {
    $.get('api/view_orders.php', {user_id: USER_ID}, function(res){
        let orders = (typeof res === "string") ? JSON.parse(res) : res;
        let tbody = $("#orderTable tbody");
        tbody.html("");

        if (!orders.length) {
            tbody.html(`<tr><td colspan="6" class="text-center">No orders found</td></tr>`);
            return;
        }

        orders.forEach(o => {
            let payStatus = o.payments.length 
                ? o.payments[0].status 
                : "Unpaid";

            tbody.append(`
                <tr>
                    <td>#${o.order_id}</td>
                    <td>₱${parseFloat(o.total_amount).toFixed(2)}</td>
                    <td><span class="badge bg-primary">${o.status}</span></td>
                    <td>${o.placed_at}</td>
                    <td>${payStatus}</td>
                    <td>
                        <button class="btn btn-pink btn-sm view-summary" data-id="${o.order_id}">
                            View Summary
                        </button>
                    </td>
                </tr>
            `);
        });
    });
}

// Show order summary
$(document).on("click", ".view-summary", function(){
    let id = $(this).data("id");

    $.get('api/view_orders.php', {user_id: USER_ID}, function(res){
        let orders = (typeof res === "string") ? JSON.parse(res) : res;
        let order = orders.find(o => o.order_id == id);

        if (!order) return;

        let itemsHTML = order.items.map(it => `
            <tr>
                <td>${it.item_name}</td>
                <td>${it.quantity}</td>
                <td>₱${parseFloat(it.unit_price).toFixed(2)}</td>
                <td>₱${parseFloat(it.subtotal).toFixed(2)}</td>
            </tr>
        `).join('');

        let payHTML = order.payments.map(p => `
            <tr>
                <td>${p.method}</td>
                <td>₱${parseFloat(p.amount).toFixed(2)}</td>
                <td>${p.status}</td>
                <td>${p.transaction_ref ?? '—'}</td>
                <td>${p.created_at}</td>
            </tr>
        `).join('');

        $("#orderSummaryBody").html(`
            <h5>Order #${order.order_id}</h5>
            <p><strong>Date:</strong> ${order.placed_at}</p>
            <p><strong>Status:</strong> ${order.status}</p>
            <p><strong>Shipping:</strong> ${order.address_line}, ${order.city}, ${order.province}</p>

            <hr>

            <h6>Items</h6>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
                </thead>
                <tbody>${itemsHTML}</tbody>
            </table>

            <h6>Payments</h6>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr><th>Method</th><th>Amount</th><th>Status</th><th>Reference</th><th>Date</th></tr>
                </thead>
                <tbody>${payHTML}</tbody>
            </table>

            <hr>
            <h5>Total: ₱${parseFloat(order.total_amount).toFixed(2)}</h5>

            <button class="btn btn-dark w-100">Download Invoice</button>
        `);

        new bootstrap.Modal(document.getElementById('orderSummaryModal')).show();
    });
});

$(document).ready(loadOrders);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
