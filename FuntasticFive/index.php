<?php
require_once __DIR__ . '/sql_utils.php';

$products = db_query_all('SELECT item_id, item_name, item_price, stock, image_url FROM items ORDER BY item_id ASC');
$USER_ID = 1;
$addresses = db_query_all('SELECT * FROM shipping_addresses WHERE user_id = ?', [$USER_ID]);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Style 'n Wear</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="cartstyle.css?v=<?=time()?>">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Shop Products</h2>
      <button id="toggleCartBtn" class="btn btn-pink">
          <i class="bi bi-cart"></i> View Cart
          <span id="cartBadge" class="badge bg-danger rounded-pill">0</span>
      </button>
  </div>

  <div class="products-grid mb-5">
    <?php foreach ($products as $product): ?>
      <div class="card-product">
        <img src="<?=htmlspecialchars($product['image_url'])?>" alt="<?=htmlspecialchars($product['item_name'])?>">
        <div class="p-3 d-flex flex-column">
          <h5 title="<?=htmlspecialchars($product['item_name'])?>"><?=htmlspecialchars($product['item_name'])?></h5>
          <p>Stock: <?=intval($product['stock'])?></p>
          <p>₱<?=number_format($product['item_price'],2)?></p>
          <div class="d-flex gap-2 mt-auto">
            <input type="number" class="form-control qty-input" value="1" min="1" max="<?=intval($product['stock'])?>">
            <button class="btn-pink add-btn" data-id="<?=$product['item_id']?>">
              <i class="bi bi-cart-plus"></i> Add
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- CART + SHIPPING + PAYMENT + ORDER HISTORY -->
  <div id="cartSection" style="display:none;">
    
    <!-- CART TABLE -->
    <div class="section-box mb-4">
      <h3>Your Cart</h3>
      <div class="table-responsive">
        <table class="table table-striped" id="cartTable">
          <thead>
            <tr>
              <th>Product</th>
              <th>Price</th>
              <th>Qty</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- SHIPPING + PAYMENT -->
    <div class="section-box row mb-4">
      <!-- SHIPPING -->
      <div class="col-md-6 mb-3">
        <h4>Shipping Address</h4>
        <select id="shipping_select" class="form-select mb-2">
          <option value="">-- Choose saved address --</option>
          <?php foreach ($addresses as $a): ?>
            <option value="<?=intval($a['address_id'])?>">
              <?=htmlspecialchars($a['recipient_name'].' - '.$a['address_line'].', '.$a['city'].', '.$a['province'])?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-pink btn-sm mb-3" id="showNewShip">Add New Address</button>

        <div id="newShip" style="display:none;">
          <input class="form-control mb-1" id="recipient_name" placeholder="Recipient name">
          <input class="form-control mb-1" id="address_line" placeholder="Address">
          <input class="form-control mb-1" id="city" placeholder="City">
          <input class="form-control mb-1" id="province" placeholder="Province">
          <input class="form-control mb-1" id="postal_code" placeholder="Postal code">
          <input class="form-control mb-1" id="phone" placeholder="Phone">
          <button class="btn btn-pink btn-sm" id="saveAddr">Save Address</button>
        </div>
      </div>

      <!-- PAYMENT -->
      <div class="col-md-6 mb-3">
        <h4>Payment</h4>
        <select id="payment_method" class="form-select mb-2">
          <option value="COD">Cash on Delivery</option>
          <option value="GCASH">GCash</option>
          <option value="BANK">Bank Transfer</option>
        </select>
        <input class="form-control mb-2" id="txn_ref" placeholder="Transaction reference (if any)">
        <button class="btn btn-pink w-100" id="placeOrder">Place Order</button>
      </div>
    </div>

    <!-- ORDER HISTORY -->
    <div class="section-box">
      <h4>Your Orders</h4>
      <div id="orders"></div>
    </div>

  </div>

</div>

<script>
const USER_ID = <?=json_encode($USER_ID)?>;

// ------------------ CART ------------------
$('#toggleCartBtn').on('click', function(){
    $('#cartSection').slideToggle();
    $('.products-grid').slideToggle();
});

// Load cart items
function loadCart(){
    $.get('api/view_cart.php', {user_id: USER_ID}, function(res){
        let items = typeof res==='string'?JSON.parse(res):res;
        let tbody = $('#cartTable tbody'); tbody.html('');
        if(!items.length){
            tbody.append('<tr><td colspan="5" class="text-center">Cart is empty</td></tr>');
            $('#cartBadge').text(0); return;
        }
        let totalQty=0; let total=0;
        items.forEach(it=>{
            totalQty+=parseInt(it.quantity);
            total+=parseFloat(it.subtotal);
            tbody.append(`<tr>
<td>${it.item_name}</td>
<td>₱${parseFloat(it.item_price).toFixed(2)}</td>
<td><input type="number" class="form-control qty" value="${it.quantity}" min="1" data-cart-id="${it.cart_item_id}" style="width:70px;"></td>
<td>₱${parseFloat(it.subtotal).toFixed(2)}</td>
<td><button class="btn btn-danger btn-sm remove" data-cart-id="${it.cart_item_id}"><i class="bi bi-trash"></i></button></td>
</tr>`);
        });
        tbody.append(`<tr><td colspan="3"><strong>Total</strong></td><td colspan="2">₱${total.toFixed(2)}</td></tr>`);
        $('#cartBadge').text(totalQty);
    });
}

// Add to cart
$(document).on('click', '.add-btn', function(){
    const id = $(this).data('id');
    const qty = $(this).siblings('input.qty-input').val() || 1;
    $.post('api/add_to_cart.php', {user_id: USER_ID, item_id: id, qty: qty}, function(res){
        res = typeof res==='string'?JSON.parse(res):res;
        if(res.status==='success'){ alert('Added to cart!'); loadCart(); }
        else alert('Error adding to cart');
    });
});

// Update quantity
$(document).on('change', '.qty', function(){
    const cartId = $(this).data('cart-id');
    const qty = $(this).val();
    $.post('api/update_cart.php', {cart_item_id: cartId, qty: qty}, loadCart);
});

// Remove item
$(document).on('click', '.remove', function(){
    const cartId = $(this).data('cart-id');
    $.post('api/remove_from_cart.php', {cart_item_id: cartId}, loadCart);
});

// ------------------ SHIPPING ------------------
$('#showNewShip').on('click', () => $('#newShip').toggle());
$('#saveAddr').on('click', function(){
    const data = {
        user_id: USER_ID,
        recipient_name: $('#recipient_name').val(),
        address_line: $('#address_line').val(),
        city: $('#city').val(),
        province: $('#province').val(),
        postal_code: $('#postal_code').val(),
        phone: $('#phone').val()
    };
    $.post('api/shipping.php', data, function(res){
        res = typeof res==='string'?JSON.parse(res):res;
        if(res.status==='success'){
            alert('Address saved!');
            $('#shipping_select').append(`<option value="${res.address_id}">${data.recipient_name} - ${data.address_line}, ${data.city}</option>`).val(res.address_id);
            $('#newShip').hide();
        } else alert(res.message || 'Failed to save address');
    });
});

// ------------------ PLACE ORDER + PAYMENT ------------------
$('#placeOrder').on('click', function(){
    const shipping_id = $('#shipping_select').val();
    const method = $('#payment_method').val();
    const txn_ref = $('#txn_ref').val();
    if(!shipping_id) return alert('Please select or add a shipping address');
    $.post('api/checkout.php', {user_id: USER_ID, shipping_address_id: shipping_id}, function(res){
        res = typeof res==='string'?JSON.parse(res):res;
        if(res.status==='success'){
            $.post('api/payments_api.php', {
                order_id: res.order_id,
                amount: res.total,
                method: method,
                transaction_ref: txn_ref
            }, function(pay){
                pay = typeof pay==='string'?JSON.parse(pay):pay;
                if(pay.status==='success'){
                    alert('Order placed successfully!');
                    loadCart();
                    loadOrders();
                } else alert('Payment failed: '+pay.message);
            });
        } else alert('Order failed: '+res.message);
    });
});

$(document).ready(function(){ loadCart(); loadOrders(); });
</script>

</body>
</html>
