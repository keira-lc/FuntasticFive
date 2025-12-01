<?php
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/sql_utils.php';

/**
 * Place order safely
 */
function place_order($user_id, $shipping_address_id = null){
    $items = get_cart_items_db($user_id);
    if (empty($items)) return ['success'=>false, 'error'=>'Cart is empty'];

    $total = 0;
    foreach ($items as $it){
        if ((int)$it['stock'] < (int)$it['quantity']){
            return ['success'=>false, 'error'=>'Not enough stock for: '.$it['item_name']];
        }
        $total += (float)$it['item_price'] * (int)$it['quantity'];
    }

    try {
        db_begin();

        // Insert order
        $order_res = db_execute(
            "INSERT INTO orders (user_id, shipping_address_id, total_amount, status) VALUES (?, ?, ?, 'pending')",
            [$user_id, $shipping_address_id, $total]
        );
        $order_id = $order_res['insert_id'];

        foreach ($items as $it){
            $qty = (int)$it['quantity'];
            $unit_price = (float)$it['item_price'];

            // Insert order items
            db_execute(
                'INSERT INTO order_items (order_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)',
                [$order_id, $it['item_id'], $qty, $unit_price]
            );

            // Lock item row and update stock
            $item = db_query_one("SELECT stock FROM items WHERE item_id = ? FOR UPDATE", [$it['item_id']]);
            if ((int)$item['stock'] < $qty) throw new Exception('Stock update failed for '.$it['item_name']);

            db_execute('UPDATE items SET stock = stock - ? WHERE item_id = ?', [$qty, $it['item_id']]);
        }

        // Clear cart
        clear_cart_db($user_id);

        db_commit();
        return ['success'=>true, 'order_id'=>$order_id, 'total'=>$total];

    } catch (Exception $ex){
        db_rollback();
        return ['success'=>false, 'error'=>$ex->getMessage()];
    }
}
?>
