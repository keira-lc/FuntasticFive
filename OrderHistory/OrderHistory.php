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

// Step 2: Assume logged-in user ID (for demo purposes)
session_start();
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = intval($_SESSION['user_id']); 

// Step 3: Query orders for this user
$sql = "SELECT order_id, items AS product_name, quantity, total_price, order_date 
        FROM order_history 
        WHERE user_id = $user_id 
        ORDER BY order_date";

$result = $conn->query($sql);

// Step 4: Display results
echo "<h2>Order History</h2>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>
            <tr>
              <th>Order ID</th>
              <th>Product</th>
              <th>Quantity</th>
              <th>Total Price</th>
              <th>Date</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['order_id']}</td>
                <td>{$row['product_name']}</td>
                <td>{$row['quantity']}</td>
                <td>{$row['total_price']}</td>
                <td>{$row['order_date']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No orders found.";
}

$conn->close();
?>
