<?php
require_once __DIR__ . '/payments.php';
require_once __DIR__ . '/sql_utils.php';
header('Content-Type: application/json');

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$method = trim($_POST['method'] ?? '');
$transaction_ref = trim($_POST['transaction_ref'] ?? null);

if (!$order_id || $amount <= 0 || !$method) {
    echo json_encode(['status'=>'error','message'=>'Missing required parameters']);
    exit;
}

try {
    $res = add_payment($order_id, $amount, $method, $transaction_ref);
    db_execute('UPDATE orders SET status = ? WHERE order_id = ?', ['paid', $order_id]);
    echo json_encode(['status'=>'success','payment_id'=>$res['insert_id']]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
