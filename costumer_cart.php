<?php
include 'config/config.php';
session_start();

// Pastikan pelanggan sudah login
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user_username'];

// Proses penghapusan pesanan
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    
    // Mulai transaksi database
    $conn->begin_transaction();
    
    try {
        // Cek apakah pesanan milik user yang sedang login
        $check_order_query = "SELECT order_status_222060 FROM order_222060 WHERE id_222060 = ? AND username_222060 = ?";
        $stmt_check = $conn->prepare($check_order_query);
        $stmt_check->bind_param("is", $order_id, $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Hapus data dari tabel payment_222060 jika ada
            $payment_check_query = "SELECT * FROM payment_222060 WHERE id_222060 = ?";
            $stmt_payment_check = $conn->prepare($payment_check_query);
            $stmt_payment_check->bind_param("i", $order_id);
            $stmt_payment_check->execute();
            $payment_result = $stmt_payment_check->get_result();
            
            if ($payment_result->num_rows > 0) {
                $delete_payment_query = "DELETE FROM payment_222060 WHERE id_222060 = ?";
                $stmt_delete_payment = $conn->prepare($delete_payment_query);
                $stmt_delete_payment->bind_param("i", $order_id);
                $stmt_delete_payment->execute();
                $stmt_delete_payment->close();
            }
            $stmt_payment_check->close();
            
            // Hapus data dari tabel order_222060
            $delete_order_query = "DELETE FROM order_222060 WHERE id_222060 = ? AND username_222060 = ?";
            $stmt_delete_order = $conn->prepare($delete_order_query);
            $stmt_delete_order->bind_param("is", $order_id, $username);
            $stmt_delete_order->execute();
            
            // Commit transaksi jika tidak ada error
            $conn->commit();
            echo "<script>alert('Pesanan berhasil dihapus!'); window.location='costumer_cart.php';</script>";
        } else {
            // Rollback transaksi jika pesanan tidak ditemukan
            $conn->rollback();
            echo "<script>alert('Pesanan tidak ditemukan!'); window.location='costumer_cart.php';</script>";
        }
        
        $stmt_check->close();
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='costumer_cart.php';</script>";
    }
}

// Ambil data pesanan berdasarkan username pelanggan, termasuk gambar produk
// We need to modify the query to also get the payment confirmation status
$query = "SELECT o.id_222060, o.product_name_222060, o.quantity_222060, o.price_222060, o.total_price_222060, 
                 o.order_status_222060, o.order_date_222060, o.payment_method_222060, o.alamat_222060, p.product_image_222060,
                 pay.payment_confirmation_222060  
          FROM order_222060 o
          JOIN product_222060 p ON o.product_name_222060 = p.product_name_222060
          LEFT JOIN payment_222060 pay ON o.id_222060 = pay.id_222060
          WHERE o.username_222060 = ? AND o.order_status_222060 NOT IN ('Completed', 'Cancelled')";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Pesanan</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/costumer_cart.css">
    <style>
        /* Optional: tambahkan class disabled untuk tombol */
        .btn-delete.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="bg-pattern"></div>
    <div class="container">
        <div class="order-header">
            <h2>Keranjang Pesanan</h2>
        </div>
        <div class="username">
            <i class="bi bi-person"></i> Pengguna: <strong><?= htmlspecialchars($username) ?></strong>
        </div>
        <div class="filter-note">
            <i class="fas fa-info-circle"></i> Pesanan dengan status "Completed" atau "Cancelled" ditampilkan pada daftar <a href="history_pesanan.php">Histori Pesanan</a>.
        </div>

        <!-- Tabel Desktop -->
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Gambar</th>
                    <th>Nama Produk</th>
                    <th>Jumlah</th>
                    <th>Harga</th>
                    <th>Total Harga</th>
                    <th>Metode Pembayaran</th>
                    <th>Alamat</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; $total_all = 0;
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $total_all += $row['total_price_222060'];
                        $product_image = !empty($row['product_image_222060']) ? $row['product_image_222060'] : 'default.jpg';
                        $status_lower = strtolower($row['order_status_222060']);
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><img src="uploads/<?= htmlspecialchars($product_image) ?>" class="product-image" alt="Gambar Produk"></td>
                    <td><?= htmlspecialchars($row['product_name_222060']) ?></td>
                    <td><?= $row['quantity_222060'] ?></td>
                    <td>Rp <?= number_format($row['price_222060'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['total_price_222060'], 0, ',', '.') ?></td>
                    <td><span class="payment-badge"><i class="fas fa-credit-card"></i> <?= htmlspecialchars($row['payment_method_222060']) ?></span></td>
                    <td class="alamat" title="<?= htmlspecialchars($row['alamat_222060']) ?>"><?= htmlspecialchars($row['alamat_222060']) ?></td>
                    <td><span class="status <?= $status_lower ?>"><?= htmlspecialchars($row['order_status_222060']) ?></span></td>
                    <td><?= date("d-m-Y", strtotime($row['order_date_222060'])) ?></td>
                    <td>
    <?php if ($status_lower !== 'processed'): ?>
        <?php if ($row['order_status_222060'] == 'Cancelled' || $row['payment_confirmation_222060'] == 'cancelled'): ?>
            <button type="button" class="btn-delete disabled" disabled>
                Pesanan Dibatalkan
            </button>
        <?php else: ?>
            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesanan ini?');">
                <input type="hidden" name="order_id" value="<?= $row['id_222060'] ?>">
                <button type="submit" name="delete_order" class="btn-delete">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <button type="button" class="btn-delete disabled" disabled>
        Dalam Pengiriman
        </button>
    <?php endif; ?>
</td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="11">
                        <div class="empty-orders">
                            <i class="fas fa-box-open"></i>
                            <p>Tidak ada pesanan aktif yang tersedia.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Kartu Mobile -->
        <div class="mobile-cards">
            <?php
            if ($result && $result->num_rows > 0):
                $result->data_seek(0);
                $no = 1;
                $total_all = 0;
                while ($row = $result->fetch_assoc()):
                    $total_all += $row['total_price_222060'];
                    $product_image = !empty($row['product_image_222060']) ? $row['product_image_222060'] : 'default.jpg';
                    $status_lower = strtolower($row['order_status_222060']);
            ?>
            <div class="product-card">
                <div class="product-card-header">
                    <div><i class="fas fa-box"></i> Produk #<?= $no++ ?></div>
                    <span class="status <?= $status_lower ?>"><?= htmlspecialchars($row['order_status_222060']) ?></span>
                </div>
                <div class="product-card-body">
                    <div class="product-card-image">
                        <img src="uploads/<?= htmlspecialchars($product_image) ?>" class="product-image" alt="Gambar Produk">
                    </div>
                    <div class="product-card-details">
                        <div class="product-card-row">
                            <div class="product-card-label">Produk:</div>
                            <div class="product-card-value"><?= htmlspecialchars($row['product_name_222060']) ?></div>
                        </div>
                        <div class="product-card-row">
                            <div class="product-card-label">Jumlah:</div>
                            <div class="product-card-value"><i class="fas fa-cubes"></i> <?= $row['quantity_222060'] ?></div>
                        </div>
                        <div class="product-card-row">
                            <div class="product-card-label">Harga:</div>
                            <div class="product-card-value"><i class="fas fa-tag"></i> Rp <?= number_format($row['price_222060'], 0, ',', '.') ?></div>
                        </div>
                        <div class="product-card-row">
                            <div class="product-card-label">Total:</div>
                            <div class="product-card-value"><strong>Rp <?= number_format($row['total_price_222060'], 0, ',', '.') ?></strong></div>
                        </div>
                        <div class="product-card-row">
                            <div class="product-card-label">Pembayaran:</div>
                            <div class="product-card-value"><i class="fas fa-credit-card"></i> <?= htmlspecialchars($row['payment_method_222060']) ?></div>
                        </div>
                        <div class="product-card-row">
                            <div class="product-card-label">Alamat:</div>
                            <div class="product-card-value"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['alamat_222060']) ?></div>
                        </div>
                        <div class="product-card-row">
                            <div class="product-card-label">Tanggal:</div>
                            <div class="product-card-value"><i class="far fa-calendar-alt"></i> <?= date("d-m-Y", strtotime($row['order_date_222060'])) ?></div>
                        </div>
                        <div class="product-card-actions">
    <?php if ($status_lower !== 'processed'): ?>
        <?php if ($row['order_status_222060'] == 'Cancelled' || $row['payment_confirmation_222060'] == 'cancelled'): ?>
            <button type="button" class="btn-delete disabled" disabled>
                Pesanan Dibatalkan
            </button>
        <?php else: ?>
            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesanan ini?');">
                <input type="hidden" name="order_id" value="<?= $row['id_222060'] ?>">
                <button type="submit" name="delete_order" class="btn-delete">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <button type="button" class="btn-delete disabled" disabled>
         Dalam Pengiriman
        </button>
    <?php endif; ?>
</div>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty-orders">
                <i class="fas fa-box-open"></i>
                <p>Tidak ada pesanan aktif yang tersedia.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
        <div class="order-summary">
            <div class="order-summary-label">Total Pesanan Aktif:</div>
            <div class="order-summary-value">Rp <?= number_format($total_all, 0, ',', '.') ?></div>
        </div>
        <?php endif; ?>

        <div class="back-container">
            <!-- Tombol kembali ke beranda -->
            <a href="costumer_home.php" class="btn-back">
                <i class="fas fa-home"></i> Beranda
            </a>
            <!-- Tombol lanjut ke halaman pembayaran -->
            <a href="payment.php" class="btn-back">
                <i class="fas fa-credit-card"></i> Pembayaran
            </a>
        </div>


        <div class="copyright">&copy; <?= date("Y"); ?> Toko Bangunan. All rights reserved.</div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tableRows = document.querySelectorAll('.desktop-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = "0";
                row.style.transform = "translateY(20px)";
                row.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                setTimeout(() => { row.style.opacity = "1"; row.style.transform = "translateY(0)"; }, 100 + (index * 50));
            });
            const cards = document.querySelectorAll('.product-card');
            cards.forEach((card, index) => {
                card.style.opacity = "0";
                card.style.transform = "translateY(20px)";
                card.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                setTimeout(() => { card.style.opacity = "1"; card.style.transform = "translateY(0)"; }, 100 + (index * 50));
            });
        });
    </script>
</body>
</html>
