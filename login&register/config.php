<?php
$host = "localhost:3307"; // change port if your MySQL uses different port
$user = "root";
$pass = "";
$db   = "users_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
