<?php
require_once __DIR__ . '/sql_utils.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

/*
    Fetch all orders for the user
    JOIN shipping address to show details 
*/
$orders = db_query_all(
    'SELECT 
        o.order_id,
        o.user_id,
        o.shipping_address_id,
        o.total_amount,
        o.currency,
        o.status,
        o.placed_at,
        o.updated_at,
        sa.recipient_name,
        sa.address_line,
        sa.city,
        sa.province,
        sa.postal_code,
        sa.phone
     FROM orders o
     LEFT JOIN shipping_addresses sa 
        ON sa.address_id = o.shipping_address_id
     WHERE o.user_id = ?
     ORDER BY o.placed_at DESC',
    [$user_id]
);

/*
    For each order, fetch:
    - Ordered items
    - Payments history
*/
foreach ($orders as &$o) {

    // ORDER ITEMS
    $o['items'] = db_query_all(
        'SELECT 
            oi.order_item_id,
            oi.item_id,
            oi.quantity,
            oi.unit_price,
            oi.subtotal,
            i.item_name,
            i.image_url
         FROM order_items oi
         JOIN items i ON i.item_id = oi.item_id
         WHERE oi.order_id = ?',
        [$o['order_id']]
    );

    // PAYMENTS
    $o['payments'] = db_query_all(
        'SELECT 
            payment_id,
            order_id,
            amount,
            method,
            status,
            transaction_ref,
            paid_at,
            created_at
         FROM payments
         WHERE order_id = ?
         ORDER BY created_at DESC',
        [$o['order_id']]
    );
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($orders);
?>
