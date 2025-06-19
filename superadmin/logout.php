<?php
session_start();
unset($_SESSION['super_admin_logged_in']);
unset($_SESSION['super_admin_id']);
unset($_SESSION['super_admin_username']);
header("Location: ../costumer_login.php");
exit();
?>
