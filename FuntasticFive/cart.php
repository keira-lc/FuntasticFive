<?php
require_once __DIR__ . '/sql_utils.php';

function ensure_cart_for_user($user_id){
    $row = db_query_one('SELECT cart_id FROM carts WHERE user_id = ?', [$user_id]);
    if ($row) return (int)$row['cart_id'];
    $res = db_execute('INSERT INTO carts (user_id) VALUES (?)', [$user_id]);
    return (int)$res['insert_id'];
}

function get_cart_items_db($user_id){
    $cart = db_query_one('SELECT cart_id FROM carts WHERE user_id = ?', [$user_id]);
    if (!$cart) return [];
    $cart_id = (int)$cart['cart_id'];
    return db_query_all(
        'SELECT ci.cart_item_id, ci.cart_id, ci.item_id, ci.quantity, i.item_name, i.item_price, i.stock
         FROM cart_items ci
         JOIN items i ON i.item_id = ci.item_id
         WHERE ci.cart_id = ?',
        [$cart_id]
    );
}

function add_to_cart_db($user_id, $item_id, $qty){
    $qty = (int)$qty;
    if ($qty <= 0) throw new Exception('Quantity must be at least 1');

    $cart_id = ensure_cart_for_user($user_id);

    db_begin(); // start transaction
    $item = db_query_one("SELECT stock FROM items WHERE item_id = ? FOR UPDATE", [$item_id]); // lock row
    if (!$item) {
        db_rollback();
        throw new Exception("Item not found");
    }

    if ($qty > (int)$item['stock']) {
        db_rollback();
        throw new Exception("Not enough stock");
    }

    // Check existing cart item
    $existing = db_query_one("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND item_id = ?", [$cart_id, $item_id]);

    if ($existing) {
        $new_qty = $existing['quantity'] + $qty;
        if ($new_qty > (int)$item['stock']) {
            db_rollback();
            throw new Exception("Quantity exceeds stock");
        }
        $res = db_execute("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?", [$new_qty, $existing['cart_item_id']]);
    } else {
        $res = db_execute("INSERT INTO cart_items (cart_id, item_id, quantity) VALUES (?, ?, ?)", [$cart_id, $item_id, $qty]);
    }

    db_commit();
    return $res;
}

function update_cart_qty_db($cart_item_id, $qty){
    $qty = (int)$qty;
    if ($qty <= 0) return remove_from_cart_db($cart_item_id);
    return db_execute('UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?', [$qty, $cart_item_id]);
}

function remove_from_cart_db($cart_item_id){
    return db_execute('DELETE FROM cart_items WHERE cart_item_id = ?', [$cart_item_id]);
}
function clear_cart_db($user_id){
    $cart = db_query_one('SELECT cart_id FROM carts WHERE user_id = ?', [$user_id]);
    if (!$cart) return ['success'=>true];
    return db_execute('DELETE FROM cart_items WHERE cart_id = ?', [(int)$cart['cart_id']]);
}
?>
