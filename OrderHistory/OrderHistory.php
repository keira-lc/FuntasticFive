<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please log in to view your order history.");
}

$user_id = $_SESSION['user_id'];

$host = "127.0.0.1";
$port = 3307;
$dbname = "stylenwear_db";
$username = "root";
$password = "";

$conn = new mysqli($hostname, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT product_name, quantity, total_price, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Your Order History</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Order Date</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td>$<?php echo $row['total_price']; ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>
    <br>
    <a href="index.php">Back to Home</a>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
