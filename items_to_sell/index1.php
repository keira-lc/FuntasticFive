<?php
// Show all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = "127.0.0.1";
$port = 3307;
$dbname = "stylenwear_db";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all products
$sql = "SELECT * FROM products ORDER BY id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'n Wear</title>
    <link rel="stylesheet" href="style1.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet" />
</head>

<body>
    <header>
        <a href="#" class="logo">Style'nWear</a>
        <div id="cart-icon">
            <i class="ri-shopping-bag-line"></i>
            <span class="cart-item-count"></span>
        </div>
    </header>

    <div class="cart">
        <h2 class="cart-title">Your Cart</h2>
        <div class="cart-content">
            <div class="total">
                <div class="total-title">Total</div>
                <div class="total-price">₱0.00</div>
            </div>
            <button class="btn-buy">Buy Now</button>
            <i class="ri-close-line" id="cart-close"></i>
        </div>
    </div>

    <section class="shop">
        <h1 class="section title">Shop Products</h1>
        <div class="product-content">

            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $title = $row['tite'];
                    $price = $row['price'];
                    $image = $row['image'];

                    echo '
                    <div class="product-box" data-price="' . $price . '">
                        <div class="img-box">
                            <img src="' . $image . '" alt="' . htmlspecialchars($title) . '">
                        </div>
                        <h2 class="product-title">' . htmlspecialchars($title) . '</h2>
                        <div class="price-and-cart">
                            <span class="price">₱' . number_format($price, 2) . '</span>
                            <i class="ri-shopping-bag-line add-cart"></i>
                        </div>
                    </div>';
                }
            } else {
                echo "<p>No products found.</p>";
            }

            $conn->close();
            ?>

        </div>
    </section>

    <script src="script.js"></script>
</body>
</html>
