<?php
session_start();

// Pastikan data struk tersedia
if (!isset($_SESSION['receipt'])) {
    echo "<script>alert('Data struk tidak ditemukan!'); window.location='costumer_home.php';</script>";
    exit();
}

$receipt = $_SESSION['receipt'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran</title>
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
            align-items: center;
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

        .receipt-container {
            width: 100%;
            max-width: 450px;
            background: white;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .receipt-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .receipt-header h2 {
            color: #007bff;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1.5rem;
            padding-bottom: 10px;
            border-bottom: none;
        }

        .receipt-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #007bff;
        }

        .order-success {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .success-icon {
            width: 60px;
            height: 60px;
            background-color: #28a745;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
            color: white;
            font-size: 1.8rem;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        }

        .receipt-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .receipt-info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: center;
        }

        .receipt-info-row:last-child {
            margin-bottom: 0;
        }

        .receipt-info-label {
            flex: 0 0 120px;
            font-weight: 500;
            color: #666;
            display: flex;
            align-items: center;
        }

        .receipt-info-label i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }

        .receipt-info-value {
            flex: 1;
            color: #333;
            padding-left: 8px;
        }

        .receipt-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .receipt-table th, .receipt-table td {
            padding: 12px 10px;
            text-align: center;
        }

        .receipt-table th {
            background-color: #007bff;
            color: white;
            white-space: nowrap;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .receipt-table td {
            border-bottom: 1px solid #eee;
        }

        .receipt-table tr:last-child td {
            border-bottom: none;
        }

        .receipt-table tr:hover {
            background-color: #f8f9fa;
        }

        .receipt-table tr.total-row {
            font-weight: 600;
            background-color: #f8f9fa;
        }

        .receipt-table tr.total-row td {
            padding: 15px 10px;
            border-top: 2px solid #ddd;
            color: #007bff;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .print-btn, .back-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .print-btn {
            background-color: #28a745;
            color: white;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        }

        .print-btn:hover {
            background-color: #218838;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }

        .back-btn {
            background-color: #007bff;
            color: white;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2);
        }

        .back-btn:hover {
            background-color: #0056b3;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3);
        }

        .copyright {
            text-align: center;
            color: #888;
            font-size: 0.8rem;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }

        /* CSS Agar Tombol Tidak Ikut Tercetak */
        @media print {
            .button-container, .copyright {
                display: none;
            }
            
            .receipt-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* Mobile Responsive */
        @media screen and (max-width: 480px) {
            .receipt-container {
                padding: 20px 15px;
            }
            
            .receipt-info-row {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 12px;
            }
            
            .receipt-info-label {
                margin-bottom: 3px;
            }
            
            .receipt-table th, .receipt-table td {
                padding: 8px 5px;
                font-size: 0.8rem;
            }
            
            .button-container {
                flex-direction: column;
            }
            
            .print-btn, .back-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>

    <div class="receipt-container">
        <div class="receipt-header">
            <h2>Toko Bangunan</h2>
            <p>Struk Pembayaran</p>
        </div>

        <div class="order-success">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
        </div>

        <div class="receipt-info">
            <div class="receipt-info-row">
                <div class="receipt-info-label"><i class="bi bi-calendar3"></i> Tanggal:</div>
                <div class="receipt-info-value"><?= date("Y-m-d"); ?></div>
            </div>
            <div class="receipt-info-row">
                <div class="receipt-info-label"><i class="bi bi-clock"></i> Waktu:</div>
                <div class="receipt-info-value">
                    <?php
                    // Set timezone berdasarkan lokasi di Indonesia
                    $timezone = 'Asia/Jakarta'; // Default WIB
                    
                    // Anda dapat menambahkan logika untuk menentukan timezone
                    // berdasarkan parameter atau konfigurasi sistem
                    if (isset($timezone_location)) {
                        if ($timezone_location == 'WITA') {
                            $timezone = 'Asia/Makassar';
                        } elseif ($timezone_location == 'WIT') {
                            $timezone = 'Asia/Jayapura';
                        }
                    }
                    
                    date_default_timezone_set($timezone);
                    
                    // Tentukan label zona waktu
                    $timezone_label = 'WIB';
                    if ($timezone == 'Asia/Makassar') {
                        $timezone_label = 'WITA';
                    } elseif ($timezone == 'Asia/Jayapura') {
                        $timezone_label = 'WIT';
                    }
                    
                    echo date("H:i:s") . " " . $timezone_label;
                    ?>
                </div>
            </div>
            <div class="receipt-info-row">
                <div class="receipt-info-label"><i class="bi bi-person"></i> Pelanggan:</div>
                <div class="receipt-info-value"><?= htmlspecialchars($receipt['customer_name']); ?></div>
            </div>
            <div class="receipt-info-row">
                <div class="receipt-info-label"><i class="bi bi-geo-alt"></i> Alamat:</div>
                <div class="receipt-info-value"><?= htmlspecialchars($receipt['address']); ?></div>
            </div>
            <div class="receipt-info-row">
                <div class="receipt-info-label"><i class="bi bi-credit-card"></i> Pembayaran:</div>
                <div class="receipt-info-value"><?= htmlspecialchars($receipt['payment_method']); ?></div>
            </div>
        </div>

        <table class="receipt-table">
            <tr>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Total</th>
            </tr>
            <?php foreach ($receipt['products'] as $index => $product): ?>
            <tr>
                <td><?= htmlspecialchars($product); ?></td>
                <td><?= $receipt['quantities'][$index]; ?></td>
                <td>Rp <?= number_format($receipt['prices'][$index], 0, ',', '.'); ?></td>
                <td>Rp <?= number_format($receipt['total_prices'][$index], 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="3">Total Keseluruhan</td>
                <td>Rp <?= number_format($receipt['total_amount'], 0, ',', '.'); ?></td>
            </tr>
        </table>

        <div class="button-container">
            <button class="print-btn" onclick="window.print();">
                <i class="bi bi-printer"></i> Cetak Struk
            </button>
            <button class="back-btn" onclick="window.location.href='costumer_home.php';">
                <i class="bi bi-house"></i> Kembali
            </button>
        </div>

        <div class="copyright">
            &copy; <?= date("Y"); ?> Toko Bangunan. All rights reserved.
        </div>
    </div>

    <script>
        // Add animation effect when page loads
        document.addEventListener("DOMContentLoaded", function() {
            const receiptContainer = document.querySelector('.receipt-container');
            receiptContainer.style.opacity = "0";
            receiptContainer.style.transform = "translateY(20px)";
            
            setTimeout(() => {
                receiptContainer.style.transition = "opacity 0.5s ease, transform 0.5s ease";
                receiptContainer.style.opacity = "1";
                receiptContainer.style.transform = "translateY(0)";
            }, 100);
        });
    </script>
</body>
</html>

<?php
// Hapus session struk setelah dicetak
unset($_SESSION['receipt']);
?>
