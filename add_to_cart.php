<?php
include 'config/config.php';
session_start();

// Cek apakah pelanggan sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: costumer_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// **CASE: Checkout dari Keranjang**
if (isset($_POST['checkout_from_cart'])) {
    $cart_id = $_POST['cart_id'];
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $total_price = $_POST['total_price'];

    if ($quantity > 0) {
        $_SESSION['checkout'] = [
            'product_name' => $product_name,
            'quantity' => $quantity,
            'price' => $price,
            'total_price' => $total_price
        ];

        $delete_query = "DELETE FROM cart_222060 WHERE id_222060 = ? AND user_id_222060 = ?";
        $stmt_delete = $conn->prepare($delete_query);
        $stmt_delete->bind_param("ii", $cart_id, $user_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        header("Location: checkout.php");
        exit();
    }
}

// Proses penghapusan produk secara manual dari keranjang
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_cart'])) {
    $cart_id = $_POST['cart_id'];
    $delete_query = "DELETE FROM cart_222060 WHERE id_222060 = ? AND user_id_222060 = ?";
    $stmt_delete = $conn->prepare($delete_query);
    $stmt_delete->bind_param("ii", $cart_id, $user_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    echo "<script>alert('Produk berhasil dihapus dari keranjang!'); window.location='add_to_cart.php';</script>";
}

$query = "SELECT c.id_222060, p.product_image_222060, c.product_name_222060, c.quantity_222060, c.price_222060, c.total_price_222060 
          FROM cart_222060 c
          JOIN product_222060 p ON c.product_name_222060 = p.product_name_222060
          WHERE c.user_id_222060 = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_all = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            justify-content: center;
            padding: 40px 20px;
        }

        /* Background pattern */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(rgba(0, 123, 255, 0.1) 3px, transparent 3px),
                radial-gradient(rgba(0, 123, 255, 0.1) 3px, transparent 3px);
            background-size: 50px 50px;
            background-position: 0 0, 25px 25px;
            z-index: -1;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            background: white;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #007bff;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #007bff;
        }

        .username {
            text-align: center;
            font-size: 0.95rem;
            color: #666;
            margin-top: -10px;
            margin-bottom: 30px;
            background-color: #f8f9fa;
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
        }

        .username strong {
            color: #007bff;
            font-weight: 600;
        }

        /* Cart Header with Icon */
        .cart-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }



        /* Desktop Table Style */
        .desktop-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .desktop-table th, .desktop-table td {
            padding: 12px 10px;
            text-align: center;
        }

        .desktop-table th {
            background-color: #007bff;
            color: white;
            white-space: nowrap;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .desktop-table td {
            border-bottom: 1px solid #eee;
        }

        .desktop-table tr:last-child td {
            border-bottom: none;
        }

        .desktop-table tr:hover {
            background-color: #f8f9fa;
        }

        .desktop-table tr:last-child {
            font-weight: 600;
            background-color: #f8f9fa;
        }

        /* Mobile Card Style */
        .mobile-cards {
            display: none;
        }

        .order-card {
            margin-bottom: 20px;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .order-card-header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
        }

        .order-card-body {
            padding: 15px;
            display: flex;
            background-color: white;
        }

        .order-card-image {
            flex: 0 0 100px;
            margin-right: 15px;
        }

        .order-card-details {
            flex: 1;
        }

        .order-card-row {
            display: flex;
            margin-bottom: 8px;
            align-items: center;
        }

        .order-card-label {
            flex: 0 0 80px;
            font-weight: 500;
            color: #666;
        }

        .order-card-value {
            flex: 1;
            color: #333;
        }

        .order-card-actions {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
        }

        /* Buttons and Common Elements */
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
            transition: transform 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        tr:hover .product-image, .order-card:hover .product-image {
            transform: scale(1.05);
        }

        .btn-checkout, .btn-delete {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            color: white;
            margin: 2px 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-checkout {
            background-color: #28a745;
        }

        .btn-checkout:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn-delete:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 10px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0,123,255,0.2);
        }

        .btn-back:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }

        .back-container {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Empty cart message */
        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-cart i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
            display: block;
        }

        .empty-cart a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .empty-cart a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Total summary box */
        .cart-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-summary-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }

        .cart-summary-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #007bff;
        }

        /* Copyright */
        .copyright {
            text-align: center;
            color: #888;
            font-size: 0.8rem;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            
            .container {
                width: 100%;
                padding: 20px 15px;
            }
            
            .desktop-table {
                display: none;
            }
            
            .mobile-cards {
                display: block;
            }
            
            h2 {
                font-size: 1.4rem;
            }
            
            .username {
                font-size: 0.85rem;
            }
            
            .order-card-image {
                flex: 0 0 80px;
            }
            
            .product-image {
                width: 70px;
                height: 70px;
            }
            
            .cart-summary {
                flex-direction: column;
                gap: 5px;
            }
            
            .back-container {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media screen and (max-width: 480px) {
            .order-card-body {
                flex-direction: column;
            }
            
            .order-card-image {
                margin-right: 0;
                margin-bottom: 15px;
                text-align: center;
            }
            
            .product-image {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>

    <div class="container">
        <!-- Cart Header with Icon -->
        <div class="cart-header">
            <h2>Keranjang Belanja</h2>
        </div>
        
        <div class="username">
            <i class="bi bi-person"></i> Pengguna: <strong><?= htmlspecialchars($username) ?></strong>
        </div>

        <!-- Tampilan tabel untuk desktop -->
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Gambar</th>
                    <th>Nama Produk</th>
                    <th>Jumlah</th>
                    <th>Harga</th>
                    <th>Total Harga</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_all = 0;
                if ($result->num_rows > 0) { 
                    while ($row = $result->fetch_assoc()) {
                        $total_all += $row['total_price_222060']; 
                        // Pastikan gambar tidak kosong
                        $product_image = !empty($row['product_image_222060']) ? $row['product_image_222060'] : 'default.jpg'; 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><img src="uploads/<?= htmlspecialchars($product_image) ?>" class="product-image" alt="Gambar Produk"></td>
                            <td><?= htmlspecialchars($row['product_name_222060']) ?></td>
                            <td><?= $row['quantity_222060'] ?></td>
                            <td>Rp <?= number_format($row['price_222060'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($row['total_price_222060'], 0, ',', '.') ?></td>
                            <td><?= date("d-m-Y") ?></td>
                            <td>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="product_name" value="<?= $row['product_name_222060'] ?>">
                                    <input type="hidden" name="quantity" value="<?= $row['quantity_222060'] ?>">
                                    <input type="hidden" name="price" value="<?= $row['price_222060'] ?>">
                                    <input type="hidden" name="total_price" value="<?= $row['total_price_222060'] ?>">
                                    <button type="submit" name="checkout_from_cart" class="btn-checkout">
                                        <i class="bi bi-bag-check"></i> Checkout
                                    </button>
                                </form>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="cart_id" value="<?= $row['id_222060'] ?>">
                                    <button type="submit" name="delete_cart" class="btn-delete" onclick="return confirm('Yakin ingin menghapus produk ini?');">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-cart">
                                <i class="bi bi-cart-x"></i>
                                <p>Keranjang Anda kosong.</p>
                                <p><a href="costumer_services.php">Belanja sekarang!</a></p>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Tampilan card untuk mobile -->
        <div class="mobile-cards">
            <?php 
            // Reset iterator
            if ($result && $result->num_rows > 0) {
                // Kembalikan pointer ke awal
                $result->data_seek(0);
                $no = 1;
                $total_all = 0;
                while ($row = $result->fetch_assoc()) { 
                    $total_all += $row['total_price_222060'];
                    $product_image = !empty($row['product_image_222060']) ? $row['product_image_222060'] : 'default.jpg';
                    ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <div><i class="bi bi-box-seam"></i> Produk #<?= $no++ ?></div>
                            <div><?= date("d-m-Y") ?></div>
                        </div>
                        <div class="order-card-body">
                            <div class="order-card-image">
                                <img src="uploads/<?= htmlspecialchars($product_image) ?>" class="product-image" alt="Gambar Produk">
                            </div>
                            <div class="order-card-details">
                                <div class="order-card-row">
                                    <div class="order-card-label">Produk:</div>
                                    <div class="order-card-value"><?= htmlspecialchars($row['product_name_222060']) ?></div>
                                </div>
                                <div class="order-card-row">
                                    <div class="order-card-label">Jumlah:</div>
                                    <div class="order-card-value"><i class="bi bi-box"></i> <?= $row['quantity_222060'] ?></div>
                                </div>
                                <div class="order-card-row">
                                    <div class="order-card-label">Harga:</div>
                                    <div class="order-card-value"><i class="bi bi-tag"></i> Rp <?= number_format($row['price_222060'], 0, ',', '.') ?></div>
                                </div>
                                <div class="order-card-row">
                                    <div class="order-card-label">Total:</div>
                                    <div class="order-card-value"><strong>Rp <?= number_format($row['total_price_222060'], 0, ',', '.') ?></strong></div>
                                </div>
                                <div class="order-card-actions">
                                <form method="POST" action="checkout.php" style="display:inline;">
    <input type="hidden" name="from_page" value="add_to_cart">
    <input type="hidden" name="product_name" value="<?= $row['product_name_222060'] ?>">
    <input type="hidden" name="quantity" value="<?= $row['quantity_222060'] ?>">
    <input type="hidden" name="price" value="<?= $row['price_222060'] ?>">
    <input type="hidden" name="total_price" value="<?= $row['total_price_222060'] ?>">
    <button type="submit" name="order_now" class="btn-checkout">
        <i class="bi bi-bag-check"></i> Checkout
    </button>
</form>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="cart_id" value="<?= $row['id_222060'] ?>">
                                        <button type="submit" name="delete_cart" class="btn-delete" onclick="return confirm('Yakin ingin menghapus produk ini?');">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-cart">
                    <i class="bi bi-cart-x"></i>
                    <p>Keranjang Anda kosong.</p>
                    <p><a href="costumer_services.php">Belanja sekarang!</a></p>
                </div>
            <?php } ?>
        </div>

        <?php if ($result && $result->num_rows > 0) { ?>
            <!-- Cart Summary Box -->
            <div class="cart-summary">
                <div class="cart-summary-label">Total Belanja:</div>
                <div class="cart-summary-value">Rp <?= number_format($total_all, 0, ',', '.') ?></div>
            </div>
        <?php } ?>

        <div class="back-container">
           <!-- Tombol kembali ke beranda -->
           <a href="costumer_home.php" class="btn-back">
                <i class="fas fa-home"></i> Beranda
            </a>
            <!-- Tombol lanjut ke halaman pembayaran -->
            <a href="costumer_cart.php" class="btn-back">
            <i class="bi bi-cart"></i> Pesanan
            </a>
            
            <?php if ($result && $result->num_rows > 0) { ?>
                <a href="costumer_services.php" class="btn-back" style="background-color: #28a745;">
                    <i class="bi bi-cart-plus"></i> Tambah Produk Lain
                </a>
            <?php } ?>
        </div>

        <!-- Copyright -->
        <div class="copyright">
            &copy; <?= date("Y"); ?> Toko Bangunan. All rights reserved.
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Add animation for table rows
            const tableRows = document.querySelectorAll('.desktop-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = "0";
                row.style.transform = "translateY(20px)";
                row.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                
                setTimeout(() => {
                    row.style.opacity = "1";
                    row.style.transform = "translateY(0)";
                }, 100 + (index * 50));
            });
            
            // Add animation for mobile cards
            const cards = document.querySelectorAll('.order-card');
            cards.forEach((card, index) => {
                card.style.opacity = "0";
                card.style.transform = "translateY(20px)";
                card.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                
                setTimeout(() => {
                    card.style.opacity = "1";
                    card.style.transform = "translateY(0)";
                }, 100 + (index * 50));
            });
        });
    </script>
</body>
</html>