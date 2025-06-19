<?php
include '../config/config.php';
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../costumer_login.php");
    exit();
}

// Ambil semua user dari database
$users = $conn->query("SELECT * FROM user_222060");

// Tambah user baru
if (isset($_POST['add_user'])) {
    $username = $_POST['username_222060'];
    $email = $_POST['email_222060'];
    $password = password_hash($_POST['password_222060'], PASSWORD_DEFAULT);
    $role = 'customer'; // Default role
    $created_at = date('Y-m-d H:i:s');

    $query = "INSERT INTO user_222060 (username_222060, email_222060, password_222060, role_222060, created_at_222060) 
              VALUES ('$username', '$email', '$password', '$role', '$created_at')";
              
    if ($conn->query($query) === TRUE) {
        header("Location: users.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Hapus user
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM user_222060 WHERE id_222060=$id");
    header("Location: users.php");
    exit();
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User</title>
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

        /* Background pattern - subtle dots */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(rgba(52, 58, 64, 0.03) 2px, transparent 2px),
                radial-gradient(rgba(52, 58, 64, 0.03) 2px, transparent 2px);
            background-size: 40px 40px;
            background-position: 0 0, 20px 20px;
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

        /* Card Styling (Different from products.php) */
        .card-section {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            border-left: 5px solid #343a40;
        }

        .card-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-section h2 {
            color: #343a40;
            margin-bottom: 20px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-section h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 3px;
            background-color: #343a40;
        }

        /* Form Styling (Different from products.php) */
        .user-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .user-form:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: #343a40;
            box-shadow: 0 0 5px rgba(52, 58, 64, 0.3);
            outline: none;
        }

        .form-control::placeholder {
            color: #adb5bd;
        }

        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            padding: 12px 20px;
            font-size: 16px;
            line-height: 1.5;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #343a40, #495057);
            border-color: #343a40;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #495057, #343a40);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-danger {
            color: #fff;
            background: linear-gradient(135deg, #dc3545, #c71f37);
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c71f37, #dc3545);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }

        /* User Card Styling (Unique to users.php) */
        .user-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .user-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            border-top: 4px solid #343a40;
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .user-card .user-icon {
            width: 60px;
            height: 60px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: #343a40;
        }

        .user-card h3 {
            margin: 10px 0;
            color: #343a40;
            font-weight: 600;
        }

        .user-card p {
            color: #6c757d;
            margin-bottom: 15px;
        }

        .user-card .card-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .badge-customer {
            background-color: #e9ecef;
            color: #343a40;
        }

        .badge-admin {
            background-color: #343a40;
            color: #fff;
        }

        /* Table View Toggle Button (Unique to users.php) */
        .view-toggle {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .toggle-btn {
            background-color: #343a40;
            color: #fff;
            border: none;
            padding: 8px 15px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .toggle-btn.active {
            background-color: #495057;
        }

        .toggle-btn:hover {
            background-color: #495057;
        }

        /* Table Styling */
        .table-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-top: 20px;
            display: none; /* Hidden by default */
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        .styled-table thead tr {
            background-color: #343a40;
            color: #ffffff;
            text-align: left;
            font-weight: 500;
        }

        .styled-table th, .styled-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
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

        /* Search Bar */
        .search-bar {
            margin-bottom: 20px;
            width: 100%;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: white;
            padding-left: 40px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23adb5bd' class='bi bi-search' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 15px center;
            background-size: 16px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #343a40;
            box-shadow: 0 0 0 0.2rem rgba(52, 58, 64, 0.25);
        }

        /* Responsive styling */
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
            
            .user-form {
                grid-template-columns: 1fr;
            }
            
            .user-cards {
                grid-template-columns: 1fr;
            }
            
            /* Mobile styling for table */
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
            
            .styled-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.9em;
                text-align: right;
                padding: 12px 15px;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .styled-table td::before {
                content: attr(data-label);
                float: left;
                font-weight: bold;
                color: #343a40;
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
    <h2>Admin</h2>
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
        <li><a href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a></li>
    </ul>

    <!-- Copyright -->
    <div class="copyright">
        &copy; <?= date("Y"); ?> Admin Panel. All rights reserved.
    </div>
</aside>

<!-- Konten Utama -->
<main class="content">
    <div class="card-section">
        <h2><i class="bi bi-person-plus-fill"></i> Tambah User Baru</h2>
        
        <form method="post" class="user-form">
            <div class="form-group">
                <input type="text" class="form-control" name="username_222060" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="email" class="form-control" name="email_222060" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password_222060" placeholder="Password" required>
            </div>
            <div class="form-group">
                <button type="submit" name="add_user" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Tambah User
                </button>
            </div>
        </form>
    </div>

    <div class="card-section">
        <h2><i class="bi bi-people-fill"></i> Daftar User</h2>
        
        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari username atau email...">
        </div>
        
        <!-- Toggle View -->
        <div class="view-toggle">
            <button class="toggle-btn active" id="cardView"><i class="bi bi-grid"></i> Card View</button>
            <button class="toggle-btn" id="tableView"><i class="bi bi-table"></i> Table View</button>
        </div>
        
        <!-- User Cards View -->
        <div class="user-cards" id="userCardsContainer">
            <?php 
            $no = 1;
            while ($row = $users->fetch_assoc()) { ?>
                <div class="user-card">
                    <div class="user-icon">
                        <i class="bi bi-person"></i>
                    </div>
                    <span class="badge <?= $row['role_222060'] == 'admin' ? 'badge-admin' : 'badge-customer' ?>">
                        <?= htmlspecialchars($row['role_222060']) ?>
                    </span>
                    <h3><?= htmlspecialchars($row['username_222060']) ?></h3>
                    <p><?= htmlspecialchars($row['email_222060']) ?></p>
                    <p class="text-muted"><small>Dibuat: <?= date('d M Y', strtotime($row['created_at_222060'])) ?></small></p>

                </div>
            <?php } ?>
        </div>
        
        <!-- Table View -->
        <div class="table-container" id="tableContainer">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Reset pointer to beginning of result set
                    $users->data_seek(0);
                    $no = 1;
                    while ($row = $users->fetch_assoc()) { ?>
                        <tr>
                            <td data-label="No"><?= $no++ ?></td>
                            <td data-label="Username"><?= htmlspecialchars($row['username_222060']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($row['email_222060']) ?></td>
                            <td data-label="Role"><?= htmlspecialchars($row['role_222060']) ?></td>
                            <td data-label="Tanggal Dibuat"><?= date('d M Y', strtotime($row['created_at_222060'])) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
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

        // Toggle between card and table view
        document.getElementById("cardView").addEventListener("click", function() {
            document.getElementById("userCardsContainer").style.display = "grid";
            document.getElementById("tableContainer").style.display = "none";
            this.classList.add("active");
            document.getElementById("tableView").classList.remove("active");
        });

        document.getElementById("tableView").addEventListener("click", function() {
            document.getElementById("userCardsContainer").style.display = "none";
            document.getElementById("tableContainer").style.display = "block";
            this.classList.add("active");
            document.getElementById("cardView").classList.remove("active");
        });

        // Search functionality
        document.getElementById("searchInput").addEventListener("keyup", function() {
            const searchValue = this.value.toLowerCase();
            
            // Filter cards
            const cards = document.querySelectorAll(".user-card");
            cards.forEach(function(card) {
                const username = card.querySelector("h3").textContent.toLowerCase();
                const email = card.querySelector("p").textContent.toLowerCase();
                
                if (username.includes(searchValue) || email.includes(searchValue)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
            
            // Filter table rows
            const rows = document.querySelectorAll(".styled-table tbody tr");
            rows.forEach(function(row) {
                const username = row.querySelector("td[data-label='Username']").textContent.toLowerCase();
                const email = row.querySelector("td[data-label='Email']").textContent.toLowerCase();
                
                if (username.includes(searchValue) || email.includes(searchValue)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    });
</script>

</body>
</html>