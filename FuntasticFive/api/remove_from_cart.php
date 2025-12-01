<?php
require_once __DIR__ . '/cart.php';
header('Content-Type: application/json');

$cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : null;

if (!$cart_item_id) {
    echo json_encode(['status'=>'error','message'=>'Missing cart_item_id']);
    exit;
}

try {
    $res = remove_from_cart_db($cart_item_id);
    echo json_encode(['status'=>'success','result'=>$res]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>

