<?php
session_start();
if (!isset($_SESSION['super_admin_logged_in']) || $_SESSION['super_admin_logged_in'] !== true) {
    header("Location: ../costumer_login.php");
    exit();
}
include '../config/config.php';

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Verifikasi bahwa ID diberikan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];

// Ambil data pesanan berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM order_222060 WHERE id_222060 = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();

// Fetch payment data that matches this order
$payment_stmt = $conn->prepare("SELECT * FROM payment_222060 
                               WHERE user_id_222060 = ? 
                               AND product_name_222060 = ? 
                               AND total_price_222060 = ?
                               AND quantity_222060 = ?");

if (!$payment_stmt) {
    echo "Error preparing payment statement: " . $conn->error;
    $payment_result = null;
    $payment_data = null;
} else {
    $payment_stmt->bind_param("isdi", 
        $order['user_id_222060'], 
        $order['product_name_222060'], 
        $order['total_price_222060'],
        $order['quantity_222060']
    );
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();
    $payment_data = $payment_result->fetch_assoc();
}

// Periksa apakah bukti pembayaran tersedia
$has_payment_proof = false;
if ($payment_data && !empty($payment_data['payment_proof_222060'])) {
    $has_payment_proof = true;
}

// Periksa apakah pembayaran sudah dikonfirmasi
$is_payment_confirmed = false;
if ($payment_data && $payment_data['payment_confirmation_222060'] == 'confirmed') {
    $is_payment_confirmed = true;
}

// Periksa apakah ini pesanan COD
$is_cod = ($order['payment_method_222060'] == 'COD');

// Periksa apakah pesanan berstatus processed
$is_processed = ($order['order_status_222060'] == 'processed');
$is_pending = ($order['order_status_222060'] == 'pending');

// Periksa apakah konfirmasi pengiriman tersedia
$has_arrival_confirmation = false;
if ($payment_data && !empty($payment_data['arrival_confirmation_222060'])) {
    $has_arrival_confirmation = true;
}

// Cek apakah status order sudah completed atau cancelled
if ($order['order_status_222060'] == 'completed' || $order['order_status_222060'] == 'cancelled') {
    $_SESSION['error_message'] = "Pesanan yang sudah selesai atau dibatalkan tidak dapat diedit.";
    header("Location: orders.php");
    exit();
}


// Proses konfirmasi pembayaran
if (isset($_POST['confirm_payment']) && isset($payment_data)) {
    // Check if connection is valid first
    if (!$conn) {
        $error_message = "Database connection error.";
    } else {
        // Update status pembayaran di database
        $update_payment = $conn->prepare("UPDATE payment_222060 
                                          SET order_status_222060 = 'Pembayaran Terkonfirmasi', 
                                              payment_confirmation_222060 = 'confirmed'
                                          WHERE id_222060 = ?");
                                          
        if ($update_payment === false) {
            $error_message = "Prepare statement failed: " . $conn->error;
        } else {
            $update_payment->bind_param("i", $payment_data['id_222060']);

            if ($update_payment->execute()) {
                $_SESSION['success_message'] = "Pembayaran berhasil dikonfirmasi!";

                // Setelah update sukses, redirect ke halaman yang sama agar data refresh
                header("Location: edit_order.php?id=" . $payment_data['order_id_222060']);
                exit();
            } else {
                $error_message = "Gagal mengonfirmasi pembayaran: " . $conn->error;
            }
        }
    }
}



// Proses upload konfirmasi pengiriman
if (isset($_POST['upload_arrival_confirmation'])) {
    // Cek apakah sudah ada data payment
    if ($payment_data) {
        // Proses upload gambar
        $target_dir = "../uploads/arrival_confirmations/";
        
        // Buat direktori jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Cek apakah file yang diupload adalah gambar
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($_FILES['arrival_confirmation']['type'], $allowed_types)) {
            $file_name = time() . '_' . basename($_FILES['arrival_confirmation']['name']);
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES['arrival_confirmation']['tmp_name'], $target_file)) {
                // Update data di tabel payment
                $update_arrival = $conn->prepare("UPDATE payment_222060 
                                              SET arrival_confirmation_222060 = ? 
                                              WHERE id_222060 = ?");
                $update_arrival->bind_param("si", $file_name, $payment_data['id_222060']);
                
                if ($update_arrival->execute()) {
                    $_SESSION['success_message'] = "Konfirmasi pengiriman berhasil diunggah!";
                    
                    // Refresh payment data
                    $payment_stmt->execute();
                    $payment_result = $payment_stmt->get_result();
                    $payment_data = $payment_result->fetch_assoc();
                    $has_arrival_confirmation = !empty($payment_data['arrival_confirmation_222060']);
                    
                    // Redirect kembali ke halaman yang sama untuk refresh data
                    header("Location: edit_order.php?id=" . $order_id);
                    exit();
                } else {
                    $error_message = "Gagal menyimpan konfirmasi pengiriman ke database: " . $conn->error;
                }
            } else {
                $error_message = "Gagal mengupload file konfirmasi pengiriman.";
            }
        } else {
            $error_message = "Hanya file gambar (JPG, JPEG, PNG) yang diperbolehkan.";
        }
    } else {
        // Jika belum ada data payment, buat data payment baru terlebih dahulu
        // Ini umumnya untuk pesanan COD
        // Proses upload gambar terlebih dahulu
        $target_dir = "../uploads/arrival_confirmations/";
        
        // Buat direktori jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Cek apakah file yang diupload adalah gambar
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($_FILES['arrival_confirmation']['type'], $allowed_types)) {
            $file_name = time() . '_' . basename($_FILES['arrival_confirmation']['name']);
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES['arrival_confirmation']['tmp_name'], $target_file)) {
                // Insert data payment baru dengan konfirmasi pengiriman
                $insert_payment = $conn->prepare("INSERT INTO payment_222060 
                                              (user_id_222060, product_name_222060, total_price_222060, 
                                              quantity_222060, payment_status_222060, arrival_confirmation_222060) 
                                              VALUES (?, ?, ?, ?, 'Menunggu Pembayaran', ?)");
                $insert_payment->bind_param("isdis", 
                    $order['user_id_222060'], 
                    $order['product_name_222060'], 
                    $order['total_price_222060'],
                    $order['quantity_222060'],
                    $file_name
                );
                
                if ($insert_payment->execute()) {
                    $_SESSION['success_message'] = "Konfirmasi pengiriman berhasil diunggah!";
                    
                    // Refresh payment data
                    $payment_stmt->execute();
                    $payment_result = $payment_stmt->get_result();
                    $payment_data = $payment_result->fetch_assoc();
                    $has_arrival_confirmation = !empty($payment_data['arrival_confirmation_222060']);
                    
                    // Redirect kembali ke halaman yang sama untuk refresh data
                    header("Location: edit_order.php?id=" . $order_id);
                    exit();
                } else {
                    $error_message = "Gagal menyimpan data pembayaran dan konfirmasi pengiriman: " . $conn->error;
                }
            } else {
                $error_message = "Gagal mengupload file konfirmasi pengiriman.";
            }
        } else {
            $error_message = "Hanya file gambar (JPG, JPEG, PNG) yang diperbolehkan.";
        }
    }
}

// Proses hapus konfirmasi pengiriman
if (isset($_POST['delete_arrival_confirmation']) && isset($payment_data) && $has_arrival_confirmation) {
    // Dapatkan nama file gambar
    $image_file = $payment_data['arrival_confirmation_222060'];
    $file_path = "../uploads/arrival_confirmations/" . $image_file;
    
    // Hapus file jika ada
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Update data di database untuk menghapus referensi gambar
    $delete_arrival = $conn->prepare("UPDATE payment_222060 
                                   SET arrival_confirmation_222060 = NULL 
                                   WHERE id_222060 = ?");
    $delete_arrival->bind_param("i", $payment_data['id_222060']);
    
    if ($delete_arrival->execute()) {
        $_SESSION['success_message'] = "Konfirmasi pengiriman berhasil dihapus!";
        
        // Refresh payment data
        $payment_stmt->execute();
        $payment_result = $payment_stmt->get_result();
        $payment_data = $payment_result->fetch_assoc();
        $has_arrival_confirmation = false; // Update status
        
        // Redirect kembali ke halaman yang sama untuk refresh data
        header("Location: edit_order.php?id=" . $order_id);
        exit();
    } else {
        $error_message = "Gagal menghapus konfirmasi pengiriman: " . $conn->error;
    }
}

// Proses manual update konfirmasi COD
if (isset($_POST['update_cod_confirmation'])) {
    $confirmation_status = $_POST['cod_confirmation'] === 'confirmed' ? 'confirmed' : 'not_confirmed';
    
    // Cek apakah sudah ada data pembayaran
    if ($payment_data) {
        // Update status konfirmasi di data pembayaran yang sudah ada
        $update_confirmation = $conn->prepare("UPDATE payment_222060 
                                            SET payment_confirmation_222060 = ? 
                                            WHERE id_222060 = ?");
        $update_confirmation->bind_param("si", $confirmation_status, $payment_data['id_222060']);
    } else {
        // Buat data pembayaran baru untuk pesanan COD
        $update_confirmation = $conn->prepare("INSERT INTO payment_222060 
                                            (user_id_222060, product_name_222060, total_price_222060, 
                                            quantity_222060, payment_status_222060, payment_confirmation_222060) 
                                            VALUES (?, ?, ?, ?, 'Menunggu Pembayaran COD', ?)");
        $update_confirmation->bind_param("isdis", 
            $order['user_id_222060'], 
            $order['product_name_222060'], 
            $order['total_price_222060'],
            $order['quantity_222060'],
            $confirmation_status
        );
    }
    
    if ($update_confirmation->execute()) {
        $_SESSION['success_message'] = "Status konfirmasi COD berhasil diperbarui!";
        
        // Refresh data pembayaran setelah update
        if ($payment_stmt) {
            $payment_stmt->execute();
            $payment_result = $payment_stmt->get_result();
            $payment_data = $payment_result->fetch_assoc();
        }
        
        // Redirect kembali ke halaman yang sama untuk refresh data
        header("Location: edit_order.php?id=" . $order_id);
        exit();
    } else {
        $error_message = "Gagal memperbarui status konfirmasi COD: " . $conn->error;
    }
}

// Proses update status pesanan
if (isset($_POST['update_order'])) {
    $new_status = $_POST['order_status'];
    
    // Cek apakah pesanan adalah COD dan belum dikonfirmasi oleh pelanggan
    $is_cod_not_confirmed = false;
    if ($is_cod) {
        if ($payment_data) {
            if ($payment_data['payment_confirmation_222060'] != 'confirmed') {
                $is_cod_not_confirmed = true;
            }
        } else {
            $is_cod_not_confirmed = true; // Tidak ada data pembayaran berarti belum dikonfirmasi
        }
    }
    
    // Cek pembayaran online tanpa bukti pembayaran atau konfirmasi
    $is_online_without_proof = false;
    $is_online_not_confirmed = false;
    
    if (!$is_cod) {
        if (!$has_payment_proof && $new_status != 'cancelled' && $new_status != 'pending') {
            $is_online_without_proof = true;
        }
        
        if ($has_payment_proof && !$is_payment_confirmed && $new_status != 'cancelled' && $new_status != 'pending') {
            $is_online_not_confirmed = true;
        }
    }
    
    // Jika COD dan belum dikonfirmasi, tolak perubahan status kecuali cancelled
    if ($is_cod_not_confirmed && $new_status != 'cancelled') {
        $error_message = "Status pesanan COD tidak dapat diubah karena pelanggan belum mengonfirmasi pesanan.";
    } 
    // Jika pembayaran online tanpa bukti pembayaran, tolak perubahan status kecuali cancelled atau pending
    elseif ($is_online_without_proof) {
        $error_message = "Status pesanan tidak dapat diubah menjadi processed/completed karena belum ada bukti pembayaran.";
    }
    // Jika pembayaran belum dikonfirmasi, tolak perubahan status kecuali cancelled atau pending
    elseif ($is_online_not_confirmed) {
        $error_message = "Status pesanan tidak dapat diubah menjadi processed/completed karena pembayaran belum dikonfirmasi.";
    }
    // Jika status akan diubah menjadi completed tapi belum ada konfirmasi pengiriman
    elseif ($new_status == 'completed' && !$has_arrival_confirmation) {
        $error_message = "Status tidak dapat diubah menjadi completed karena belum ada konfirmasi pengiriman.";
    }
    // Jika ada konfirmasi pengiriman tapi status bukan completed atau cancelled
    elseif ($has_arrival_confirmation && $new_status != 'completed' && $new_status != 'cancelled') {
        $error_message = "Karena sudah ada konfirmasi pengiriman, status hanya bisa diubah menjadi completed atau cancelled.";
    }
    else {
        // Update status pesanan di database
        $update_stmt = $conn->prepare("UPDATE order_222060 SET order_status_222060 = ? WHERE id_222060 = ?");
        $update_stmt->bind_param("si", $new_status, $order_id);
        
        if ($update_stmt->execute()) {
            // Jika status diubah menjadi cancelled untuk pesanan COD, update payment_confirmation_222060
            if ($new_status == 'cancelled' && $is_cod) {
                if ($payment_data) {
                    // Update existing payment record
                    $update_payment = $conn->prepare("UPDATE payment_222060 
                                                SET payment_confirmation_222060 = 'cancelled',
                                                    payment_status_222060 = 'Pesanan Dibatalkan'
                                                WHERE id_222060 = ?");
                    $update_payment->bind_param("i", $payment_data['id_222060']);
                    $update_payment->execute();
                } else {
                    // Create new payment record with cancelled status
                    $insert_payment = $conn->prepare("INSERT INTO payment_222060 
                                                (user_id_222060, product_name_222060, total_price_222060, 
                                                quantity_222060, payment_status_222060, payment_confirmation_222060) 
                                                VALUES (?, ?, ?, ?, 'Pesanan Dibatalkan', 'cancelled')");
                    $insert_payment->bind_param("isdi", 
                        $order['user_id_222060'], 
                        $order['product_name_222060'], 
                        $order['total_price_222060'],
                        $order['quantity_222060']
                    );
                    $insert_payment->execute();
                }
            }
            
            // Jika status diubah menjadi completed atau cancelled, tambahkan ke laporan
            if ($new_status == 'completed' || $new_status == 'cancelled') {
                // Tentukan catatan berdasarkan status
                $note_text = ($new_status == 'completed') ? "Pesanan Selesai" : "Dibatalkan";
                
                // Periksa apakah sudah ada di laporan
                $check_report = $conn->prepare("SELECT 1 FROM order_report_222060 WHERE order_id_222060 = ?");
                $check_report->bind_param("i", $order_id);
                $check_report->execute();
                $report_result = $check_report->get_result();
                
                // Jika belum ada di laporan, tambahkan
                if ($report_result->num_rows === 0) {
                    $insert_report = $conn->prepare("INSERT INTO order_report_222060 
                                                   (order_id_222060, customer_name_222060, product_222060, 
                                                    quantity_222060, price_222060, status_222060, 
                                                    completion_date_222060, notes_222060) 
                                                   VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
                    
                    $insert_report->bind_param("issiiss", 
                        $order_id,
                        $order['username_222060'],
                        $order['product_name_222060'],
                        $order['quantity_222060'],
                        $order['total_price_222060'],
                        $new_status,
                        $note_text
                    );
                    
                    $insert_report->execute();
                }
            }
            
            // Set pesan sukses
            $_SESSION['success_message'] = "Status pesanan berhasil diperbarui!";
            
            // Redirect kembali ke halaman orders
            header("Location: orders.php");
            exit();
        } else {
            $error_message = "Gagal memperbarui status pesanan: " . $conn->error;
        }
    }
}

// Cek status konfirmasi untuk pesanan COD
$cod_confirmation_status = "Belum Mengonfirmasi";
if ($is_cod) {
    if (isset($payment_data)) {
        if ($payment_data['payment_confirmation_222060'] == 'confirmed') {
            $cod_confirmation_status = "Telah Mengonfirmasi";
        } elseif ($payment_data['payment_confirmation_222060'] == 'cancelled') {
            $cod_confirmation_status = "Pesanan Dibatalkan";
        }
    }
}

// Check if payment needs confirmation
$needs_payment_confirmation = false;
if ($has_payment_proof && $payment_data && isset($payment_data['payment_confirmation_222060']) && $payment_data['payment_confirmation_222060'] != 'confirmed') {
    $needs_payment_confirmation = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Status Pesanan</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/edit_order.css">
    <style>
        .confirmation-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        .confirmation-pending {
            background-color: #ffcccc; /* merah terang */
            color: #990000;            /* merah gelap */
            border: 1px solid #f5c6cb;
        }

        .confirmation-confirmed {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .cod-confirmation-box {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .cod-confirmation-box h4 {
            margin-top: 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        .radio-option {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .radio-option input {
            margin-right: 10px;
        }
        .arrival-confirmation-section {
            background-color: #e8f4ff;
            border: 1px solid #b8daff;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .arrival-confirmation-title {
            color: #0c5460;
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .upload-form {
            background-color: #f8f9fa;
            border: 1px dashed #adb5bd;
            border-radius: 6px;
            padding: 20px;
            margin-top: 15px;
            text-align: center;
        }
        .upload-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
        }
        .upload-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .upload-button:hover {
            background-color: #218838;
        }
        .arrival-image-container {
            max-width: 100%;
            overflow: hidden;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 15px 0;
        }
        .arrival-image {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .arrival-image:hover {
            transform: scale(1.02);
        }
        .delete-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .delete-button:hover {
            background-color: #c82333;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .payment-proof-section {
            background-color: #e8f4ff;
            border: 1px solid #b8daff;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .payment-proof-title {
            color: #0c5460;
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .payment-image-container {
            max-width: 100%;
            overflow: hidden;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 15px 0;
        }
        .payment-image {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .payment-image:hover {
            transform: scale(1.02);
        }
        .payment-status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 10px;
        }
        .confirm-payment-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .confirm-payment-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<!-- Background Pattern -->
<div class="bg-pattern"></div>

<!-- Image Modal -->
<div id="imageModal" class="modal">
    <span class="close" id="closeModal">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<!-- Tombol Menu --> 
<div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-bars" id="menu-icon"></i>
</div>

<!-- Sidebar -->
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
<!-- Konten Utama -->
<main class="content">
    <section>
        <h2><i class="bi bi-pencil-square"></i> Edit Status Pesanan</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <div class="order-details">
            <h3 style="margin-bottom: 15px;">Detail Pesanan #<?= $order_id ?></h3>
            
            <div class="detail-row">
                <div class="detail-label">Username</div>
                <div class="detail-value"><?= htmlspecialchars($order['username_222060']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Produk</div>
                <div class="detail-value"><?= htmlspecialchars($order['product_name_222060']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Jumlah</div>
                <div class="detail-value"><?= htmlspecialchars($order['quantity_222060']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Total Harga</div>
                <div class="detail-value">Rp<?= number_format($order['total_price_222060'], 0, ',', '.') ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Metode Pembayaran</div>
                <div class="detail-value"><?= htmlspecialchars($order['payment_method_222060']) ?></div>
            </div>
            
            <?php if ($is_cod): ?>
            <div class="detail-row">
                <div class="detail-label">Status Konfirmasi COD</div>
                <div class="detail-value">
                    <span class="confirmation-badge <?= $cod_confirmation_status == 'Telah Mengonfirmasi' ? 'confirmation-confirmed' : 'confirmation-pending' ?>">
                         <?= $cod_confirmation_status ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
        
            
            <div class="detail-row">
                <div class="detail-label">Alamat Pengiriman</div>
                <div class="detail-value"><?= htmlspecialchars($order['alamat_222060']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Tanggal Pesan</div>
                <div class="detail-value"><?= htmlspecialchars($order['order_date_222060']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Status Saat Ini</div>
                <div class="detail-value">
                    <span class="status-badge status-<?= strtolower($order['order_status_222060']) ?>">
                        <?= htmlspecialchars($order['order_status_222060']) ?>
                    </span>
                </div>
            </div>
        </div>
        
<!-- Bagian Bukti Pembayaran -->
<?php
// Proses konfirmasi pembayaran
if (isset($_POST['confirm_payment']) && isset($payment_data)) {
    if (!$conn) {
        $error_message = "Database connection error.";
    } else {
        $update_payment = $conn->prepare("UPDATE payment_222060 
                                          SET order_status_222060 = 'Pembayaran Terkonfirmasi', 
                                              payment_confirmation_222060 = 'confirmed'
                                          WHERE id_222060 = ?");
        if ($update_payment === false) {
            $error_message = "Prepare statement failed: " . $conn->error;
        } else {
            $update_payment->bind_param("i", $payment_data['id_222060']);
            if ($update_payment->execute()) {
                $_SESSION['success_message'] = "Pembayaran berhasil dikonfirmasi!";
                header("Location: edit_order.php?id=" . $payment_data['order_id_222060']);
                exit();
            } else {
                $error_message = "Gagal mengonfirmasi pembayaran: " . $conn->error;
            }
        }
    }
}
?>

<?php if ($payment_result && $payment_data && !$is_cod): ?>
<div class="payment-proof-section">
    <h3 class="payment-proof-title"><i class="bi bi-credit-card-2-front"></i> Bukti Pembayaran</h3>
    
    <?php if (!empty($payment_data['payment_proof_222060'])): ?>
    <div class="payment-image-container">
        <img src="../uploads/payment_proofs/<?= htmlspecialchars($payment_data['payment_proof_222060']) ?>" 
             alt="Bukti Pembayaran" 
             class="payment-image" 
             id="paymentProofImage">
    </div>
    
    <div class="payment-status">
        <span class="payment-status-badge <?= $payment_data['payment_confirmation_222060'] == 'confirmed' ? 'confirmation-confirmed' : 'confirmation-pending' ?>">
            <?= $payment_data['payment_confirmation_222060'] == 'confirmed' ? 'Pembayaran Dikonfirmasi' : 'Menunggu Konfirmasi' ?>
        </span>
    </div>
    
    <?php if ($payment_data['payment_confirmation_222060'] != 'confirmed'): ?>
    <form method="POST">
        <button type="submit" name="confirm_payment" class="confirm-payment-button">
            <i class="bi bi-check-circle"></i> Konfirmasi Pembayaran
        </button>
    </form>
    <?php endif; ?>
    
    <?php else: ?>
    <p>Belum ada bukti pembayaran yang diunggah.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

        
<!-- Bagian Konfirmasi Pengiriman - Hanya tampilkan jika status sudah processed -->
<?php 
$show_arrival_confirmation = false;
// Hanya tampilkan konfirmasi pengiriman jika status bukan pending
if ($order['order_status_222060'] != 'pending') {
    $show_arrival_confirmation = true;
}
?>

<?php if ($show_arrival_confirmation): ?>
<div class="arrival-confirmation-section">
    <h3 class="arrival-confirmation-title"><i class="bi bi-truck"></i> Konfirmasi Pengiriman</h3>
    
    <?php if ($has_arrival_confirmation): ?>
    <div class="arrival-image-container">
        <img src="../uploads/arrival_confirmations/<?= htmlspecialchars($payment_data['arrival_confirmation_222060']) ?>" 
             alt="Konfirmasi Pengiriman" 
             class="arrival-image" 
             id="arrivalConfirmationImage">
    </div>
    <p>Konfirmasi pengiriman telah diunggah.</p>
    
    <!-- Tombol untuk menghapus konfirmasi pengiriman -->
    <form method="POST" class="delete-form">
        <button type="submit" name="delete_arrival_confirmation" class="delete-button">
            <i class="bi bi-trash"></i> Hapus Konfirmasi Pengiriman
        </button>
    </form>
    
    <!-- Informasi bahwa status hanya bisa diubah menjadi completed atau cancelled -->
    <div class="alert alert-warning" style="margin-top: 15px;">
        <i class="bi bi-exclamation-triangle"></i> Karena sudah ada konfirmasi pengiriman, status hanya bisa diubah menjadi completed atau cancelled.
    </div>
    
    <?php else: ?>
    <p>Belum ada konfirmasi pengiriman yang diunggah.</p>
    
    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <label for="arrival_confirmation" class="upload-label">
            <i class="bi bi-image"></i> Pilih Foto Konfirmasi Pengiriman
        </label>
        <input type="file" name="arrival_confirmation" id="arrival_confirmation" accept="image/*" required>
        <button type="submit" name="upload_arrival_confirmation" class="upload-button">
            <i class="bi bi-upload"></i> Unggah Konfirmasi
        </button>
    </form>
    <?php endif; ?>
</div>
<?php else: ?>
<!-- Pesan informasi bahwa konfirmasi pengiriman akan tersedia setelah status processed -->
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Konfirmasi pengiriman akan tersedia setelah status pesanan diubah menjadi "Processed".
</div>
<?php endif; ?>

<!-- Form untuk mengubah status pesanan -->
<form method="POST" class="edit-form">
    <?php if ($is_cod && $cod_confirmation_status == 'Belum Mengonfirmasi'): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Pelanggan belum mengonfirmasi pesanan COD. Status hanya dapat diubah menjadi cancelled.
    </div>
    <?php endif; ?>
    
    <?php if (!$is_cod && !$has_payment_proof): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Belum ada bukti pembayaran. Status hanya dapat diubah menjadi pending atau cancelled.
    </div>
    <?php endif; ?>

    <?php if (!$is_cod && $has_payment_proof && !$is_payment_confirmed): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Pembayaran belum dikonfirmasi. Status tidak dapat diubah menjadi processed/completed/cancelled.
    </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label class="form-label">Ubah Status Pesanan</label>
        <div class="status-options">
            <input type="radio" name="order_status" id="status-pending" value="pending" class="status-option" 
                   <?= $order['order_status_222060'] == 'pending' ? 'checked' : '' ?>
                   <?= $has_arrival_confirmation ? 'disabled' : '' ?>>
            <label for="status-pending" <?= $has_arrival_confirmation ? 'style="opacity:0.5"' : '' ?>>
                <i class="bi bi-hourglass-split"></i> Pending
            </label>
            
            <input type="radio" name="order_status" id="status-processed" value="processed" class="status-option" 
                   <?= $order['order_status_222060'] == 'processed' ? 'checked' : '' ?>
                   <?= (($is_cod && $cod_confirmation_status == 'Belum Mengonfirmasi') || 
                        ($cod_confirmation_status == 'Pesanan Dibatalkan') ||
                        (!$is_cod && !$has_payment_proof) ||
                        (!$is_cod && $has_payment_proof && !$is_payment_confirmed) ||
                        $has_arrival_confirmation) ? 'disabled' : '' ?>>
            <label for="status-processed" <?= (($is_cod && $cod_confirmation_status == 'Belum Mengonfirmasi') || 
                                               ($cod_confirmation_status == 'Pesanan Dibatalkan') ||
                                               (!$is_cod && !$has_payment_proof) ||
                                               (!$is_cod && $has_payment_proof && !$is_payment_confirmed) ||
                                               $has_arrival_confirmation) ? 'style="opacity:0.5"' : '' ?>>
                <i class="bi bi-truck"></i> Processed
            </label>
            
            <input type="radio" name="order_status" id="status-completed" value="completed" class="status-option" 
                   <?= $order['order_status_222060'] == 'completed' ? 'checked' : '' ?>
                   <?= (($is_cod && $cod_confirmation_status == 'Belum Mengonfirmasi') || 
                        ($cod_confirmation_status == 'Pesanan Dibatalkan') ||
                        (!$is_cod && !$has_payment_proof) ||
                        (!$is_cod && $has_payment_proof && !$is_payment_confirmed) ||
                        !$has_arrival_confirmation) ? 'disabled' : '' ?>>
            <label for="status-completed" <?= (($is_cod && $cod_confirmation_status == 'Belum Mengonfirmasi') || 
                                               ($cod_confirmation_status == 'Pesanan Dibatalkan') ||
                                               (!$is_cod && !$has_payment_proof) ||
                                               (!$is_cod && $has_payment_proof && !$is_payment_confirmed) ||
                                               !$has_arrival_confirmation) ? 'style="opacity:0.5"' : '' ?>>
                <i class="bi bi-check-circle"></i> Completed
            </label>
            
            <input type="radio" name="order_status" id="status-cancelled" value="cancelled" class="status-option" 
                   <?= $order['order_status_222060'] == 'cancelled' ? 'checked' : '' ?>
                   <?= (!$is_cod && $has_payment_proof && !$is_payment_confirmed) ? 'disabled' : '' ?>
                   <?= $cod_confirmation_status == 'Pesanan Dibatalkan' ? 'checked' : '' ?>>
            <label for="status-cancelled" <?= (!$is_cod && $has_payment_proof && !$is_payment_confirmed) ? 'style="opacity:0.5"' : '' ?>>
                <i class="bi bi-x-circle"></i> Cancelled
            </label>
        </div>
    </div>
    
    <div class="button-group">
        <button type="submit" name="update_order" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan Perubahan
        </button>
        <a href="orders.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</form>
    </section>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Toggle sidebar
        document.getElementById("menu-toggle").addEventListener("click", function() {
            let sidebar = document.getElementById("sidebar");
            let menuIcon = document.getElementById("menu-icon");

            sidebar.classList.toggle("open");

            // Ubah icon menjadi X atau kembali menjadi bars
            if (sidebar.classList.contains("open")) {
                menuIcon.classList.remove("fa-bars");
                menuIcon.classList.add("fa-times");
                menuIcon.style.color = "#fff";
                menuIcon.style.marginTop = "13px"; 
                menuIcon.style.fontSize = "30px";
            } else {
                menuIcon.classList.remove("fa-times");
                menuIcon.classList.add("fa-bars");
                menuIcon.style.color = "#343a40";
                menuIcon.style.marginTop = "0"; 
                menuIcon.style.fontSize = "28px";
            }
        });

        // Add animation to the order details
        const detailRows = document.querySelectorAll('.detail-row');
        detailRows.forEach((row, index) => {
            row.style.opacity = "0";
            row.style.transform = "translateY(20px)";
            row.style.transition = "opacity 0.3s ease, transform 0.3s ease";
            
            setTimeout(() => {
                row.style.opacity = "1";
                row.style.transform = "translateY(0)";
            }, 100 + (index * 50));
        });
        
        // Modal Image Preview for Payment Proof
        const paymentProofImage = document.getElementById("paymentProofImage");
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        const closeModal = document.getElementById("closeModal");
        
        // Setup event listeners for payment proof if element exists
        if (paymentProofImage) {
            paymentProofImage.onclick = function() {
                modal.style.display = "block";
                modalImg.src = this.src;
            }
        }
        
        // Modal Image Preview for Arrival Confirmation
        const arrivalConfirmationImage = document.getElementById("arrivalConfirmationImage");
        
        // Setup event listeners for arrival confirmation if element exists
        if (arrivalConfirmationImage) {
            arrivalConfirmationImage.onclick = function() {
                modal.style.display = "block";
                modalImg.src = this.src;
            }
        }
        
        if (closeModal) {
            closeModal.onclick = function() {
                modal.style.display = "none";
            }
        }
        
        // Close modal when clicking outside the image
        if (modal) {
            modal.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            }
        }
        
        // Preview image before upload
        const fileInput = document.getElementById("arrival_confirmation");
        if (fileInput) {
            fileInput.addEventListener("change", function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Create preview element if it doesn't exist
                        let previewContainer = document.querySelector(".preview-container");
                        
                        if (!previewContainer) {
                            previewContainer = document.createElement("div");
                            previewContainer.className = "preview-container";
                            previewContainer.style.marginTop = "15px";
                            previewContainer.style.textAlign = "center";
                            
                            const previewImage = document.createElement("img");
                            previewImage.style.maxWidth = "100%";
                            previewImage.style.maxHeight = "200px";
                            previewImage.style.borderRadius = "4px";
                            previewImage.style.boxShadow = "0 2px 4px rgba(0,0,0,0.1)";
                            
                            previewContainer.appendChild(previewImage);
                            fileInput.parentNode.insertBefore(previewContainer, fileInput.nextSibling);
                        }
                        
                        const previewImage = previewContainer.querySelector("img");
                        previewImage.src = e.target.result;
                        previewContainer.style.display = "block";
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>

</body>
</html>