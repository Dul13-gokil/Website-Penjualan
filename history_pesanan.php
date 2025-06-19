<?php
include 'config/config.php';
session_start();

// Pastikan pelanggan sudah login
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user_username'];

// Ambil data history pesanan yang sudah selesai atau dibatalkan
$query = "SELECT r.id_222060, r.order_id_222060, r.customer_name_222060, r.product_222060, 
                 r.quantity_222060, r.price_222060, r.status_222060, r.completion_date_222060, 
                 r.notes_222060, p.product_image_222060
          FROM order_report_222060 r
          LEFT JOIN product_222060 p ON r.product_222060 = p.product_name_222060
          WHERE r.customer_name_222060 = ? AND (r.status_222060 = 'Completed' OR r.status_222060 = 'Cancelled')
          ORDER BY r.completion_date_222060 DESC";
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
    <title>History Pesanan</title>
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

        /* Order Header with Icon */
        .order-header {
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

        .desktop-table td.notes {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .desktop-table td.notes:hover {
            white-space: normal;
            overflow: visible;
        }

        .desktop-table tr:last-child td {
            border-bottom: none;
        }

        .desktop-table tr:hover {
            background-color: #f8f9fa;
        }

        .desktop-table tr.total-row {
            font-weight: 600;
            background-color: #f8f9fa;
        }

        /* Status Styles */
        .status {
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 13px;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .status:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .status i {
            margin-right: 5px;
        }

        .completed {
            background-color: #28a745;
            color: #ffffff;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .cancelled {
            background-color: #dc3545;
            color: #ffffff;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Mobile Card Style */
        .mobile-cards {
            display: none;
        }

        .product-card {
            margin-bottom: 20px;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .product-card-header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
        }

        .product-card-body {
            padding: 15px;
            display: flex;
            background-color: white;
        }

        .product-card-image {
            flex: 0 0 100px;
            margin-right: 15px;
        }

        .product-card-details {
            flex: 1;
        }

        .product-card-row {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-start;
        }

        .product-card-label {
            flex: 0 0 80px;
            font-weight: 500;
            color: #666;
        }

        .product-card-value {
            flex: 1;
            color: #333;
            word-break: break-word;
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

        tr:hover .product-image, .product-card:hover .product-image {
            transform: scale(1.05);
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

                /* Tombol Detail */
                .btn-detail {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background-color: #17a2b8;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .btn-detail:hover {
            background-color: #117a8b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Modal Pembayaran */
.modal-payment {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.7);
}

.modal-payment-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
    width: 80%;
    max-width: 700px;
    position: relative;
    animation: modalopen 0.4s;
}

@keyframes modalopen {
    from {opacity: 0; transform: translateY(-50px);}
    to {opacity: 1; transform: translateY(0);}
}

.close-payment-modal {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.close-payment-modal:hover {
    color: #333;
}

/* Style untuk konten modal pembayaran */
.payment-detail {
    margin-top: 20px;
}

.payment-detail-row {
    display: flex;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.payment-detail-label {
    flex: 0 0 180px;
    font-weight: 600;
    color: #555;
}

.payment-detail-value {
    flex: 1;
    color: #333;
}

.payment-proof-preview {
    margin-top: 15px;
    text-align: center;
}

.payment-proof-image {
    max-width: 100%;
    max-height: 300px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .modal-payment-content {
        width: 90%;
        margin: 10% auto;
    }
    
    .payment-detail-row {
        flex-direction: column;
    }
    
    .payment-detail-label {
        flex: 1;
        margin-bottom: 5px;
    }
}

        /* Empty orders message */
        .empty-orders {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-orders i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
            display: block;
        }

        /* Total summary box */
        .order-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-summary-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }

        .order-summary-value {
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
            
            .product-card-image {
                flex: 0 0 80px;
            }
            
            .product-image {
                width: 70px;
                height: 70px;
            }
            
            .order-summary {
                flex-direction: column;
                gap: 5px;
            }
            
            .back-container {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media screen and (max-width: 480px) {
            .product-card-body {
                flex-direction: column;
            }
            
            .product-card-image {
                margin-right: 0;
                margin-bottom: 15px;
                text-align: center;
            }
            
            .product-image {
                width: 100px;
                height: 100px;
            }

            .modal-detail { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 999; }
        .modal-detail-content { background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; position: relative; }
        .modal-detail-close { position: absolute; top: 10px; right: 10px; cursor: pointer; font-size: 1.2rem; }
        .btn-detail { cursor: pointer; }
        }
    </style>
</head>

<body>
    <div class="bg-pattern"></div>
    <div class="container">
        <div class="order-header">
            <h2>History Pesanan</h2>
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
                    <th>Produk</th>
                    <th>Jumlah</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Tanggal Selesai</th>
                    <th>Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_all = 0;
                if ($result->num_rows > 0) { 
                    while ($row = $result->fetch_assoc()) {
                        $total_price = $row['quantity_222060'] * $row['price_222060'];
                        $total_all += $total_price;
                        $product_image = !empty($row['product_image_222060']) ? $row['product_image_222060'] : 'default.jpg'; 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><img src="uploads/<?= htmlspecialchars($product_image) ?>" class="product-image" alt="Gambar Produk"></td>
                            <td><?= htmlspecialchars($row['product_222060']) ?></td>
                            <td><?= $row['quantity_222060'] ?></td>
                            <td>Rp <?= number_format($row['price_222060'], 0, ',', '.') ?></td>
                            <td>
                                <?php
                                $status = $row['status_222060'];
                                echo '<span class="status ' . strtolower($status) . '">' . htmlspecialchars($status) . '</span>';
                                ?>
                            </td>
                            <td><?= date("d-m-Y", strtotime($row['completion_date_222060'])) ?></td>
                            <td class="notes" title="<?= htmlspecialchars($row['notes_222060']) ?>"><?= htmlspecialchars($row['notes_222060']) ?></td>
                            <td>
    <a href="javascript:void(0);" onclick="showPaymentDetail('<?= $row['order_id_222060'] ?>')" class="btn-detail">
        <i class="fas fa-info-circle"></i> Detail Pembayaran
    </a>
</td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-orders">
                                <i class="fas fa-history"></i>
                                <p>Tidak ada riwayat pesanan yang tersedia.</p>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Tampilan card untuk mobile -->
        <div class="mobile-cards">
            <?php 
            if ($result && $result->num_rows > 0) {
                $result->data_seek(0);
                $no = 1;
                $total_all = 0;
                while ($row = $result->fetch_assoc()) { 
                    $total_price = $row['quantity_222060'] * $row['price_222060'];
                    $total_all += $total_price;
                    $product_image = !empty($row['product_image_222060']) ? $row['product_image_222060'] : 'default.jpg';
                    $status = $row['status_222060'];
                    ?>
                    <div class="product-card">
                        <div class="product-card-header">
                            <div><i class="fas fa-history"></i> Riwayat #<?= $no++ ?></div>
                            <span class="status <?= strtolower($status) ?>"><?= htmlspecialchars($status) ?></span>
                        </div>
                        <div class="product-card-body">
                            <div class="product-card-image">
                                <img src="uploads/<?= htmlspecialchars($product_image) ?>" class="product-image" alt="Gambar Produk">
                            </div>
                            <div class="product-card-details">
                                <div class="product-card-row">
                                    <div class="product-card-label">Produk:</div>
                                    <div class="product-card-value"><?= htmlspecialchars($row['product_222060']) ?></div>
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
                                    <div class="product-card-label">Status:</div>
                                    <div class="product-card-value"><?= htmlspecialchars($row['status_222060']) ?></div>
                                </div>
                                <div class="product-card-row">
                                    <div class="product-card-label">Tanggal:</div>
                                    <div class="product-card-value"><i class="far fa-calendar-alt"></i> <?= date("d-m-Y", strtotime($row['completion_date_222060'])) ?></div>
                                </div>
                                <div class="product-card-row">
                                    <div class="product-card-label">Catatan:</div>
                                    <div class="product-card-value"><?= htmlspecialchars($row['notes_222060']) ?></div>
                                </div>
                                <div class="product-card-row">
                                    <div class="product-card-label"></div>
                                    <div class="product-card-value">
                                    <a href="javascript:void(0);" onclick="showPaymentDetail('<?= $row['order_id_222060'] ?>')" class="btn-detail">
        <i class="fas fa-info-circle"></i> Detail Pembayaran
    </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-orders">
                    <i class="fas fa-history"></i>
                    <p>Tidak ada riwayat pesanan yang tersedia.</p>
                </div>
            <?php } ?>
        </div>

        <div class="back-container">
            <a href="costumer_home.php" class="btn-back">
                <i class="fas fa-home"></i> Beranda
            </a>
            <a href="costumer_cart.php" class="btn-back">
                <i class="fas fa-shopping-cart"></i> Pesanan
            </a>
        </div>

        <div class="copyright">
            &copy; <?= date("Y"); ?> Toko Bangunan. All rights reserved.
        </div>
    </div>

    <script>
        // Fungsi untuk menampilkan modal detail pembayaran
function showPaymentDetail(orderId) {
    // Tampilkan loading
    document.getElementById('paymentModalContent').innerHTML = `
        <div style="text-align: center; padding: 30px;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Memuat detail pembayaran...</p>
        </div>
    `;
    
    // Tampilkan modal
    document.getElementById('paymentModal').style.display = 'block';
    
    // Ambil data pembayaran via AJAX
    fetch(`get_payment_detail.php?order_id=${orderId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('paymentModalContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('paymentModalContent').innerHTML = `
                <div class="alert alert-danger">
                    Gagal memuat detail pembayaran. Silakan coba lagi.
                </div>
            `;
        });
}

// Fungsi untuk menutup modal
function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// Fungsi untuk menampilkan modal
function showPaymentDetail(orderId) {
    // Tampilkan loading
    document.getElementById('paymentModalContent').innerHTML = `
        <div style="text-align: center; padding: 30px;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Memuat detail pembayaran...</p>
        </div>
    `;
    
    // Tampilkan modal
    document.getElementById('paymentModal').style.display = 'block';
    
    // Ambil data pembayaran via AJAX
    fetch(`get_payment_detail.php?order_id=${orderId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('paymentModalContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('paymentModalContent').innerHTML = `
                <div class="alert alert-danger">
                    Gagal memuat detail pembayaran. Silakan coba lagi.
                </div>
            `;
        });
}

// Tutup modal ketika klik di luar konten modal
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target == modal) {
        closePaymentModal();
    }
}

// Animasi untuk modal
document.addEventListener("DOMContentLoaded", function() {
    // ... kode animasi yang sudah ada ...
});
        document.addEventListener("DOMContentLoaded", function() {
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
            const cards = document.querySelectorAll('.product-card');
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

    <!-- Modal Detail Pembayaran -->
<!-- Modal Detail Pembayaran -->
<div id="paymentModal" class="modal-payment" style="display: none;">
    <div class="modal-payment-content">
        <span class="close-payment-modal" onclick="closePaymentModal()">&times;</span>
        <div id="paymentModalContent">
            <!-- Konten akan diisi oleh JavaScript -->
        </div>
    </div>
</div>
</body>
</html>
