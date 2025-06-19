<?php
include 'config/config.php';
session_start();

// Pastikan pelanggan sudah login
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user_username'];
$uploadMessage = '';

// Proses upload bukti pembayaran (hanya untuk non-COD)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_id'])) {
    $payment_id = $_POST['payment_id'];
    
    // Cek apakah pembayaran ini bukan COD sebelum proses upload
    $check_query = "SELECT payment_method_222060 FROM payment_222060 
                   WHERE id_222060 = ? AND username_222060 = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $payment_id, $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $payment_method = '';
    
    if ($check_result && $row = $check_result->fetch_assoc()) {
        $payment_method = $row['payment_method_222060'];
    }
    
    // Hanya proses jika bukan COD
    if ($payment_method != 'COD') {
        // Periksa jika ada file yang diupload
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'pdf');
            $filename = $_FILES['payment_proof']['name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Validasi ekstensi file
            if (in_array($file_ext, $allowed)) {
                // Buat nama unik untuk file
                $newname = 'payment_proof_' . $payment_id . '_' . time() . '.' . $file_ext;
                $target = 'uploads/payment_proofs/' . $newname;
                
                // Pastikan direktori ada
                if (!file_exists('uploads/payment_proofs/')) {
                    mkdir('uploads/payment_proofs/', 0777, true);
                }
                
                // Pindahkan file ke direktori target
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target)) {
                    // Update database dengan nama file bukti pembayaran dan ubah status menjadi 'Menunggu Konfirmasi'
                    $update_query = "UPDATE payment_222060 SET payment_proof_222060 = ?, order_status_222060 = 'Menunggu Konfirmasi' WHERE id_222060 = ? AND username_222060 = ?";
                    $stmt = $conn->prepare($update_query);
                    
                    if ($stmt === false) {
                        $uploadMessage = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
                    } else {
                        $stmt->bind_param("sis", $newname, $payment_id, $username);
                        
                        if ($stmt->execute()) {
                            $uploadMessage = '<div class="alert alert-success">Bukti pembayaran berhasil diunggah!</div>';
                        } else {
                            $uploadMessage = '<div class="alert alert-danger">Gagal mengupdate data pembayaran.</div>';
                        }
                    }
                } else {
                    $uploadMessage = '<div class="alert alert-danger">Gagal mengunggah file. Silakan coba lagi.</div>';
                }
            } else {
                $uploadMessage = '<div class="alert alert-danger">Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.</div>';
            }
        } else {
            $uploadMessage = '<div class="alert alert-danger">Silakan pilih file untuk diunggah.</div>';
        }
    }
}

// Proses untuk konfirmasi penerimaan pesanan COD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_cod']) && isset($_POST['payment_id'])) {
    $payment_id = $_POST['payment_id'];
    
    // Update status menjadi "confirmed" untuk COD
    $update_query = "UPDATE payment_222060 SET order_status_222060 = 'confirmed', 
                    payment_confirmation_222060 = 1
                    WHERE id_222060 = ? AND username_222060 = ? AND payment_method_222060 = 'COD'";
    $stmt = $conn->prepare($update_query);
    
    if ($stmt === false) {
        $uploadMessage = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    } else {
        $stmt->bind_param("is", $payment_id, $username);
        
        if ($stmt->execute()) {
            $uploadMessage = '<div class="alert alert-success">Pesanan COD berhasil dikonfirmasi!</div>';
        } else {
            $uploadMessage = '<div class="alert alert-danger">Gagal mengkonfirmasi pesanan COD.</div>';
        }
    }
}

// Proses untuk pembatalan pesanan COD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_cod']) && isset($_POST['payment_id'])) {
    $payment_id = $_POST['payment_id'];
    
    // Update status menjadi "Cancelled" untuk pembatalan COD
    $update_query = "UPDATE payment_222060 SET order_status_222060 = 'Cancelled', 
                    payment_confirmation_222060 = 'cancelled'
                    WHERE id_222060 = ? AND username_222060 = ? AND payment_method_222060 = 'COD'";
    $stmt = $conn->prepare($update_query);
    
    if ($stmt === false) {
        $uploadMessage = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    } else {
        $stmt->bind_param("is", $payment_id, $username);
        
        if ($stmt->execute()) {
            $uploadMessage = '<div class="alert alert-success">Pesanan COD berhasil dibatalkan!</div>';
        } else {
            $uploadMessage = '<div class="alert alert-danger">Gagal membatalkan pesanan COD.</div>';
        }
    }
}
// Proses untuk menghapus bukti pembayaran (hanya untuk non-COD)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_proof']) && isset($_POST['payment_id'])) {
    $payment_id = $_POST['payment_id'];
    
    // Ambil nama file bukti pembayaran
    $query = "SELECT payment_proof_222060, payment_method_222060 FROM payment_222060 WHERE id_222060 = ? AND username_222060 = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        $uploadMessage = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    } else {
        $stmt->bind_param("is", $payment_id, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            // Hanya proses jika bukan COD
            if ($row['payment_method_222060'] != 'COD') {
                $file_path = 'uploads/payment_proofs/' . $row['payment_proof_222060'];
                
                // Hapus file jika ada
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Update database untuk menghapus referensi bukti pembayaran dan kembalikan status ke Pending
                $update_query = "UPDATE payment_222060 SET payment_proof_222060 = NULL, order_status_222060 = 'Pending' WHERE id_222060 = ? AND username_222060 = ?";
                $stmt = $conn->prepare($update_query);
                
                if ($stmt === false) {
                    $uploadMessage = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
                } else {
                    $stmt->bind_param("is", $payment_id, $username);
                    
                    if ($stmt->execute()) {
                        $uploadMessage = '<div class="alert alert-success">Bukti pembayaran berhasil dihapus. Anda dapat mengupload ulang.</div>';
                    } else {
                        $uploadMessage = '<div class="alert alert-danger">Gagal menghapus data pembayaran.</div>';
                    }
                }
            }
        }
    }
}

// Ambil data pembayaran dari tabel payment_222060
$query = "SELECT p.id_222060, p.user_id_222060, p.username_222060, p.product_name_222060, 
                 p.quantity_222060, p.price_222060, p.total_price_222060, p.alamat_222060, 
                 p.payment_method_222060, p.order_status_222060, p.order_date_222060,
                 p.virtual_account_222060, p.payment_proof_222060, p.payment_confirmation_222060,
                 o.order_status_222060 as actual_order_status
          FROM payment_222060 p
          LEFT JOIN order_222060 o ON p.id_222060 = o.id_222060
          WHERE p.username_222060 = ? 
          AND (o.order_status_222060 IS NULL OR o.order_status_222060 != 'completed')
          AND (o.order_status_222060 IS NULL OR o.order_status_222060 != 'cancelled')";

$stmt = $conn->prepare($query);

// Periksa apakah prepare statement berhasil
if ($stmt === false) {
    die("Error in preparing statement: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pembayaran</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/payment.css">
    <style>
        /* Tambahan CSS untuk COD */
        .cod-section {
            background-color: #f2f9ff;
            border: 1px solid #c2e1ff;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .cod-title {
            display: flex;
            align-items: center;
            color: #0056b3;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .cod-title i {
            margin-right: 10px;
        }
        
        .cod-info {
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .cod-info-list {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 15px;
        }
        
        .cod-info-list li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .cod-info-list li i {
            color: #0056b3;
            margin-right: 10px;
            margin-top: 3px;
        }
        
        .cod-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-confirm-cod {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn-confirm-cod:hover {
            background-color: #218838;
        }
        
        .btn-cancel-cod {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn-cancel-cod:hover {
            background-color: #c82333;
        }
        
        .badge-cod-confirmed {
            background-color: #28a745;
        }
        
        .badge-cod-canceled {
            background-color: #dc3545;
        }

        /* Status badge untuk COD */
        .badge-cod {
            background-color: #ff9800;
        }
    </style>
</head>

<body>
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>

    <div class="container">
        <!-- Payment Header with Icon -->
        <div class="payment-header">
            <h2>Manajemen Pembayaran</h2>
        </div>
        
        <div class="username">
            <i class="bi bi-person"></i> Pengguna: <strong><?= htmlspecialchars($username) ?></strong>
        </div>
        
        <!-- Info note about payment -->
        <div class="info-note">
            <i class="fas fa-info-circle"></i> Halaman ini menampilkan riwayat pembayaran Anda. Anda dapat melihat status pembayaran dan detail transaksi.
        </div>

        <!-- Display upload messages if any -->
        <?php if (!empty($uploadMessage)) { echo $uploadMessage; } ?>

        <?php if ($result && $result->num_rows > 0) { ?>
            <!-- Payment Cards -->
            <div class="payment-cards">
                <?php 
                $total_all = 0;
                while ($row = $result->fetch_assoc()) {
                    $total_all += $row['total_price_222060'];
                    $virtual_account = $row['virtual_account_222060'];
                    $payment_proof = $row['payment_proof_222060'];
                    $is_cod = ($row['payment_method_222060'] == 'COD');
                    
                    // Menentukan kelas badge berdasarkan status
                    $badge_class = '';
                    $status_text = '';
                    $icon_class = '';
                    
                    if ($is_cod) {
                        // Status untuk pembayaran COD
                        if ($row['order_status_222060'] == 'Pending') {
                            $badge_class = 'badge-cod';
                            $status_text = 'Menunggu Konfirmasi COD';
                            $icon_class = 'fa-truck';
                        } elseif ($row['order_status_222060'] == 'confirmed') {
                            $badge_class = 'badge-cod-confirmed';
                            $status_text = 'Pesanan COD Dikonfirmasi';
                            $icon_class = 'fa-check-circle';
                        } elseif ($row['order_status_222060'] == 'Cancelled') {
                            $badge_class = 'badge-cod-canceled';
                            $status_text = 'Pesanan COD Dibatalkan';
                            $icon_class = 'fa-times-circle';
                        } else {
                            $badge_class = 'badge-cod';
                            $status_text = 'Pesanan COD';
                            $icon_class = 'fa-truck';
                        }
                    } else {
// Status untuk pembayaran non-COD
if ($row['payment_confirmation_222060'] == 'confirmed') {
    $badge_class = 'badge-confirmed';
    $status_text = 'Pembayaran Terkonfirmasi';
    $icon_class = 'fa-check-circle';
} elseif ($row['order_status_222060'] == 'Pending') {
    $badge_class = 'badge-pending';
    $status_text = 'Menunggu Pembayaran';
    $icon_class = 'fa-clock';
} elseif ($row['order_status_222060'] == 'Menunggu Konfirmasi') {
    $badge_class = 'badge-waiting';
    $status_text = 'Menunggu Konfirmasi Pembayaran';
    $icon_class = 'fa-hourglass-half';
} elseif ($row['order_status_222060'] == 'Cancelled') {
    $badge_class = 'badge-cod-canceled';
    $status_text = 'Pembayaran Dibatalkan';
    $icon_class = 'fa-times-circle';
} else {
    $badge_class = 'badge-pending';
    $status_text = $row['order_status_222060'];
    $icon_class = 'fa-info-circle';
}

                    }
                ?>
                <div class="payment-card">
                    <div class="payment-card-header">
                        <div>
                            <?php if ($is_cod) { ?>
                                <i class="fas fa-truck"></i>
                            <?php } else { ?>
                                <i class="fas fa-credit-card"></i>
                            <?php } ?>
                            Pembayaran #<?= $row['id_222060'] ?>
                        </div>
                        <span class="payment-badge <?= $badge_class ?>">
                            <i class="fas <?= $icon_class ?>"></i>
                            <?= $status_text ?>
                        </span>
                    </div>
                    <div class="payment-card-body">
                        <div class="payment-info">
                            <div class="payment-row">
                                <div class="payment-label">Produk:</div>
                                <div class="payment-value">
                                    <?= htmlspecialchars($row['product_name_222060']) ?>
                                </div>
                            </div>
                            <div class="payment-row">
                                <div class="payment-label">Jumlah:</div>
                                <div class="payment-value"><?= $row['quantity_222060'] ?> item</div>
                            </div>
                            <div class="payment-row">
                                <div class="payment-label">Total Harga:</div>
                                <div class="payment-value">Rp <?= number_format($row['total_price_222060'], 0, ',', '.') ?></div>
                            </div>
                            <div class="payment-row">
                                <div class="payment-label">Metode Pembayaran:</div>
                                <div class="payment-value">
                                    <?php if ($is_cod) { ?>
                                        <strong>COD (Cash On Delivery)</strong>
                                    <?php } else { ?>
                                        <?= htmlspecialchars($row['payment_method_222060']) ?>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="payment-row">
                                <div class="payment-label">Tanggal Pesanan:</div>
                                <div class="payment-value"><?= date('d-m-Y H:i', strtotime($row['order_date_222060'])) ?></div>
                            </div>
                            <div class="payment-row">
                                <div class="payment-label">Alamat:</div>
                                <div class="payment-value"><?= htmlspecialchars($row['alamat_222060']) ?></div>
                            </div>
                        </div>
                        
                        <?php if ($is_cod) { ?>
                        <!-- COD Section -->
                        <div class="cod-section">
                            <div class="cod-title">
                                <i class="fas fa-money-bill-wave"></i> Pembayaran COD (Cash On Delivery)
                            </div>
                            
                            <?php if ($row['order_status_222060'] == 'Pending') { ?>
                                <div class="cod-info">
                                    Pesanan Anda akan dikirim dengan metode COD (bayar di tempat). Silakan konfirmasi pesanan Anda untuk melanjutkan proses pengiriman.
                                </div>
                                
                                <ul class="cod-info-list">
                                    <li>
                                        <i class="fas fa-info-circle"></i>
                                        <span>Pembayaran dilakukan saat barang diterima</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-money-bill"></i>
                                        <span>Siapkan uang pas sebesar Rp <?= number_format($row['total_price_222060'], 0, ',', '.') ?></span>
                                    </li>
                                    <li>
                                        <i class="fas fa-truck"></i>
                                        <span>Estimasi pengiriman 2-4 hari kerja setelah konfirmasi</span>
                                    </li>
                                </ul>
                                
                                <div class="cod-buttons">
                                    <form action="" method="POST">
                                        <input type="hidden" name="payment_id" value="<?= $row['id_222060'] ?>">
                                        <input type="hidden" name="confirm_cod" value="1">
                                        <button type="submit" class="btn-confirm-cod">
                                            <i class="fas fa-check"></i> Konfirmasi Pesanan COD
                                        </button>
                                    </form>
                                    
                                    <form action="" method="POST" onsubmit="return confirmCancelCOD()">
                                        <input type="hidden" name="payment_id" value="<?= $row['id_222060'] ?>">
                                        <input type="hidden" name="cancel_cod" value="1">
                                        <button type="submit" class="btn-cancel-cod">
                                            <i class="fas fa-times"></i> Batalkan Pesanan
                                        </button>
                                    </form>
                                </div>
                            <?php } elseif ($row['order_status_222060'] == 'confirmed') { ?>
                                <div class="cod-info">
                                    <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                                    Pesanan COD Anda telah dikonfirmasi. Kurir akan segera mengirimkan pesanan Anda.
                                </div>
                                
                                <ul class="cod-info-list">
                                    <li>
                                        <i class="fas fa-info-circle"></i>
                                        <span>Pesanan sedang dalam proses pengiriman</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-money-bill"></i>
                                        <span>Siapkan uang pas sebesar Rp <?= number_format($row['total_price_222060'], 0, ',', '.') ?></span>
                                    </li>
                                    <li>
                                        <i class="fas fa-phone"></i>
                                        <span>Kurir akan menghubungi Anda sebelum pengiriman</span>
                                    </li>
                                </ul>
                            <?php } elseif ($row['order_status_222060'] == 'Cancelled') { ?>
                                <div class="cod-info">
                                    <i class="fas fa-times-circle" style="color: #dc3545;"></i> 
                                    Pesanan COD Anda telah dibatalkan.
                                </div>
                            <?php } ?>
                        </div>
                        <?php } else { ?>
                            <!-- Non-COD content -->
                            <?php if (!empty($virtual_account)) { ?>
                            <!-- Virtual Account Section -->
                            <div class="virtual-account">
                                <div class="virtual-account-title">
                                    <i class="fas fa-university"></i> Virtual Account
                                </div>
                                <div class="virtual-account-number">
                                    <?= htmlspecialchars($virtual_account) ?>
                                    <button class="copy-button" onclick="copyToClipboard('<?= htmlspecialchars($virtual_account) ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <?php } ?>
                            
                            <?php if ($row['order_status_222060'] == 'Pending') { ?>
                            <!-- Upload Payment Proof Section -->
                            <div class="payment-proof-section">
                                <div class="payment-proof-title">
                                    <i class="fas fa-upload"></i> Upload Bukti Pembayaran
                                </div>
                                <div class="payment-proof-form">
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="payment_id" value="<?= $row['id_222060'] ?>">
                                        <div class="form-group">
                                            <label for="payment_proof">Pilih File (JPG, JPEG, PNG, PDF)</label>
                                            <input type="file" name="payment_proof" id="payment_proof" required>
                                        </div>
                                        <button type="submit">Upload Bukti Pembayaran</button>
                                    </form>
                                </div>
                            </div>
                            <?php } elseif ($row['order_status_222060'] == 'Menunggu Konfirmasi' && !empty($payment_proof)) { ?>
                            <!-- Payment Proof Preview -->
                            <div class="payment-proof-section">
                                <div class="payment-proof-title">
                                    <i class="fas fa-file-image"></i> Bukti Pembayaran
                                </div>
                                <div class="payment-proof-preview">
                                    <?php
                                    $file_ext = strtolower(pathinfo($payment_proof, PATHINFO_EXTENSION));
                                    if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                                    ?>
                                    <img src="uploads/payment_proofs/<?= $payment_proof ?>" class="payment-proof-image" onclick="openModal('uploads/payment_proofs/<?= $payment_proof ?>')">
                                    <?php } else { ?>
                                    <a href="uploads/payment_proofs/<?= $payment_proof ?>" class="payment-proof-download" target="_blank">
                                        <i class="fas fa-file-pdf"></i> Lihat Bukti Pembayaran (PDF)
                                    </a>
                                    <?php } ?>
                                    
<?php if ($row['payment_confirmation_222060'] !== 'confirmed'): ?>
    <!-- Delete Proof Button -->
    <form action="" method="POST" onsubmit="return confirmDelete()">
        <input type="hidden" name="payment_id" value="<?= $row['id_222060'] ?>">
        <input type="hidden" name="delete_proof" value="1">
        <button type="submit" class="btn-delete-proof">
            <i class="fas fa-trash"></i> Hapus & Upload Ulang
        </button>
    </form>
<?php else: ?>
    <button class="btn-delete-proof" disabled style="opacity: 0.6; cursor: not-allowed;">
     Sudah Terkonfirmasi
    </button>
<?php endif; ?>


                                </div>
                            </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
            
            <!-- Total Summary Box -->
            <div class="payment-summary">
                <div class="payment-summary-label">Total Semua Pembayaran:</div>
                <div class="payment-summary-value">Rp <?= number_format($total_all, 0, ',', '.') ?></div>
            </div>
        <?php } else { ?>
            <!-- Empty Payment Message -->
            <div class="empty-payments">
                <i class="fas fa-shopping-cart"></i>
                <p>Anda belum memiliki riwayat pembayaran.</p>
            </div>
        <?php } ?>
        
<!-- Back & Payment Buttons -->
<div class="back-container">
  <a href="costumer_home.php" class="btn-back">
    <i class="fas fa-home"></i>Beranda
  </a>
  <a href="costumer_cart.php" class="btn-back">
  <i class="bi bi-cart"></i> Pesanan
  </a>
</div>

        
        <!-- Copyright -->
        <div class="copyright">
            &copy; <?= date("Y") ?> Toko Bangunan. All rights reserved.
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">Bukti Pembayaran</span>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <img id="modalImg" class="modal-img" src="" alt="Bukti Pembayaran">
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-title">
                <i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus
            </div>
            <div class="confirm-modal-text">
                Apakah Anda yakin ingin menghapus bukti pembayaran? Status akan kembali menjadi "Menunggu Pembayaran".
            </div>
            <div class="confirm-modal-buttons">
                <button id="cancelDelete" class="btn-cancel">Batal</button>
                <button id="confirmDelete" class="btn-confirm-delete">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <!-- COD Cancel Confirmation Modal -->
    <div id="confirmCODModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-title">
                <i class="fas fa-exclamation-triangle"></i> Konfirmasi Pembatalan
            </div>
            <div class="confirm-modal-text">
                Apakah Anda yakin ingin membatalkan pesanan COD ini?
            </div>
            <div class="confirm-modal-buttons">
                <button id="cancelCODCancel" class="btn-cancel">Tidak</button>
                <button id="confirmCODCancel" class="btn-confirm-delete">Ya, Batalkan</button>
            </div>
        </div>
    </div>

    <script>
        // Copy to clipboard function
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Show temporary notification
            alert('Virtual Account berhasil disalin!');
        }

        // Image modal functions
        function openModal(imgSrc) {
            document.getElementById('modalImg').src = imgSrc;
            document.getElementById('imageModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Delete confirmation modal variables
        const confirmModal = document.getElementById('confirmModal');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        let deleteForm = null;

        // Function to confirm delete
        function confirmDelete() {
            confirmModal.style.display = 'block';
            deleteForm = event.target;
            event.preventDefault();
            return false;
        }

        // Handle cancel delete
        cancelDeleteBtn.onclick = function() {
            confirmModal.style.display = 'none';
            deleteForm = null;
        }

        // Handle confirm delete
        confirmDeleteBtn.onclick = function() {
            if (deleteForm) {
                deleteForm.submit();
            }
            confirmModal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == confirmModal) {
                confirmModal.style.display = 'none';
            }
            if (event.target == document.getElementById('imageModal')) {
                document.getElementById('imageModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>

    <script>
        // Function to copy virtual account number to clipboard
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.innerText.trim();
            
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            // Show copied notification
            const button = element.querySelector('.copy-button');
            const originalIcon = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.style.color = '#28a745';
            
            setTimeout(() => {
                button.innerHTML = originalIcon;
                button.style.color = '#007bff';
            }, 2000);
        }
        
        // Animation for payment cards
        document.addEventListener("DOMContentLoaded", function() {
            const cards = document.querySelectorAll('.payment-card');
            cards.forEach((card, index) => {
                card.style.opacity = "0";
                card.style.transform = "translateY(20px)";
                card.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                
                setTimeout(() => {
                    card.style.opacity = "1";
                    card.style.transform = "translateY(0)";
                }, 100 + (index * 100));
            });
        });

        
    </script>
</body>
</html>