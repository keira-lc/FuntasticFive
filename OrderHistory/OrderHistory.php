<?php
// Step 1: Connect to database
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "stylenwear";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 2: Start session and verify login
session_start();
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = intval($_SESSION['user_id']); 

/* IMPORTANT:
   Your table does NOT have user_id.
   Replace 'user_id' below with the correct column name from your database.
   Example: customer_id, client_id, userid, etc.
*/

// <-- CHANGE THIS COLUMN NAME -->
$user_column = "customer_id"; // Replace with your actual column name!

// Step 3: Query orders for this user
$sql = "SELECT order_id, items AS product_name, quantity, total_price, order_date 
        FROM order_history 
        WHERE user_id = $user_id
        ORDER BY order_date";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Order History</title>
    <link rel="stylesheet" href="stylesheetoh.css">
</head>
<body>

<div class="order-history-container">
    <h2>Order History</h2>

    <?php
    // Step 4: Display results
    if ($result && $result->num_rows > 0) {
        echo "<table class='order-table'>
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>";
                
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td data-label='Order ID'>{$row['order_id']}</td>
                    <td data-label='Product'>{$row['product_name']}</td>
                    <td data-label='Quantity'>{$row['quantity']}</td>
                    <td data-label='Total Price'>₱{$row['total_price']}</td>
                    <td data-label='Date'>{$row['order_date']}</td>
                  </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p>No orders found.</p>";
    }

    $conn->close();
    ?>
</div>

</body>
</html>
