<?php
require_once __DIR__ . '/checkout.php';
header('Content-Type: application/json');

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$shipping_address_id = isset($_POST['shipping_address_id']) ? (int)$_POST['shipping_address_id'] : null;

if (!$user_id) {
    echo json_encode(['status'=>'error','message'=>'Missing user_id']);
    exit;
}

$result = place_order($user_id, $shipping_address_id);

if ($result['success']) {
    echo json_encode(['status'=>'success','order_id'=>$result['order_id'],'total'=>$result['total']]);
} else {
    echo json_encode(['status'=>'error','message'=>$result['error']]);
}
?>
