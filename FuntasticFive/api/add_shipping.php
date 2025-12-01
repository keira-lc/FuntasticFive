<?php
require_once __DIR__ . '/sql_utils.php';
header('Content-Type: application/json');

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$recipient_name = trim($_POST['recipient_name'] ?? '');
$address_line = trim($_POST['address_line'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (!$user_id || !$address_line) {
    echo json_encode(['status'=>'error','message'=>'Missing user_id or address']);
    exit;
}

try {
    $res = db_execute('INSERT INTO shipping_addresses (user_id, recipient_name, address_line, city, province, postal_code, phone) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$user_id, $recipient_name, $address_line, $city, $province, $postal_code, $phone]);
    echo json_encode(['status'=>'success','address_id'=>$res['insert_id']]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
