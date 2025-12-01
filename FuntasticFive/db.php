<?php
function db_connect() {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'stylenwear_db'; 

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    return $conn;
}
?>

