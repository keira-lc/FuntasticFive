<?php
// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "stylenwear";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = intval($_SESSION['user_id']);

// GET order ID from URL
if (!isset($_GET['order_id'])) {
    die("Order ID missing.");
}
$order_id = intval($_GET['order_id']);

$user_column = "user_id";

$sql = "SELECT order_id, items, quantity, total_price, order_date 
        FROM order_history 
        WHERE order_id = $order_id AND $user_column = $user_id";

$result = $conn->query($sql);
$order  = $result->fetch_assoc();

if (!$order) {
    die("Order not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?php echo $order['order_id']; ?></title>
    <link rel="stylesheet" href="invoice.css">
</head>
<body>

<div class="invoice-container">

    <div class="header">
        <h1>Receipt / Invoice</h1>
    </div>

    <div class="store-info">
        <strong>Style N Wear</strong><br>
        Manila, Philippines<br>
        Phone: 0912-345-6789<br>
        Email: support@stylenwear.com
    </div>

    <div class="details">
        <p><strong>Invoice Number:</strong> <?php echo $order['order_id']; ?></p>
        <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
        <p><strong>Customer ID:</strong> <?php echo $user_id; ?></p>
    </div>

    <table>
        <tr
