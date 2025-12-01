<?php
require_once __DIR__ . '/cart.php';
header('Content-Type: application/json');

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : null;
$qty = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;

if (!$user_id || !$item_id) {
    echo json_encode(['status'=>'error','message'=>'Missing user_id or item_id']);
    exit;
}

try {
    $res = add_to_cart_db($user_id, $item_id, $qty);
    echo json_encode(['status'=>'success','result'=>$res]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
