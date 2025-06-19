<?php
session_start();
include 'config/config.php';

// Cek login user
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: costumer_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_username'];
$checkout = [];

// Jika data dikirim dari tombol "Pesan Sekarang"
if (isset($_POST['order_now'])) {
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $total_price = $price * $quantity;
    
    // Cek stok produk sebelum checkout
    $check_stock = $conn->prepare("SELECT stock_222060 FROM product_222060 WHERE product_name_222060 = ?");
    $check_stock->bind_param("s", $product_name);
    $check_stock->execute();
    $check_stock->bind_result($stock);
    $check_stock->fetch();
    $check_stock->close();
    
    // Jika stok tidak mencukupi
    if ($stock < $quantity) {
        $_SESSION['error_message'] = "Stok produk $product_name tidak mencukupi! Tersedia: $stock";
        if (isset($_POST['from_page']) && $_POST['from_page'] == 'customer_services') {
            header("Location: costumer_services.php");
        } else {
            header("Location: add_to_cart.php");
        }
        exit();
    }
    
    // Menyimpan halaman asal untuk tombol kembali
    if (isset($_POST['from_page']) && $_POST['from_page'] == 'customer_services') {
        $_SESSION['back_to'] = 'costumer_services.php';
    } else {
        $_SESSION['back_to'] = 'add_to_cart.php';
    }

    // Simpan data checkout di session
    $_SESSION['checkout'] = [
        'product_name' => $product_name,
        'quantity' => $quantity,
        'price' => $price,
        'total_price' => $total_price
    ];
}

// Ambil data checkout dari session
if (isset($_SESSION['checkout'])) {
    $checkout[] = $_SESSION['checkout'];
} else {
    echo "Tidak ada data untuk ditampilkan.";
    exit();
}

// Tambahkan setelah mengolah pesanan di tabel order_222060
// Modifikasi bagian proses complete_order

if (isset($_POST['complete_order'])) {
    $order_date = date('Y-m-d H:i:s');
    $status = 'Pending';

    // Tangkap alamat pengiriman dan metode pembayaran
    $alamat = htmlspecialchars($_POST['address']);
    $payment_method = htmlspecialchars($_POST['payment_method']);
    
    // Tambahan informasi pembayaran
    $virtual_account = '';
    
    // Proses detail metode pembayaran
    if ($payment_method === 'Transfer Bank') {
        $selected_bank = htmlspecialchars($_POST['selected_bank']);
        $payment_method = "Transfer Bank - " . $selected_bank;
        
        // Generate nomor VA (Virtual Account) acak untuk demo
        $virtual_account = $selected_bank . '-' . rand(100000000000, 999999999999);
    } elseif ($payment_method === 'E-Wallet') {
        $selected_ewallet = htmlspecialchars($_POST['selected_ewallet']);
        $payment_method = "E-Wallet - " . $selected_ewallet;
        
        // Generate ID transaksi acak untuk demo
        $virtual_account = $selected_ewallet . '-' . strtoupper(substr(md5(time()), 0, 12));
    }

    // Pastikan koneksi ke database aktif
    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    // Mulai transaksi untuk memastikan konsistensi data
    $conn->begin_transaction();

    try {
        foreach ($checkout as $item) {
            // Cek stok produk sebelum menyelesaikan pesanan
            $check_stock = $conn->prepare("SELECT stock_222060 FROM product_222060 WHERE product_name_222060 = ?");
            $check_stock->bind_param("s", $item['product_name']);
            $check_stock->execute();
            $check_stock->bind_result($current_stock);
            $stock_exists = $check_stock->fetch();
            $check_stock->close();
            
            // Jika produk tidak ditemukan atau stok tidak mencukupi
            if (!$stock_exists || $current_stock < $item['quantity']) {
                throw new Exception("Stok produk " . $item['product_name'] . " tidak mencukupi! Tersedia: " . ($stock_exists ? $current_stock : 0));
            }
            
            // Simpan pesanan ke tabel `order_222060` tanpa virtual_account_222060
            $stmt = $conn->prepare("INSERT INTO order_222060 
                (user_id_222060, username_222060, product_name_222060, quantity_222060, price_222060, total_price_222060, alamat_222060, payment_method_222060, order_status_222060, order_date_222060) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Query gagal: " . $conn->error);
            }

            // Perhatikan tipe data yang dikirim, jika diperlukan konversi
            $stmt->bind_param(
                "issiidssss", // Perbaikan tipe data: integer, string, string, integer, integer, double, string, string, string, string
                $user_id,
                $username,
                $item['product_name'],
                $item['quantity'],
                $item['price'],
                $item['total_price'],
                $alamat,
                $payment_method,
                $status,
                $order_date
            );

            if (!$stmt->execute()) {
                throw new Exception("Eksekusi query gagal: " . $stmt->error);
            }
            
            // Ambil order_id yang baru saja dibuat
            $order_id = $conn->insert_id;
            $stmt->close();
            
            // Simpan data pembayaran ke tabel `payment_222060` dengan order_id yang benar
            $payment_stmt = $conn->prepare("INSERT INTO payment_222060 
                (id_222060, user_id_222060, username_222060, product_name_222060, quantity_222060, price_222060, total_price_222060, alamat_222060, payment_method_222060, order_status_222060, order_date_222060, virtual_account_222060) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$payment_stmt) {
                throw new Exception("Query gagal: " . $conn->error);
            }

            $payment_stmt->bind_param(
                "iissiidsssss", // String definisi tipe: 12 parameter (i = integer, s = string, d = double)
                $order_id, // Tambahkan order_id yang sudah didapat dari insert sebelumnya
                $user_id,
                $username,
                $item['product_name'],
                $item['quantity'],
                $item['price'],
                $item['total_price'],
                $alamat,
                $payment_method,
                $status,
                $order_date,
                $virtual_account
            );

            if (!$payment_stmt->execute()) {
                throw new Exception("Eksekusi query pembayaran gagal: " . $payment_stmt->error);
            }

            $payment_stmt->close();

            // Update stok produk (mengurangi stok)
            $update_stock = $conn->prepare("UPDATE product_222060 SET stock_222060 = stock_222060 - ? WHERE product_name_222060 = ?");
            if (!$update_stock) {
                throw new Exception("Query update stok gagal: " . $conn->error);
            }
            
            $update_stock->bind_param("is", $item['quantity'], $item['product_name']);
            if (!$update_stock->execute()) {
                throw new Exception("Eksekusi update stok gagal: " . $update_stock->error);
            }
            
            $update_stock->close();

            // Hapus produk dari tabel `cart_222060`
            $delete_cart = $conn->prepare("DELETE FROM cart_222060 WHERE user_id_222060 = ? AND product_name_222060 = ?");
            if (!$delete_cart) {
                throw new Exception("Query gagal: " . $conn->error);
            }

            $delete_cart->bind_param("is", $user_id, $item['product_name']);
            if (!$delete_cart->execute()) {
                throw new Exception("Eksekusi query gagal: " . $delete_cart->error);
            }

            $delete_cart->close();
        }

        // Commit transaksi jika semua query berhasil
        $conn->commit();

        // Simpan data untuk halaman struk ke session
        $_SESSION['receipt'] = [
            'customer_name' => $username,
            'address' => $alamat,
            'payment_method' => $payment_method,
            'virtual_account' => $virtual_account, // Tetap menyimpan di session untuk struk
            'products' => array_column($checkout, 'product_name'),
            'quantities' => array_column($checkout, 'quantity'),
            'prices' => array_column($checkout, 'price'),
            'total_prices' => array_column($checkout, 'total_price'),
            'total_amount' => array_sum(array_column($checkout, 'total_price'))
        ];

        // Hapus data checkout dari session setelah pesanan selesai
        unset($_SESSION['checkout']);

        // Redirect ke halaman struk
        header("Location: receipt.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: checkout.php");
        exit();
    }
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Produk</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/checkout.css">
</head>
<body>
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>

    <div class="container">
        <!-- Checkout Header with Icon -->
        <div class="checkout-header">
            <h2>Checkout Produk</h2>
        </div>
        
        <div class="username">
            <i class="bi bi-person"></i> Pengguna: <strong><?= htmlspecialchars($username) ?></strong>
        </div>

        <!-- Error message display -->
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message">
            <i class="bi bi-exclamation-triangle"></i> <?= $_SESSION['error_message'] ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Step indicator -->
        <div class="checkout-steps">
            <div class="step">
                <div class="step-number completed">
                    <i class="bi bi-cart"></i>
                </div>
                <div class="step-label completed">Pesan Produk</div>
            </div>
            <div class="step">
                <div class="step-number active">
                <i class="bi bi-basket"></i>
                </div>
                <div class="step-label active">Checkout</div>
            </div>
        </div>

        <form method="POST">
            <div class="checkout-form">
                <div>
                    <div class="form-group">
                        <label class="form-label">Nama Pelanggan</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($username) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Alamat Pengiriman</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Masukkan alamat lengkap pengiriman Anda" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-control" placeholder="Masukkan nomor telepon aktif" required>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label class="form-label">Metode Pembayaran</label>
                        <div class="payment-methods">
                            <input type="radio" name="payment_method" id="cod" value="COD" class="payment-method" checked>
                            <label for="cod">
                                <i class="bi bi-cash"></i>
                                Cash on Delivery
                            </label>
                            
                            <input type="radio" name="payment_method" id="bank" value="Transfer Bank" class="payment-method">
                            <label for="bank">
                                <i class="bi bi-bank"></i>
                                Transfer Bank
                            </label>
                            
                            <input type="radio" name="payment_method" id="ewallet" value="E-Wallet" class="payment-method">
                            <label for="ewallet">
                                <i class="bi bi-wallet2"></i>
                                E-Wallet
                            </label>
                        </div>
                        
                        <!-- Detail Bank Transfer -->
                        <div id="bank-details" class="payment-details">
                            <label class="form-label">Pilih Bank</label>
                            <div class="payment-option">
                                <input type="radio" name="selected_bank" id="bca" value="BCA" class="payment-option-item" checked>
                                <label for="bca">
                                    <i class="bi bi-bank"></i>
                                    BCA
                                </label>
                                
                                <input type="radio" name="selected_bank" id="bri" value="BRI" class="payment-option-item">
                                <label for="bri">
                                    <i class="bi bi-bank"></i>
                                    BRI
                                </label>
                                
                                <input type="radio" name="selected_bank" id="mandiri" value="Mandiri" class="payment-option-item">
                                <label for="mandiri">
                                    <i class="bi bi-bank"></i>
                                    Mandiri
                                </label>
                                
                                <input type="radio" name="selected_bank" id="bni" value="BNI" class="payment-option-item">
                                <label for="bni">
                                    <i class="bi bi-bank"></i>
                                    BNI
                                </label>
                            </div>
                        </div>
                        
                        <!-- Detail E-Wallet -->
                        <div id="ewallet-details" class="payment-details">
                            <label class="form-label">Pilih E-Wallet</label>
                            <div class="payment-option">
                                <input type="radio" name="selected_ewallet" id="gopay" value="GoPay" class="payment-option-item" checked>
                                <label for="gopay">
                                    <i class="bi bi-wallet2"></i>
                                    GoPay
                                </label>
                                
                                <input type="radio" name="selected_ewallet" id="ovo" value="OVO" class="payment-option-item">
                                <label for="ovo">
                                    <i class="bi bi-wallet2"></i>
                                    OVO
                                </label>
                                
                                <input type="radio" name="selected_ewallet" id="dana" value="DANA" class="payment-option-item">
                                <label for="dana">
                                    <i class="bi bi-wallet2"></i>
                                    DANA
                                </label>
                                
                                <input type="radio" name="selected_ewallet" id="linkaja" value="LinkAja" class="payment-option-item">
                                <label for="linkaja">
                                    <i class="bi bi-wallet2"></i>
                                    LinkAja
                                </label>

                                <input type="radio" name="selected_ewallet" id="shopeepay" value="ShopeePay" class="payment-option-item">
                                <label for="shopeepay">
                                    <i class="bi bi-wallet2"></i>
                                    ShopeePay
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Catatan Pesanan (Opsional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan untuk pesanan Anda (opsional)"></textarea>
                    </div>
                </div>
            </div>

            <!-- Order summary -->

           <!-- Order summary -->
<div class="order-summary">
    <div class="order-summary-header">
        <i class="bi bi-basket"></i> Ringkasan Pesanan
    </div>
            <!-- Tampilan tabel untuk desktop -->
            <table class="desktop-table">
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    foreach ($checkout as $item):
                        $grand_total += $item['total_price'];

                        // Ambil gambar produk dari database
                        $product_name = $item['product_name'];
                        $query = "SELECT product_image_222060, stock_222060 FROM product_222060 WHERE product_name_222060 = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("s", $product_name);
                        $stmt->execute();
                        $stmt->bind_result($product_image, $product_stock);
                        $stmt->fetch();
                        $stmt->close();

                        // Default gambar jika tidak ada di database
                        $image_src = $product_image ? "uploads/" . $product_image : "uploads/default.png";
                    ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($image_src); ?>" class="product-image" alt="Gambar Produk"></td>
                            <td><?= htmlspecialchars($item['product_name']); ?></td>
                            <td><?= htmlspecialchars($item['quantity']); ?> 
                                <?php if ($product_stock < $item['quantity']): ?>
                                <span class="stock-warning">(Stok tidak cukup! Tersedia: <?= $product_stock ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>Rp <?= number_format($item['price'], 0, ',', '.'); ?></td>
                            <td>Rp <?= number_format($item['total_price'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;"><strong>Total Keseluruhan</strong></td>
                        <td><strong>Rp <?= number_format($grand_total, 0, ',', '.'); ?></strong></td>
                    </tr>
                </tbody>
            </table>
    
    <!-- Mobile Cards View -->
    <div class="mobile-cards">
        <?php 
        $grand_total_mobile = 0;
        foreach ($checkout as $item) {
            $grand_total_mobile += $item['total_price'];
            
            // Ambil stok produk
            $product_name = $item['product_name'];
            $query = "SELECT stock_222060 FROM product_222060 WHERE product_name_222060 = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $product_name);
            $stmt->execute();
            $stmt->bind_result($product_stock);
            $stmt->fetch();
            $stmt->close();
        ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <div><?= htmlspecialchars($item['product_name']); ?></div>
                    </div>
                    <div class="order-card-body">
                        <div class="order-card-image">
                            <img src="<?= htmlspecialchars($image_src); ?>" class="product-image" alt="Gambar Produk">
                        </div>
                        <div class="order-card-details">
                            <div class="order-card-row">
                                <div class="order-card-label">Jumlah:</div>
                                <div class="order-card-value">
                                    <i class="bi bi-box"></i> <?= htmlspecialchars($item['quantity']); ?>
                                    <?php if ($product_stock < $item['quantity']): ?>
                                    <div class="stock-warning">(Stok tidak cukup! Tersedia: <?= $product_stock ?>)</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="order-card-row">
                                <div class="order-card-label">Harga:</div>
                                <div class="order-card-value"><i class="bi bi-tag"></i> Rp <?= number_format($item['price'], 0, ',', '.'); ?></div>
                            </div>
                            <div class="order-card-row">
                                <div class="order-card-label">Total:</div>
                                <div class="order-card-value"><strong>Rp <?= number_format($item['total_price'], 0, ',', '.'); ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
        <?php } ?>
        
        <div class="order-summary-total">
            <div class="order-summary-total-label">Total Keseluruhan:</div>
            <div class="order-summary-total-value">Rp <?= number_format($grand_total_mobile, 0, ',', '.') ?></div>
        </div>
    </div>
</div>

<button type="submit" name="complete_order" class="btn-submit">
    <i class="bi bi-check-circle"></i> Selesaikan Pesanan
</button>
</form>

<!-- Back Button -->
<a href="<?= isset($_SESSION['back_to']) ? $_SESSION['back_to'] : 'costumer_home.php' ?>" class="btn-back">
    <i class="bi bi-arrow-left"></i> Kembali
</a>

<div class="copyright">
    &copy; <?= date("Y"); ?> Toko Bangunan. All rights reserved.
</div>

<script>
    // Toggle payment options based on selected method
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const bankDetails = document.getElementById('bank-details');
        const ewalletDetails = document.getElementById('ewallet-details');
        
        function updatePaymentDetails() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            bankDetails.classList.remove('active');
            ewalletDetails.classList.remove('active');
            
            if (selectedMethod === 'Transfer Bank') {
                bankDetails.classList.add('active');
            } else if (selectedMethod === 'E-Wallet') {
                ewalletDetails.classList.add('active');
            }
        }
        
        // Initial update
        updatePaymentDetails();
        
        // Add event listeners to payment methods
        paymentMethods.forEach(method => {
            method.addEventListener('change', updatePaymentDetails);
        });
    });
</script>

<style>
/* Add styles for error message */
.error-message {
    padding: 10px 15px;
    margin: 10px 0;
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.error-message i {
    font-size: 1.2rem;
}

.stock-warning {
    color: #721c24;
    font-size: 0.85em;
    display: block;
    margin-top: 2px;
}
</style>
</body>
</html>