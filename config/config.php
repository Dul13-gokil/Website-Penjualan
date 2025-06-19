<?php
$host = "localhost";
$user = "root"; // Ganti jika ada username lain
$password = ""; // Ganti jika ada password
$database = "toko_bangunan_222060";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
