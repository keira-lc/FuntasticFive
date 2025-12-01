<?php
require_once __DIR__ . '/sql_utils.php';

function add_payment($order_id, $amount, $method, $transaction_ref = null){
    return db_execute(
        'INSERT INTO payments (order_id, amount, method, status, transaction_ref, paid_at) 
         VALUES (?, ?, ?, "paid", ?, NOW())',
        [$order_id, $amount, $method, $transaction_ref]
    );
}
function get_order_payments($order_id){
    return db_query_all('SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC', [$order_id]);
}
?>
