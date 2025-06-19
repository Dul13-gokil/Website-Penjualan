<?php
session_start();
if (!isset($_SESSION['super_admin_logged_in']) || $_SESSION['super_admin_logged_in'] !== true) {
    header("Location: ../costumer_login.php");
    exit();
}

include '../config/config.php';

// Fungsi untuk menambahkan pesanan ke laporan
function addToOrderReport($conn, $order_id, $customer_name, $product, $quantity, $price, $status, $completion_date, $notes) {
    $stmt = $conn->prepare(
        "INSERT INTO order_report_222060 
        (order_id_222060, customer_name_222060, product_222060, quantity_222060, price_222060, status_222060, completion_date_222060, notes_222060, created_at_222060) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("issiisss", $order_id, $customer_name, $product, $quantity, $price, $status, $completion_date, $notes);
    return $stmt->execute();
}

// Ambil semua pesanan beserta gambar produk (join dengan table product_222060)
$orders = $conn->query(
    "SELECT o.*, p.product_image_222060 
     FROM order_222060 o 
     LEFT JOIN product_222060 p 
       ON o.product_name_222060 = p.product_name_222060 
     WHERE o.order_status_222060 NOT IN ('completed','cancelled')"
);

// Hapus pesanan
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Periksa status pesanan sebelum menghapus
    $order_result = $conn->query("SELECT * FROM order_222060 WHERE id_222060=$id");
    if ($order_result && $order_result->num_rows > 0) {
        $order = $order_result->fetch_assoc();
        
        // Jika status completed atau cancelled, pastikan ada di laporan
        if (in_array($order['order_status_222060'], ['completed','cancelled'])) {
            // Periksa apakah sudah ada di laporan
            $report_exists = $conn->query("SELECT 1 FROM order_report_222060 WHERE order_id_222060=$id")->num_rows > 0;
            
            if (!$report_exists) {
                $note_text = ($order['order_status_222060'] == 'completed') ? "Pesanan Selesai" : "Dibatalkan";
                addToOrderReport(
                    $conn,
                    $order['id_222060'],
                    $order['username_222060'],
                    $order['product_name_222060'],
                    $order['quantity_222060'],
                    $order['total_price_222060'],
                    $order['order_status_222060'],
                    $order['order_date_222060'],
                    $note_text
                );
            }
            $conn->query("UPDATE order_report_222060 SET order_id_222060=NULL WHERE order_id_222060=$id");
        }
    }
    
    $conn->query("DELETE FROM order_222060 WHERE id_222060=$id");
    header("Location: orders.php");
    exit();
}

// Update status pesanan dan tambahkan ke laporan jika status completed
if (isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $completion_date = ($status == 'completed') ? date('Y-m-d H:i:s') : NULL;
    $notes = $_POST['notes'] ?? '';
    
    $conn->query("UPDATE order_222060 SET order_status_222060='$status' WHERE id_222060=$id");
    
    if ($status == 'completed') {
        $order = $conn->query("SELECT * FROM order_222060 WHERE id_222060=$id")->fetch_assoc();
        addToOrderReport(
            $conn,
            $order['id_222060'],
            $order['username_222060'],
            $order['product_name_222060'],
            $order['quantity_222060'],
            $order['total_price_222060'],
            $status,
            $completion_date,
            $notes
        );
    }
    
    header("Location: orders.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/orders.css">
    <style>
        /* Tambahkan styling untuk gambar produk */
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="bg-pattern"></div>
<div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-bars" id="menu-icon"></i>
</div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <h2>Super Admin</h2>
    <ul>
        <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a></li>
        <li><a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>">
            <i class="bi bi-basket"></i> Pesanan
        </a></li>
        <li><a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> Produk
        </a></li>
        <li><a href="laporan.php" class="<?= $current_page == 'laporan.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Laporan
        </a></li>
        <li><a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Kelola User
        </a></li>
        <li><a href="admins.php" class="<?= $current_page == 'admins.php' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> Kelola Admin</a></li>
        <li><a href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a></li>
        
    </ul>
</aside>
<main class="content">
    <section>
        <h2><i class="bi bi-basket"></i> Daftar Pesanan</h2>
        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID User</th>
                        <th>Username</th>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Metode Pembayaran</th>
                        <th>Alamat Pengiriman</th>
                        <th>Tanggal Pesan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    while ($row = $orders->fetch_assoc()) { ?>
                        <tr>
                            <td data-label="No"><?= $no++ ?></td>
                            <td data-label="ID User"><?= htmlspecialchars($row['user_id_222060']) ?></td>
                            <td data-label="Username"><?= htmlspecialchars($row['username_222060']) ?></td>
                            <td data-label="Gambar">
                                <?php if ($row['product_image_222060']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($row['product_image_222060']) ?>" alt="Gambar Produk" class="product-image">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td data-label="Nama Produk"><?= htmlspecialchars($row['product_name_222060']) ?></td>
                            <td data-label="Jumlah"><?= htmlspecialchars($row['quantity_222060']) ?></td>
                            <td data-label="Harga Satuan">Rp<?= number_format($row['price_222060'], 0, ',', '.') ?></td>
                            <td data-label="Total Harga">Rp<?= number_format($row['total_price_222060'], 0, ',', '.') ?></td>
                            <td data-label="Status">
                                <?php 
                                    $status = strtolower($row['order_status_222060']);
                                    if ($status == 'pending') {
                                        echo '<span class="status-badge status-pending"> Pending</span>';
                                    } elseif ($status == 'processed') {
                                        echo '<span class="status-badge status-processed"> Processed</span>';
                                    } elseif ($status == 'completed') {
                                        echo '<span class="status-badge status-completed"> Completed</span>';
                                    } elseif ($status == 'cancelled') {
                                        echo '<span class="status-badge status-cancelled"> Cancelled</span>';
                                    } else {
                                        echo '<span class="status-badge">'.htmlspecialchars($row['order_status_222060']).'</span>';
                                    }
                                ?>
                            </td>
                            <td data-label="Metode Pembayaran"><?= htmlspecialchars($row['payment_method_222060']) ?></td>
                            <td data-label="Alamat Pengiriman" class="truncate" title="<?= htmlspecialchars($row['alamat_222060']) ?>"><?= htmlspecialchars($row['alamat_222060']) ?></td>
                            <td data-label="Tanggal Pesan"><?= htmlspecialchars($row['order_date_222060']) ?></td>
                            <td data-label="Aksi">
                                <div class="action-buttons">
                                    <?php if (!in_array($row['order_status_222060'], ['completed','cancelled'])): ?>
                                        <a href="edit_order.php?id=<?= $row['id_222060'] ?>" class="action-button edit-button">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                    <?php else: ?>
                                        <button class="action-button edit-button" disabled>
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                    <?php endif; ?>
                                    <a href="orders.php?delete=<?= $row['id_222060'] ?>" onclick="return confirm('Hapus pesanan ini?')" class="action-button delete-button">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("menu-toggle").addEventListener("click", function() {
            let sidebar = document.getElementById("sidebar");
            let menuIcon = document.getElementById("menu-icon");
            sidebar.classList.toggle("open");
            if (sidebar.classList.contains("open")) {
                menuIcon.classList.replace("fa-bars","fa-times");
                menuIcon.style.color = "#fff";
                menuIcon.style.marginTop = "13px";
                menuIcon.style.fontSize = "30px";
            } else {
                menuIcon.classList.replace("fa-times","fa-bars");
                menuIcon.style.color = "#343a40";
                menuIcon.style.marginTop = "0";
                menuIcon.style.fontSize = "28px";
            }
        });
    });
</script>

</body>
</html>
