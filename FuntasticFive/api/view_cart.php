<?php
require_once __DIR__ . '/cart.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if(!$user_id){
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$items = get_cart_items_db($user_id);
foreach($items as &$it){
    $it['subtotal'] = (float)$it['item_price'] * (int)$it['quantity'];
}

header('Content-Type: application/json');
echo json_encode($items);
?>

