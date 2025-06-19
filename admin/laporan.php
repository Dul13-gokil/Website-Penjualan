<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../costumer_login.php");
    exit();
}

include '../config/config.php';

// Initialize $laporans
$laporans = null;

// Calculate revenue metrics
$current_month = date('Y-m'); // Current month (e.g. '2025-05')
$current_week = date('Y-W'); // Current week (e.g. '2025-20')
$current_day = date('Y-m-d'); // Current day (e.g. '2025-05-16')
$last_month = date('Y-m', strtotime('-1 month')); // Last month (e.g. '2025-04')

// Total All-Time Revenue
$total_sql = "
    SELECT SUM(price_222060) AS total_revenue 
    FROM order_report_222060 
    WHERE status_222060 = 'completed'
    AND price_222060 > 0
";
$total_query = $conn->query($total_sql);
$total_data = $total_query->fetch_assoc();
$total_revenue = $total_data['total_revenue'] ?? 0;

// Monthly revenue
$monthly_sql = "
    SELECT SUM(price_222060) AS total_revenue 
    FROM order_report_222060 
    WHERE DATE_FORMAT(completion_date_222060, '%Y-%m') = '$current_month'
    AND status_222060 = 'completed'
    AND price_222060 > 0
";
$monthly_query = $conn->query($monthly_sql);
$monthly_data = $monthly_query->fetch_assoc();
$monthly_revenue = $monthly_data['total_revenue'] ?? 0;

// Weekly revenue
$weekly_sql = "
    SELECT SUM(price_222060) AS total_revenue 
    FROM order_report_222060 
    WHERE DATE_FORMAT(completion_date_222060, '%Y-%u') = '$current_week'
    AND status_222060 = 'completed'
    AND price_222060 > 0
";
$weekly_query = $conn->query($weekly_sql);
$weekly_data = $weekly_query->fetch_assoc();
$weekly_revenue = $weekly_data['total_revenue'] ?? 0;

// Daily revenue
$daily_sql = "
    SELECT SUM(price_222060) AS total_revenue 
    FROM order_report_222060 
    WHERE DATE(completion_date_222060) = '$current_day'
    AND status_222060 = 'completed'
    AND price_222060 > 0
";
$daily_query = $conn->query($daily_sql);
$daily_data = $daily_query->fetch_assoc();
$daily_revenue = $daily_data['total_revenue'] ?? 0;

try {
    // 1. Hapus laporan yang order-nya sudah tidak completed/cancelled
    $delete_result = $conn->query(
        "DELETE report FROM order_report_222060 report
         JOIN order_222060 ord ON report.order_id_222060 = ord.id_222060
         WHERE ord.order_status_222060 NOT IN ('completed','cancelled')"
    );
    if ($delete_result === false) {
        throw new Exception("Failed to clean up reports: " . $conn->error);
    }

    // 2. Tambah laporan untuk order completed/cancelled yang belum dilaporkan
    $completed_orders = $conn->query(
        "SELECT * FROM order_222060 
         WHERE order_status_222060 IN ('completed','cancelled')
           AND id_222060 NOT IN (SELECT order_id_222060 FROM order_report_222060)"
    );
    if ($completed_orders === false) {
        throw new Exception("Failed to fetch completed orders: " . $conn->error);
    }

    while ($order = $completed_orders->fetch_assoc()) {
        $note_text = ($order['order_status_222060'] == 'completed') ? "Pesanan Selesai" : "Pesanan Dibatalkan";
        $insert = $conn->prepare(
            "INSERT INTO order_report_222060 
             (order_id_222060, customer_name_222060, product_222060, quantity_222060, price_222060, status_222060, completion_date_222060, notes_222060)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$insert) throw new Exception("Prepare failed: " . $conn->error);
        $insert->bind_param("issiisss",
            $order['id_222060'],
            $order['username_222060'],
            $order['product_name_222060'],
            $order['quantity_222060'],
            $order['price_222060'],
            $order['order_status_222060'],
            $order['order_date_222060'],
            $note_text
        );
        if (!$insert->execute()) {
            throw new Exception("Execute failed: " . $insert->error);
        }
        $insert->close();
    }

    // 3. Ambil semua laporan beserta gambar produk, kelompokkan berdasarkan bulan
    $current_month_laporans = $conn->query(
        "SELECT r.*, p.product_image_222060 
         FROM order_report_222060 r
         LEFT JOIN order_222060 o ON r.order_id_222060 = o.id_222060
         LEFT JOIN product_222060 p ON r.product_222060 = p.product_name_222060
         WHERE DATE_FORMAT(r.completion_date_222060, '%Y-%m') = '$current_month'
         ORDER BY r.completion_date_222060 DESC"
    );
    if ($current_month_laporans === false) {
        throw new Exception("Failed to fetch current month reports: " . $conn->error);
    }
    
    $last_month_laporans = $conn->query(
        "SELECT r.*, p.product_image_222060 
         FROM order_report_222060 r
         LEFT JOIN order_222060 o ON r.order_id_222060 = o.id_222060
         LEFT JOIN product_222060 p ON r.product_222060 = p.product_name_222060
         WHERE DATE_FORMAT(r.completion_date_222060, '%Y-%m') = '$last_month'
         ORDER BY r.completion_date_222060 DESC"
    );
    if ($last_month_laporans === false) {
        throw new Exception("Failed to fetch last month reports: " . $conn->error);
    }
    
    // Get all other reports for a separate section
    $older_laporans = $conn->query(
        "SELECT r.*, p.product_image_222060 
         FROM order_report_222060 r
         LEFT JOIN order_222060 o ON r.order_id_222060 = o.id_222060
         LEFT JOIN product_222060 p ON r.product_222060 = p.product_name_222060
         WHERE DATE_FORMAT(r.completion_date_222060, '%Y-%m') < '$last_month'
         ORDER BY r.completion_date_222060 DESC"
    );
    if ($older_laporans === false) {
        throw new Exception("Failed to fetch older reports: " . $conn->error);
    }

} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; margin:10px; background-color:#ffeeee; border:1px solid #ffaaaa; border-radius:5px;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
    $current_month_laporans = [];
    $last_month_laporans = [];
    $older_laporans = [];
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pesanan</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/laporan.css">
    <style>
        .product-image { width:60px; height:60px; object-fit:cover; border-radius:4px; }
        
        /* Stats container style */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #343a40, #495057);
        }

        .stat-card:nth-child(1)::before {
            background: linear-gradient(90deg, #343a40, #495057);
        }

        .stat-card:nth-child(2)::before {
            background: linear-gradient(90deg, #343a40, #495057);
        }

        .stat-card:nth-child(3)::before {
            background: linear-gradient(90deg, #343a40, #495057);
        }
        
        .stat-card:nth-child(4)::before {
            background: linear-gradient(90deg, #343a40, #495057);
        }

        .stat-icon {
            height: 60px;
            width: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #343a40, #495057);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 26px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card:nth-child(1) .stat-icon {
            background: linear-gradient(90deg, #343a40, #495057);
        }

        .stat-card:nth-child(2) .stat-icon {
            background: linear-gradient(90deg, #343a40, #495057);
        }

        .stat-card:nth-child(3) .stat-icon {
            background: linear-gradient(90deg, #343a40, #495057);
        }
        
        .stat-card:nth-child(4) .stat-icon {
            background: linear-gradient(90deg, #343a40, #495057);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin: 0;
            color: #343a40;
            font-weight: 600;
        }

        .stat-info p {
            margin: 5px 0 0;
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Enhanced note display */
        .note-text {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .note-completed {
            background: #28a745;
            color: #ffffff;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .note-cancelled {
            background: #dc3545;
            color: #ffffff;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        /* Section tabs */
        .tab-nav {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .tab-button {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px 20px;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .tab-button:hover {
            background: #e9ecef;
        }
        
        .tab-button.active {
            background: #343a40;
            color: white;
            border-color: #343a40;
        }

        .export-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .month-title {
            margin: 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #343a40;
            font-size: 1.5rem;
            color: #343a40;
        }
    </style>
</head>
<body>
<div class="bg-pattern"></div>
<div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-bars" id="menu-icon"></i>
</div>
<aside class="sidebar" id="sidebar">
    <h2>Admin</h2>
    <ul>
        <li><a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a href="orders.php" class="<?= $current_page=='orders.php'?'active':'' ?>"><i class="bi bi-basket"></i> Pesanan</a></li>
        <li><a href="products.php" class="<?= $current_page=='products.php'?'active':'' ?>"><i class="bi bi-box-seam"></i> Produk</a></li>
        <li><a href="laporan.php" class="<?= $current_page=='laporan.php'?'active':'' ?>"><i class="bi bi-file-earmark-text"></i> Laporan</a></li>
        <li><a href="users.php" class="<?= $current_page=='users.php'?'active':'' ?>"><i class="bi bi-people"></i> Kelola User</a></li>
        <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
    <div class="copyright">&copy; <?= date('Y'); ?> Admin Panel. All rights reserved.</div>
</aside>
<main class="content">
    <!-- Revenue Statistics -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-info">
                <h3>Rp<?= number_format($total_revenue, 0, ',', '.') ?></h3>
                <p>Total Pendapatan Keseluruhan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div class="stat-info">
                <h3>Rp<?= number_format($monthly_revenue, 0, ',', '.') ?></h3>
                <p>Pendapatan Bulan Ini</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-calendar-week"></i>
            </div>
            <div class="stat-info">
                <h3>Rp<?= number_format($weekly_revenue, 0, ',', '.') ?></h3>
                <p>Pendapatan Minggu Ini</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-calendar-day"></i>
            </div>
            <div class="stat-info">
                <h3>Rp<?= number_format($daily_revenue, 0, ',', '.') ?></h3>
                <p>Pendapatan Hari Ini</p>
            </div>
        </div>
    </div>

    <div class="export-actions" style="margin-top: 30px; text-align: center;">
    <h3 style="margin-bottom: 20px; color: #343a40;">
        <i class="bi bi-download"></i> Unduh Laporan PDF
    </h3>
    <div class="export-buttons" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
        <a href="generate_pdf.php?type=summary" class="export-btn" style="background: linear-gradient(135deg, #343a40, #495057); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <i class="bi bi-bar-chart"></i>
            Ringkasan Revenue
        </a>
        <a href="generate_pdf.php?type=current_month" class="export-btn" style="background: linear-gradient(135deg, #343a40, #495057); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <i class="bi bi-calendar-month"></i>
            Bulan Ini
        </a>
        <a href="generate_pdf.php?type=last_month" class="export-btn" style="background: linear-gradient(135deg, #343a40, #495057); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <i class="bi bi-calendar-week"></i>
            Bulan Lalu
        </a>
        <a href="generate_pdf.php?type=older" class="export-btn" style="background: linear-gradient(135deg, #343a40, #495057); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <i class="bi bi-archive"></i>
            Laporan Sebelumnya
        </a>
        <a href="generate_pdf.php?type=all" class="export-btn" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <i class="bi bi-file-earmark-pdf"></i>
            Laporan Lengkap
        </a>
    </div>
</div>
<br>

    <section>
        
        <h2><i class="bi bi-file-earmark-text"></i> Laporan Pesanan</h2>
        
        <div class="tab-nav">
            <button class="tab-button active" data-tab="current-month">Bulan Ini (<?= date('F Y') ?>)</button>
            <button class="tab-button" data-tab="last-month">Bulan Lalu (<?= date('F Y', strtotime('-1 month')) ?>)</button>
            <button class="tab-button" data-tab="older-reports">Laporan Sebelumnya</button>
        </div>
        
        <!-- Current Month Reports -->
        <div id="current-month" class="tab-content active">
            <h3 class="month-title">Laporan Bulan Ini (<?= date('F Y') ?>)</h3>
            <div class="table-container">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pelanggan</th>
                            <th>Gambar</th>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Tanggal Selesai</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($current_month_laporans && is_object($current_month_laporans) && $current_month_laporans->num_rows>0) {
                        $no=1;
                        while($row=$current_month_laporans->fetch_assoc()){
                            $note_class = ($row['status_222060']=='completed')?'note-completed':'note-cancelled';
                            ?>
                            <tr>
                                <td data-label="No"><?= $no++ ?></td>
                                <td data-label="Nama Pelanggan"><?= htmlspecialchars($row['customer_name_222060']) ?></td>
                                <td data-label="Gambar">
                                    <?php if($row['product_image_222060']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($row['product_image_222060']) ?>" class="product-image" alt="Gambar Produk">
                                    <?php else: echo '-'; endif; ?>
                                </td>
                                <td data-label="Produk"><?= htmlspecialchars($row['product_222060']) ?></td>
                                <td data-label="Jumlah"><?= htmlspecialchars($row['quantity_222060']) ?></td>
                                <td data-label="Total Harga">Rp<?= number_format($row['price_222060'],0,',','.') ?></td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?= strtolower($row['status_222060']) ?>"><?= htmlspecialchars($row['status_222060']) ?></span>
                                </td>
                                <td data-label="Tanggal Selesai"><?= htmlspecialchars($row['completion_date_222060']) ?></td>
                                <td data-label="Catatan"><span class="note-text <?= $note_class ?>"><?= htmlspecialchars($row['notes_222060']) ?></span></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="9" style="text-align:center;">Tidak ada data laporan untuk bulan ini</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Last Month Reports -->
        <div id="last-month" class="tab-content">
            <h3 class="month-title">Laporan Bulan Lalu (<?= date('F Y', strtotime('-1 month')) ?>)</h3>
            <div class="table-container">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pelanggan</th>
                            <th>Gambar</th>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Tanggal Selesai</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($last_month_laporans && is_object($last_month_laporans) && $last_month_laporans->num_rows>0) {
                        $no=1;
                        while($row=$last_month_laporans->fetch_assoc()){
                            $note_class = ($row['status_222060']=='completed')?'note-completed':'note-cancelled';
                            ?>
                            <tr>
                                <td data-label="No"><?= $no++ ?></td>
                                <td data-label="Nama Pelanggan"><?= htmlspecialchars($row['customer_name_222060']) ?></td>
                                <td data-label="Gambar">
                                    <?php if($row['product_image_222060']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($row['product_image_222060']) ?>" class="product-image" alt="Gambar Produk">
                                    <?php else: echo '-'; endif; ?>
                                </td>
                                <td data-label="Produk"><?= htmlspecialchars($row['product_222060']) ?></td>
                                <td data-label="Jumlah"><?= htmlspecialchars($row['quantity_222060']) ?></td>
                                <td data-label="Total Harga">Rp<?= number_format($row['price_222060'],0,',','.') ?></td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?= strtolower($row['status_222060']) ?>"><?= htmlspecialchars($row['status_222060']) ?></span>
                                </td>
                                <td data-label="Tanggal Selesai"><?= htmlspecialchars($row['completion_date_222060']) ?></td>
                                <td data-label="Catatan"><span class="note-text <?= $note_class ?>"><?= htmlspecialchars($row['notes_222060']) ?></span></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="9" style="text-align:center;">Tidak ada data laporan untuk bulan lalu</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Older Reports -->
        <div id="older-reports" class="tab-content">
            <h3 class="month-title">Laporan Sebelumnya</h3>
            <div class="table-container">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pelanggan</th>
                            <th>Gambar</th>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Tanggal Selesai</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($older_laporans && is_object($older_laporans) && $older_laporans->num_rows>0) {
                        $no=1;
                        while($row=$older_laporans->fetch_assoc()){
                            $note_class = ($row['status_222060']=='completed')?'note-completed':'note-cancelled';
                            ?>
                            <tr>
                                <td data-label="No"><?= $no++ ?></td>
                                <td data-label="Nama Pelanggan"><?= htmlspecialchars($row['customer_name_222060']) ?></td>
                                <td data-label="Gambar">
                                    <?php if($row['product_image_222060']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($row['product_image_222060']) ?>" class="product-image" alt="Gambar Produk">
                                    <?php else: echo '-'; endif; ?>
                                </td>
                                <td data-label="Produk"><?= htmlspecialchars($row['product_222060']) ?></td>
                                <td data-label="Jumlah"><?= htmlspecialchars($row['quantity_222060']) ?></td>
                                <td data-label="Total Harga">Rp<?= number_format($row['price_222060'],0,',','.') ?></td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?= strtolower($row['status_222060']) ?>"><?= htmlspecialchars($row['status_222060']) ?></span>
                                </td>
                                <td data-label="Tanggal Selesai"><?= htmlspecialchars($row['completion_date_222060']) ?></td>
                                <td data-label="Catatan"><span class="note-text <?= $note_class ?>"><?= htmlspecialchars($row['notes_222060']) ?></span></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="9" style="text-align:center;">Tidak ada data laporan sebelumnya</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </section>
    
</main>



<script>
    document.getElementById('menu-toggle').addEventListener('click',function(){
        let sidebar=document.getElementById('sidebar');
        let icon=document.getElementById('menu-icon');
        sidebar.classList.toggle('open');
        if(sidebar.classList.contains('open')){
            icon.classList.replace('fa-bars','fa-times');icon.style.color='#fff';icon.style.marginTop='13px';icon.style.fontSize='30px';
        }else{
            icon.classList.replace('fa-times','fa-bars');icon.style.color='#343a40';icon.style.marginTop='0';icon.style.fontSize='28px';
        }
    });
    
    // Tab navigation functionality
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Remove active class from all content sections
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show the corresponding content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
</script>

</body>
</html>