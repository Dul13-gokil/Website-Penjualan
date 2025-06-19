<?php
include 'config/config.php';
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

$order_id = $_GET['order_id'] ?? '';
$username = $_SESSION['user_username'];

// Query untuk mendapatkan detail pembayaran berdasarkan order_id
$query = "SELECT * FROM payment_222060 
          WHERE id_222060 = ? AND username_222060 = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $order_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $payment = $result->fetch_assoc();
    
    // Format data untuk ditampilkan di modal
    echo '<div class="payment-detail">';
    echo '<h3 style="color: #007bff; margin-bottom: 20px;">Detail Pembayaran</h3>';

    echo '<div class="payment-detail-row">';
    echo '<div class="payment-detail-label">Nama Pelanggan:</div>';
    echo '<div class="payment-detail-value">' . htmlspecialchars($payment['username_222060']) . '</div>';
    echo '</div>';
    
    echo '<div class="payment-detail-row">';
    echo '<div class="payment-detail-label">ID Pembayaran:</div>';
    echo '<div class="payment-detail-value">' . htmlspecialchars($payment['id_222060']) . '</div>';
    echo '</div>';
    
    echo '<div class="payment-detail-row">';
    echo '<div class="payment-detail-label">Produk:</div>';
    echo '<div class="payment-detail-value">' . htmlspecialchars($payment['product_name_222060']) . '</div>';
    echo '</div>';
    
    echo '<div class="payment-detail-row">';
    echo '<div class="payment-detail-label">Jumlah:</div>';
    echo '<div class="payment-detail-value">' . $payment['quantity_222060'] . '</div>';
    echo '</div>';
    
    echo '<div class="payment-detail-row">';
    echo '<div class="payment-detail-label">Total Harga:</div>';
    echo '<div class="payment-detail-value">Rp ' . number_format($payment['total_price_222060'], 0, ',', '.') . '</div>';
    echo '</div>';
    
    echo '<div class="payment-detail-row">';
    echo '<div class="payment-detail-label">Metode Pembayaran:</div>';
    echo '<div class="payment-detail-value">' . htmlspecialchars($payment['payment_method_222060']) . '</div>';
    echo '</div>';


    
    echo '<div class="payment-detail-row">';
    echo '<div class="payment-detail-label">Status:</div>';
    
    // Status pembayaran dengan warna background yang diubah
    $paymentStatus = $payment['payment_confirmation_222060'];
    $bgColor = '';
    
    if ($paymentStatus == 'confirmed') {
        $bgColor = 'background-color: #4CAF50; color: white; padding: 3px 8px; border-radius: 4px;'; // Hijau
    } elseif ($paymentStatus == 'cancelled') {
        $bgColor = 'background-color: #f44336; color: white; padding: 3px 8px; border-radius: 4px;'; // Merah
    }
    
    echo '<div class="payment-detail-value"><span style="' . $bgColor . '">' . htmlspecialchars($paymentStatus) . '</span></div>';
    echo '</div>';
    
    echo '<div class="payment-detail-row">';
    echo '<div class="payment-detail-label">Tanggal Pesanan:</div>';
    echo '<div class="payment-detail-value">' . date('d-m-Y H:i', strtotime($payment['order_date_222060'])) . '</div>';
    echo '</div>';
    
    // Tampilkan bukti pembayaran jika ada
    if (!empty($payment['payment_proof_222060'])) {
        echo '<div class="payment-detail-row">';
        echo '<div class="payment-detail-label">Bukti Pembayaran:</div>';
        echo '<div class="payment-detail-value">';
        
        $file_ext = strtolower(pathinfo($payment['payment_proof_222060'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo '<div class="payment-proof-preview">';
            echo '<img src="uploads/payment_proofs/' . htmlspecialchars($payment['payment_proof_222060']) . '" class="payment-proof-image">';
            echo '</div>';
        } else {
            echo '<a href="uploads/payment_proofs/' . htmlspecialchars($payment['payment_proof_222060']) . '" target="_blank" class="btn-detail">';
            echo '<i class="fas fa-file-download"></i> Download Bukti Pembayaran';
            echo '</a>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    // Tampilkan gambar konfirmasi pesanan jika ada
    if (!empty($payment['arrival_confirmation_222060'])) {
        echo '<div class="payment-detail-row">';
        echo '<div class="payment-detail-label">Konfirmasi Pesanan:</div>';
        echo '<div class="payment-detail-value">';
        
        $file_ext = strtolower(pathinfo($payment['arrival_confirmation_222060'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo '<div class="payment-proof-preview">';
            echo '<img src="uploads/arrival_confirmations/' . htmlspecialchars($payment['arrival_confirmation_222060']) . '" class="payment-proof-image">';
            echo '</div>';
        } else {
            echo '<a href="uploads/arrival_confirmations/' . htmlspecialchars($payment['arrival_confirmation_222060']) . '" target="_blank" class="btn-detail">';
            echo '<i class="fas fa-file-download"></i> Download Konfirmasi Pesanan';
            echo '</a>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
} else {
    echo '<div class="alert alert-warning">Data pembayaran tidak ditemukan.</div>';
}
?>