<?php
require_once __DIR__ . '/cart.php';
header('Content-Type: application/json');

$cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : null;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : null;

if (!$cart_item_id || $qty === null) {
    echo json_encode(['status'=>'error','message'=>'Missing cart_item_id or quantity']);
    exit;
}

try {
    $res = update_cart_qty_db($cart_item_id, $qty);
    echo json_encode(['status'=>'success','result'=>$res]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>

