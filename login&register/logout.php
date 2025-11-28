<?php

session_start();
session_unset();
session_destroy();
header('Location: index5.php');
exit();
?>