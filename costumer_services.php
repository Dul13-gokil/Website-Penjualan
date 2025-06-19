<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: costumer_login.php");
    exit();
}

include 'config/config.php';

$user_id = $_SESSION['user_id'];
$products = $conn->query("SELECT * FROM product_222060");

// Proses Tambah ke Keranjang atau Pesan Sekarang
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_name'])) {
    $user_id = $_SESSION['user_id'];
    $product_name = $_POST['product_name'];
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $total_price = $price * $quantity;

    // Periksa ketersediaan stok
    $stock_query = $conn->prepare("SELECT stock_222060 FROM product_222060 WHERE product_name_222060 = ?");
    $stock_query->bind_param("s", $product_name);
    $stock_query->execute();
    $stock_query->bind_result($current_stock);
    $stock_query->fetch();
    $stock_query->close();

    if ($quantity <= 0) {
        echo "<script>alert('Jumlah harus lebih dari 0!'); window.location.href='costumer_services.php';</script>";
        exit();
    } else if ($current_stock <= 0) {
        echo "<script>alert('Maaf, stok produk habis!'); window.location.href='costumer_services.php';</script>";
        exit();
    } else if ($quantity > $current_stock) {
        echo "<script>alert('Jumlah pesanan melebihi stok yang tersedia! Stok tersedia: " . $current_stock . "'); window.location.href='costumer_services.php';</script>";
        exit();
    }

    if (isset($_POST['order_now'])) {
        // Redirect ke checkout.php jika memilih "Pesan Sekarang"
        $_SESSION['checkout'] = [
            'product_name' => $product_name,
            'quantity' => $quantity,
            'price' => $price,
            'total_price' => $total_price
        ];
        header("Location: checkout.php");
        exit();
    } else {
        // Tambah ke keranjang
        $stmt = $conn->prepare("INSERT INTO cart_222060 (user_id_222060, product_name_222060, quantity_222060, price_222060, total_price_222060) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isidd", $user_id, $product_name, $quantity, $price, $total_price);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Produk berhasil ditambahkan ke keranjang!";
                header("Location: costumer_services.php");
                exit();
            } else {
                echo "<script>alert('Gagal menambahkan produk!'); window.location.href='costumer_services.php';</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Kesalahan sistem: " . $conn->error . "'); window.location.href='costumer_services.php';</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Produk</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome (Perbaikan Ikon Media Sosial) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/costumer_services.css">
    <style>
        .product-stock {
            margin-top: 10px;
            text-align: center;
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stock-badge:before {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .in-stock {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 6px 40px;
        }

        .in-stock:before {
            background-color: #2e7d32;
        }

        .low-stock {
            background-color: #fff8e1;
            color: #ff8f00;
            padding: 6px 40px;
        }

        .low-stock:before {
            background-color: #ff8f00;
        }

        .out-stock {
            background-color: #ffebee;
            color: #c62828;
            padding: 6px 60px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 80%;
            max-width: 500px;
            position: relative;
            animation: slideIn 0.4s;
        }

        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }

        .modal-header {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
            text-align: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .modal-body {
            padding: 20px 0;
            text-align: center;
        }

        .modal-footer {
            padding: 10px 0;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .modal-footer button {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .btn-cancel:hover {
            background-color: #e9ecef;
        }

        .btn-confirm {
            background-color: #007bff;
            color: white;
        }

        .btn-confirm:hover {
            background-color: #0069d9;
        }

        .warning-icon {
            font-size: 48px;
            color: #ff9800;
            margin-bottom: 15px;
        }

        .success-icon {
            font-size: 48px;
            color: #4CAF50;
            margin-bottom: 15px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 10px;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }


        /* Mode Malam */
        .night-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        .night-mode .sidebar {
            background-color: #2c2c2c;
        }

        .night-mode .content {
            color: #e0e0e0;
        }

        .night-mode h2 {
            color: #4da6ff;
        }

        .night-mode .intro-section {
            background-color: rgba(44, 44, 44, 0.9);
            color: #b0b0b0;
        }

        .night-mode .service-item {
            background-color: #2c2c2c;
            border-color: #444;
            color: #e0e0e0;
        }

        .night-mode .service-item:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }

        .night-mode .service-item h3 {
            color: #4da6ff;
        }

        .night-mode .product-description {
            color: #b0b0b0;
        }

        .night-mode .product-price {
            color: #4CAF50;
        }

        .night-mode .in-stock {
            background-color: rgba(46, 125, 50, 0.2);
            color: #4CAF50;
        }

        .night-mode .low-stock {
            background-color: rgba(255, 143, 0, 0.2);
            color: #ffb74d;
        }

        .night-mode .out-stock {
            background-color: rgba(198, 40, 40, 0.2);
            color: #ef5350;
        }

        .night-mode .quantity-input {
            background-color: #3a3a3a;
            border-color: #555;
            color: #e0e0e0;
        }

        .night-mode .quantity-input:focus {
            border-color: #4da6ff;
        }

        .night-mode .order-btn {
            background-color: #4da6ff;
        }

        .night-mode .order-btn:hover:not(:disabled) {
            background-color: #3d8bff;
        }

        .night-mode .cart-btn {
            background-color: #4CAF50;
        }

        .night-mode .cart-btn:hover:not(:disabled) {
            background-color: #45a049;
        }

        .night-mode .order-btn:disabled,
        .night-mode .cart-btn:disabled {
            background-color: #555;
            color: #999;
        }

        .night-mode .popup {
            background-color: #2c2c2c;
            color: #4da6ff;
            border-left-color: #4da6ff;
        }

        .night-mode .modal-content {
            background-color: #2c2c2c;
            color: #e0e0e0;
        }

        .night-mode .modal-header {
            border-bottom-color: #444;
        }

        .night-mode .modal-header h3 {
            color: #4da6ff;
        }

        .night-mode .modal-footer {
            border-top-color: #444;
        }

        .night-mode .btn-cancel {
            background-color: #3a3a3a;
            color: #b0b0b0;
        }

        .night-mode .btn-cancel:hover {
            background-color: #4a4a4a;
        }

        .night-mode .btn-confirm {
            background-color: #4da6ff;
        }

        .night-mode .btn-confirm:hover {
            background-color: #3d8bff;
        }

        .night-mode .close {
            color: #b0b0b0;
        }

        .night-mode .close:hover,
        .night-mode .close:focus {
            color: #e0e0e0;
        }

        .night-mode .bg-pattern {
            background-image: 
                radial-gradient(rgba(77, 166, 255, 0.1) 3px, transparent 3px),
                radial-gradient(rgba(77, 166, 255, 0.1) 3px, transparent 3px);
        }

        /* Perbaikan warna teks untuk mode malam */

/* Perbaiki warna placeholder input */
.night-mode .quantity-input::placeholder {
    color: #b0b0b0;
}

/* Perbaiki warna label dan teks form */
.night-mode .product-form label {
    color: #e0e0e0;
}

/* Perbaiki warna copyright */
.night-mode .copyright {
    color: #b0b0b0;
}

/* Perbaiki warna link sidebar yang tidak aktif */
.night-mode .sidebar ul li a {
    color: #b0b0b0;
}

.night-mode .sidebar ul li a:hover {
    color: #4da6ff;
}

.night-mode .sidebar ul li a.active {
    color: #ffffff;
}

/* Perbaiki warna ikon sidebar */
.night-mode .sidebar ul li a i {
    color: inherit;
}

/* Perbaiki warna judul sidebar */
.night-mode .sidebar h2 {
    color: #4da6ff;
}

/* Perbaiki warna teks input yang diketik user */
.night-mode .quantity-input {
    color: #e0e0e0;
}

/* Perbaiki warna border sidebar saat hover */
.night-mode .sidebar ul li a:hover {
    border-left-color: #4da6ff;
}

/* Perbaiki warna background sidebar active */
.night-mode .sidebar ul li a.active {
    background-color: rgba(77, 166, 255, 0.2);
    border-left-color: #4da6ff;
}

        /* Toggle Button */
        .theme-toggle {
            position: fixed;
            top: 15px;
            right: 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1001;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .theme-toggle:hover {
            background-color: #0056b3;
            transform: scale(1.1);
        }

        .night-mode .theme-toggle {
            background-color: #4da6ff;
        }

        .night-mode .theme-toggle:hover {
            background-color: #3d8bff;
        }

    </style>
</head>
<body>

<button class="theme-toggle" id="theme-toggle" title="Toggle Mode Malam">
    <i class="fas fa-moon" id="theme-icon"></i>
</button>

<!-- Background Pattern -->
<div class="bg-pattern"></div>

<!-- Tombol Menu --> 
<div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-bars" id="menu-icon"></i>
</div>

<div class="sidebar" id="sidebar">
    <h2>Menu</h2>
    <ul>
        <li>
            <a href="costumer_home.php" class="<?= basename($_SERVER['PHP_SELF']) == 'costumer_home.php' ? 'active' : ''; ?>">
                <i class="bi bi-house-door"></i> Beranda
            </a>
        </li>
        <li>
            <a href="costumer_services.php" class="<?= basename($_SERVER['PHP_SELF']) == 'costumer_services.php' ? 'active' : ''; ?>">
                <i class="bi bi-cart-plus"></i> Pesan Produk
            </a>
        </li>
        <li>
            <a href="add_to_cart.php" class="<?= basename($_SERVER['PHP_SELF']) == 'add_to_cart.php' ? 'active' : ''; ?>">
                <i class="bi bi-basket"></i> Keranjang
            </a>
        </li>
        <li>
            <a href="costumer_cart.php" class="<?= basename($_SERVER['PHP_SELF']) == 'costumer_cart.php' ? 'active' : ''; ?>">
                <i class="bi bi-cart"></i> Pesanan
            </a>
        </li>
        <li>
            <a href="payment.php" class="<?= basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">
            <i class="bi bi-credit-card"></i> Pembayaran
            </a>
        </li>
        <li>
            <a href="history_pesanan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'history_pesanan.php' ? 'active' : ''; ?>">
                <i class="bi bi-clipboard"></i> Histori Pesanan
            </a>
        </li>
        <li>
        <li>
            <a href="logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>

    <!-- Copyright -->
    <div class="copyright">
        &copy; <?= date("Y"); ?> Toko Bangunan. All rights reserved.
    </div>

    <!-- Ikon Media Sosial -->
    <div class="social-links">
        <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
        <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
        <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
        <a href="#" target="_blank"><i class="fab fa-whatsapp"></i></a>
    </div>
</div>

<div class="content">
    <h2>Daftar Produk</h2>
    
    <!-- Introduction Section (like hero section) -->
    <div class="intro-section">
        <p>Temukan berbagai material bangunan berkualitas untuk proyek Anda. Pilih produk terbaik dengan harga terjangkau dan nikmati kemudahan berbelanja.</p>
    </div>

    <!-- Popup -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="popup" id="popupMessage"><?= $_SESSION['message']; ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="services-container">
        <?php while ($product = $products->fetch_assoc()) { ?>
            <div class="service-item">
                <img src="uploads/<?= htmlspecialchars($product['product_image_222060']) ?>" alt="<?= htmlspecialchars($product['product_name_222060']) ?>">
                <h3><?= htmlspecialchars($product['product_name_222060']) ?></h3>
                
                <div class="product-info">
                    <div class="product-description">
                        <?= htmlspecialchars($product['description_222060']) ?>
                    </div>
                    <div class="product-price">
                        Rp<?= number_format($product['price_222060'], 0, ',', '.') ?>
                    </div>
                    <div class="product-stock">
                        <span class="stock-badge <?= $product['stock_222060'] > 10 ? 'in-stock' : ($product['stock_222060'] > 0 ? 'low-stock' : 'out-stock') ?>">
                            <?php if($product['stock_222060'] > 10): ?>
                                Stok Tersedia: <?= $product['stock_222060'] ?>
                            <?php elseif($product['stock_222060'] > 0): ?>
                                Stok Terbatas: <?= $product['stock_222060'] ?>
                            <?php else: ?>
                                Stok Habis
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Form untuk produk -->
                <form method="POST" action="" class="product-form" data-product-name="<?= htmlspecialchars($product['product_name_222060']) ?>" data-price="<?= $product['price_222060'] ?>" data-stock="<?= $product['stock_222060'] ?>">
                    <input type="hidden" name="from_page" value="customer_services">
                    <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['product_name_222060']) ?>">
                    <input type="hidden" name="price" value="<?= $product['price_222060'] ?>">
                    <input type="number" name="quantity" class="quantity-input" placeholder="Jumlah" required min="1" max="<?= $product['stock_222060'] ?>" <?= $product['stock_222060'] <= 0 ? 'disabled' : '' ?>>
                    <button type="button" class="order-btn" <?= $product['stock_222060'] <= 0 ? 'disabled' : '' ?> onclick="validateOrder(this, 'order')">
                        <?= $product['stock_222060'] <= 0 ? 'Stok Habis' : 'Pesan Sekarang' ?>
                    </button>
                    <button type="button" class="cart-btn" <?= $product['stock_222060'] <= 0 ? 'disabled' : '' ?> onclick="validateOrder(this, 'cart')">
                        <?= $product['stock_222060'] <= 0 ? 'Stok Habis' : 'Tambah ke Keranjang' ?>
                    </button>
                </form>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Modal Konfirmasi Tambah ke Keranjang -->
<div id="cartConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('cartConfirmModal')">&times;</span>
        <div class="modal-header">
            <h3>Konfirmasi</h3>
        </div>
        <div class="modal-body">
            <i class="bi bi-cart-plus success-icon"></i>
            <p>Apakah Anda yakin ingin menambahkan <span id="productName"></span> (<span id="productQty"></span> item) ke keranjang?</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('cartConfirmModal')">Batal</button>
            <button class="btn-confirm" id="confirmAddToCart">Konfirmasi</button>
        </div>
    </div>
</div>

<!-- Modal Peringatan Stok -->
<div id="stockWarningModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('stockWarningModal')">&times;</span>
        <div class="modal-header">
            <h3>Peringatan Stok</h3>
        </div>
        <div class="modal-body">
            <i class="bi bi-exclamation-triangle warning-icon"></i>
            <p>Jumlah yang Anda masukkan melebihi stok yang tersedia!</p>
            <p>Stok tersedia: <span id="availableStock"></span></p>
        </div>
        <div class="modal-footer">
            <button class="btn-confirm" onclick="closeModal('stockWarningModal')">Mengerti</button>
        </div>
    </div>
</div>

<script>
    // Tampilkan popup dan hilangkan setelah 3 detik
    document.addEventListener("DOMContentLoaded", function() {
        let popup = document.getElementById("popupMessage");
        if (popup) {
            popup.style.display = "block";
            setTimeout(() => {
                popup.style.display = "none";
            }, 3000);
        }
        
        // Toggle sidebar
        document.getElementById("menu-toggle").addEventListener("click", function() {
            let sidebar = document.getElementById("sidebar");
            let menuIcon = document.getElementById("menu-icon");

            sidebar.classList.toggle("open");

            // Ubah icon menjadi X atau kembali menjadi bars
            if (sidebar.classList.contains("open")) {
                menuIcon.classList.remove("fa-bars");
                menuIcon.classList.add("fa-times"); // Mengubah menjadi ikon silang/kali (X)
                menuIcon.style.color = "#fff"; // Warna putih saat menu terbuka
            } else {
                menuIcon.classList.remove("fa-times");
                menuIcon.classList.add("fa-bars"); // Mengubah kembali menjadi ikon hamburger
                menuIcon.style.color = "#007bff"; // Warna biru saat menu tertutup
            }
        });
    });

    // Fungsi untuk menutup modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    // Variabel global untuk menyimpan form dan tipe aksi saat ini
    let currentForm = null;
    let currentAction = null;

    // Fungsi untuk validasi pesanan
    function validateOrder(button, action) {
        const form = button.closest('form');
        currentForm = form;
        currentAction = action;
        
        const quantityInput = form.querySelector('.quantity-input');
        const quantity = parseInt(quantityInput.value);
        const stock = parseInt(form.dataset.stock);
        const productName = form.dataset.productName;
        
        // Validasi jumlah
        if (isNaN(quantity) || quantity <= 0) {
            alert("Jumlah harus lebih dari 0!");
            return;
        }
        
        // Validasi stok
        if (quantity > stock) {
            document.getElementById('availableStock').textContent = stock;
            document.getElementById('stockWarningModal').style.display = "block";
            return;
        }
        
        // Jika aksi tambah ke keranjang, tampilkan modal konfirmasi
        if (action === 'cart') {
            document.getElementById('productName').textContent = productName;
            document.getElementById('productQty').textContent = quantity;
            document.getElementById('cartConfirmModal').style.display = "block";
        } else if (action === 'order') {
            // Jika pesan sekarang, langsung submit form ke checkout
            const formClone = form.cloneNode(true);
            const orderNowInput = document.createElement('input');
            orderNowInput.type = 'hidden';
            orderNowInput.name = 'order_now';
            orderNowInput.value = '1';
            formClone.appendChild(orderNowInput);
            formClone.action = 'checkout.php';
            document.body.appendChild(formClone);
            formClone.submit();
        }
    }

    // Event listener untuk tombol konfirmasi tambah ke keranjang
    document.getElementById('confirmAddToCart').addEventListener('click', function() {
        if (currentForm && currentAction === 'cart') {
            currentForm.submit();
        }
        closeModal('cartConfirmModal');
    });

    // Tutup modal ketika user klik di luar modal
    window.onclick = function(event) {
        if (event.target == document.getElementById('cartConfirmModal')) {
            closeModal('cartConfirmModal');
        }
        if (event.target == document.getElementById('stockWarningModal')) {
            closeModal('stockWarningModal');
        }
    }

            // Toggle mode malam
        const themeToggle = document.getElementById("theme-toggle");
        const themeIcon = document.getElementById("theme-icon");
        const body = document.body;

        // Cek apakah ada preferensi tema yang tersimpan
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'night') {
            body.classList.add('night-mode');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }

        themeToggle.addEventListener("click", function() {
            body.classList.toggle("night-mode");
            
            if (body.classList.contains("night-mode")) {
                themeIcon.classList.remove("fa-moon");
                themeIcon.classList.add("fa-sun");
                localStorage.setItem('theme', 'night');
            } else {
                themeIcon.classList.remove("fa-sun");
                themeIcon.classList.add("fa-moon");
                localStorage.setItem('theme', 'day');
            }
        });
</script>

</body>
</html>