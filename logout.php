<?php
session_start();
unset($_SESSION['user_logged_in']);
unset($_SESSION['user_id']);
unset($_SESSION['user_username']);
header("Location: costumer_login.php");
exit();
?>

