<?php
session_start();
if (!isset($_SESSION['super_admin_logged_in']) || $_SESSION['super_admin_logged_in'] !== true) {
    header("Location: ../costumer_login.php");
    exit();
}

include '../config/config.php';

// Mengambil data pesanan yang hanya berstatus 'pending' dan 'processed'
$orders = $conn->query("SELECT * FROM order_222060 WHERE order_status_222060 = 'pending' OR order_status_222060 = 'processed'");
$orders_count = $orders->num_rows;


// Mengambil data produk
$products = $conn->query("SELECT * FROM product_222060");
$products_count = $products->num_rows;

// Mengambil data pengguna
$users = $conn->query("SELECT * FROM user_222060");
$users_count = $users->num_rows;

// Menghitung total pendapatan bulan ini dari tabel laporan (order_report_222060)
$current_month = date('Y-m'); // Misalnya: '2025-04'

$sql = "
    SELECT SUM(price_222060) AS total_revenue 
    FROM order_report_222060 
    WHERE DATE_FORMAT(completion_date_222060, '%Y-%m') = '$current_month'
    AND status_222060 = 'completed'
    AND price_222060 > 0
";

$revenue_query = $conn->query($sql);

// Debug jika query gagal
if (!$revenue_query) {
    die("Query Error: " . $conn->error . "<br>SQL: " . $sql);
}

$revenue_data = $revenue_query->fetch_assoc();
$monthly_revenue = $revenue_data['total_revenue'] ?? 0;

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
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
            display: flex;
            background-color: #f8f9fa;
            color: #333;
        }

        /* Background pattern */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(rgba(52, 58, 64, 0.05) 3px, transparent 3px),
                radial-gradient(rgba(52, 58, 64, 0.05) 3px, transparent 3px);
            background-size: 50px 50px;
            background-position: 0 0, 25px 25px;
            z-index: -1;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #343a40;
            padding-top: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            transition: transform 0.3s ease-in-out;
            z-index: 999;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 30px;
            font-size: 30px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        .sidebar h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: #495057;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        .sidebar ul li {
            margin: 8px 12px;
        }

        .sidebar ul li a {
            display: flex;
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
        }

        .sidebar ul li a:hover {
            background-color: #495057;
            transform: translateX(5px);
        }

        .sidebar ul li a.active {
            background-color: #495057;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar ul li a i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .sidebar ul li a:hover i {
            transform: scale(1.1);
        }

        /* Copyright di Sidebar */
        .sidebar .copyright {
            text-align: center;
            color: #adb5bd;
            font-size: 14px;
            margin-top: 260px;
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Menu tombol di tampilan kecil */
        .menu-toggle {
            display: none;
            font-size: 28px;
            color: #343a40;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1000;
            transition: color 0.3s ease;
        }

        /* Main Content */
        .content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
            transition: margin-left 0.3s ease-in-out;
        }

        section {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        section:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #343a40, #495057);
        }

        section h2 {
            color: #343a40;
            margin-bottom: 20px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        section h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 3px;
            background-color: #343a40;
        }

        /* Enhanced Table Styling */
        .table-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
            overflow-x: auto;
            position: relative;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
            background-color: white;
        }

        .styled-table thead tr {
            background-color: #343a40;
            color: #ffffff;
            text-align: left;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .styled-table th, .styled-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .styled-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s ease;
        }

        .styled-table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }

        .styled-table tbody tr:last-of-type {
            border-bottom: 2px solid #343a40;
        }

        .styled-table tbody tr:hover {
            background-color: #f1f3f5;
            box-shadow: 0 0 10px rgba(0,0,0,0.05) inset;
        }

/* Enhanced Status Badge Styling */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 500;
    text-align: center;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    text-transform: uppercase; /* Applied to all badges */
}

.status-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.status-badge i {
    margin-right: 5px;
}

.status-pending {
    background: #ffc107; /* Warna kuning */
    color: #212529;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-processed {
    background: #007bff; /* Warna biru */
    color: #ffffff;
    border: 1px solid rgba(0, 123, 255, 0.3);
}

.status-completed {
    background: #28a745; /* Warna hijau */
    color: #ffffff;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-cancelled {
    background: #dc3545; /* Warna merah */
    color: #ffffff;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

        /* Truncate text in long columns with tooltip on hover */
        .truncate {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
        }

        .truncate:hover::after {
            content: attr(title);
            position: absolute;
            left: 0;
            top: 100%;
            background-color: #343a40;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            z-index: 100;
            white-space: normal;
            max-width: 300px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        }

        /* Enhanced Statistics cards */
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

        /* Responsive table redesign for small screens */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }

            .menu-toggle {
                display: block;
            }

            .content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
                padding-top: 60px;
            }

            .sidebar.open {
                transform: translateX(0);
            }
            
            /* Enhanced mobile table design */
            .styled-table {
                border: 0;
            }
            
            .styled-table caption {
                font-size: 1.3em;
            }
            
            .styled-table thead {
                border: none;
                clip: rect(0 0 0 0);
                height: 1px;
                margin: -1px;
                overflow: hidden;
                padding: 0;
                position: absolute;
                width: 1px;
            }
            
            .styled-table tr {
                display: block;
                margin-bottom: 1.5rem;
                border: 1px solid #e0e0e0;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                background-color: white !important;
                overflow: hidden;
            }
            
            .styled-table tr:hover {
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            
            .styled-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.9em;
                text-align: right;
                padding: 12px 15px;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .styled-table td:last-child {
                border-bottom: 0;
            }
            
            .styled-table td::before {
                content: attr(data-label);
                float: left;
                font-weight: bold;
                color: #343a40;
            }
            
            .styled-table td:first-child {
                background-color: #f8f9fa;
                font-weight: bold;
                color: #343a40;
                border-top-left-radius: 10px;
                border-top-right-radius: 10px;
            }
            
            /* Reset truncate on mobile */
            .truncate {
                max-width: none;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }
            
            /* Better status badge for mobile */
            .status-badge {
                width: auto;
                margin-left: auto;
            }
        }
    </style>
</head>
<body>

<!-- Background Pattern -->
<div class="bg-pattern"></div>

<!-- Tombol Menu --> 
<div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-bars" id="menu-icon"></i>
</div>

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
    <!-- Statistik Dashboard -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-basket"></i>
            </div>
            <div class="stat-info">
                <h3><?= $orders_count ?></h3>
                <p>Total Pesanan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stat-info">
                <h3><?= $products_count ?></h3>
                <p>Produk Aktif</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <h3><?= $users_count ?></h3>
                <p>Total Pengguna</p>
            </div>
        </div>
        <div class="stat-card">
    <div class="stat-icon">
        <i class="bi bi-currency-dollar"></i>
    </div>
    <div class="stat-info">
    <h3>Rp<?= number_format($monthly_revenue, 0, ',', '.') ?></h3>
    <p>Pendapatan Bulan Ini</p>
    </div>
</div>

    </div>

    <section>
        <h2><i class="bi bi-basket"></i> Pesanan Terbaru</h2>
        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID User</th>
                        <th>Username</th>
                        <th>Nama Produk</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                        <th>Alamat</th>
                        <th>Metode Pembayaran</th>
                        <th>Status</th>
                        <th>Tanggal Pesan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $order_no = 1; // Counter for order numbering
                    while ($row = $orders->fetch_assoc()) { ?>
                        <tr>
                            <td data-label="No"><?= $order_no++ ?></td>
                            <td data-label="ID User"><?= htmlspecialchars($row['user_id_222060']) ?></td>
                            <td data-label="Username"><?= htmlspecialchars($row['username_222060']) ?></td>
                            <td data-label="Nama Produk"><?= htmlspecialchars($row['product_name_222060']) ?></td>
                            <td data-label="Jumlah"><?= htmlspecialchars($row['quantity_222060']) ?></td>
                            <td data-label="Harga Satuan">Rp<?= number_format($row['price_222060'], 0, ',', '.') ?></td>
                            <td data-label="Total Harga">Rp<?= number_format($row['total_price_222060'], 0, ',', '.') ?></td>
                            <td data-label="Alamat" class="truncate" title="<?= htmlspecialchars($row['alamat_222060']) ?>"><?= htmlspecialchars($row['alamat_222060']) ?></td>
                            <td data-label="Metode Pembayaran" ><?= htmlspecialchars($row['payment_method_222060']) ?></td>
                            <td data-label="Status">
                                <span class="status-badge status-<?= strtolower($row['order_status_222060']) ?>">
                                    <?= htmlspecialchars($row['order_status_222060']) ?>
                                </span>
                            </td>
                            <td data-label="Tanggal Pesan"><?= htmlspecialchars($row['order_date_222060']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2><i class="bi bi-box-seam"></i> Produk Tersedia</h2>
        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Produk</th>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $products = $conn->query("SELECT * FROM product_222060");
                    $product_no = 1; // Counter for product numbering
                    while ($row = $products->fetch_assoc()) { ?>
                        <tr>
                            <td data-label="No"><?= $product_no++ ?></td>
                            <td data-label="Nama Produk"><?= htmlspecialchars($row['product_name_222060']) ?></td>
                            <td data-label="Deskripsi" class="truncate" title="<?= htmlspecialchars($row['description_222060']) ?>"><?= htmlspecialchars($row['description_222060']) ?></td>
                            <td data-label="Harga">Rp<?= number_format($row['price_222060'], 0, ',', '.') ?></td>
                            <td data-label="Stok"><?= htmlspecialchars($row['stock_222060']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
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
                menuIcon.classList.add("fa-times"); // Mengubah menjadi ikon silang/kali (X)
                menuIcon.style.color = "#fff"; // Warna putih saat menu terbuka
                menuIcon.style.marginTop = "13px"; 
                menuIcon.style.fontSize = "30px";
            } else {
                menuIcon.classList.remove("fa-times");
                menuIcon.classList.add("fa-bars"); // Mengubah kembali menjadi ikon hamburger
                menuIcon.style.color = "#343a40"; // Warna asli saat menu tertutup
                menuIcon.style.marginTop = "0"; 
                menuIcon.style.fontSize = "28px";
            }
        });
    });
</script>

</body>
</html>