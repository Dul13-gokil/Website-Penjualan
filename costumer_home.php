<?php

session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: costumer_login.php");
    exit();
}

include 'config/config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Toko Bangunan</title>
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
            flex-direction: column;
            background-color: #f8f9fa;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #007bff;
            padding-top: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            transition: transform 0.3s ease-in-out;
            z-index: 999;
        }

        .sidebar h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 20px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 5px 10px;
        }

        .sidebar ul li a {
            display: block;
            padding: 10px 15px;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease, padding-left 0.3s ease;
        }

        .sidebar ul li a:hover {
            background-color: #3a3f47;
            padding-left: 20px;
        }

        .sidebar ul li a.active {
            background-color: #3a3f47;
        }

        .sidebar ul li a i {
            margin-right: 10px;
            transition: transform 0.3s ease;
        }

        .sidebar ul li a:hover i {
            transform: scale(1.05);
        }

        /* Menu tombol di tampilan kecil */
        .menu-toggle {
            display: none;
            font-size: 28px;
            color: #007bff;
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
            padding: 40px;
            width: calc(100% - 250px);
            transition: margin-left 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
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

        .hero-section {
            text-align: center;
            max-width: 800px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hero-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .hero-section:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #007bff, #00c6ff);
        }

        .hero-section h1 {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 36px;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }

        .hero-section h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #007bff;
        }

        .hero-section p {
            font-size: 18px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .feature-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
            width: 100%;
            max-width: 1200px;
        }

        .feature-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            width: calc(33.333% - 24px);
            text-align: center;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .feature-icon {
            font-size: 42px;
            color: #007bff;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 1.2rem;
        }

        .feature-card p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .btn-order {
            padding: 12px 25px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
        }

        .btn-order:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(40, 167, 69, 0.25);
        }

        /* Media Sosial di Sidebar */
        .sidebar .social-links {
            text-align: center;
            margin-top: 2px;
        }

        .sidebar .social-links a {
            display: inline-block;
            margin: 10px;
            color: #fff;
            font-size: 24px;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .sidebar .social-links a:hover {
            color: #ddd;
            transform: scale(1.1);
        }

        /* Footer Copyright di Sidebar */
        .sidebar .copyright {
            text-align: center;
            color: #fff;
            font-size: 14px;
            margin-top: 150px;
            padding: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
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

        /* Responsive */
        @media (max-width: 992px) {
            .feature-card {
                width: calc(50% - 24px);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
                height: 100%;
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

            .hero-section {
                padding: 30px 20px;
            }
        }

        @media (max-width: 576px) {
            .feature-card {
                width: 100%;
            }

            .hero-section h1 {
                font-size: 28px;
            }

            .hero-section p {
                font-size: 16px;
            }
        }

                /* Mode Malam */
        .night-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        .night-mode .sidebar {
            background-color: #2c2c2c;
        }

        .night-mode .hero-section {
            background-color: rgba(44, 44, 44, 0.9);
            color: #e0e0e0;
        }

        .night-mode .hero-section h1 {
            color: #4da6ff;
        }

        .night-mode .hero-section p {
            color: #b0b0b0;
        }

        .night-mode .feature-card {
            background: #2c2c2c;
            color: #e0e0e0;
        }

        .night-mode .feature-card h3 {
            color: #e0e0e0;
        }

        .night-mode .feature-card p {
            color: #b0b0b0;
        }

        .night-mode .feature-icon {
            color: #4da6ff;
        }

        .night-mode h2 {
            color: #4da6ff;
        }

        .night-mode h2:after {
            background-color: #4da6ff;
        }

        .night-mode .hero-section h1:after {
            background-color: #4da6ff;
        }

        .night-mode .hero-section:before {
            background: linear-gradient(90deg, #4da6ff, #00c6ff);
        }

        .night-mode .bg-pattern {
            background-image: 
                radial-gradient(rgba(77, 166, 255, 0.1) 3px, transparent 3px),
                radial-gradient(rgba(77, 166, 255, 0.1) 3px, transparent 3px);
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
<!-- Background Pattern -->
<div class="bg-pattern"></div>

<!-- Tombol Menu --> 
<div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-bars" id="menu-icon"></i>
</div>

<button class="theme-toggle" id="theme-toggle" title="Toggle Mode Malam">
    <i class="fas fa-moon" id="theme-icon"></i>
</button>

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
    <div class="hero-section">
        <h1>Selamat Datang di Toko Bangunan</h1>
        <p>
            Temukan berbagai kebutuhan material bangunan terbaik dengan harga terjangkau.  
            Dapatkan produk berkualitas untuk mendukung pembangunan impian Anda!
        </p>
        <a href="costumer_services.php" class="btn-order">Pesan Sekarang</a>
    </div>

    <div class="feature-cards">
        <!-- Feature 1 -->
        <div class="feature-card">
            <div class="feature-icon">
                <i class="bi bi-star"></i>
            </div>
            <h3>Produk Berkualitas</h3>
            <p>Kami hanya menyediakan material bangunan dengan kualitas terbaik yang sudah teruji dan terpercaya.</p>
        </div>

        <!-- Feature 2 -->
        <div class="feature-card">
            <div class="feature-icon">
                <i class="bi bi-truck"></i>
            </div>
            <h3>Pengiriman Cepat</h3>
            <p>Layanan pengiriman cepat ke lokasi Anda dengan biaya terjangkau dan dapat dipercaya.</p>
        </div>

        <!-- Feature 3 -->
        <div class="feature-card">
            <div class="feature-icon">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <h3>Harga Bersaing</h3>
            <p>Dapatkan harga yang kompetitif untuk semua jenis produk bangunan dan material konstruksi.</p>
        </div>
    </div>
</div>

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
            } else {
                menuIcon.classList.remove("fa-times");
                menuIcon.classList.add("fa-bars"); // Mengubah kembali menjadi ikon hamburger
                menuIcon.style.color = "#007bff"; // Warna biru saat menu tertutup
            }
        });
    });

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
            } else {
                menuIcon.classList.remove("fa-times");
                menuIcon.classList.add("fa-bars");
                menuIcon.style.color = "#007bff";
            }
        });

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
    });
</script>

</body>
</html>