<?php
include '../config/config.php';
session_start();
if (!isset($_SESSION['super_admin_logged_in']) || $_SESSION['super_admin_logged_in'] !== true) {
    header("Location: ../costumer_login.php");
    exit();
}
// Tambahkan kolom stock_222060 jika belum ada
$check_column = $conn->query("SHOW COLUMNS FROM product_222060 LIKE 'stock_222060'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE product_222060 ADD stock_222060 INT NOT NULL DEFAULT 0");
}

$products = $conn->query("SELECT * FROM product_222060");

// Direktori Upload
$upload_dir = __DIR__ . '/../uploads/';

// Cek dan buat folder uploads jika belum ada
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Tambah produk baru
if (isset($_POST['add_product'])) {
    $product_name = htmlspecialchars($_POST['product_name']);
    $description = htmlspecialchars($_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock']; // Tambahkan stock

    // Proses upload gambar
    $image = $_FILES['product_image']['name'];
    $image_tmp = $_FILES['product_image']['tmp_name'];
    $image_ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));

    // Validasi ekstensi gambar
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($image_ext, $allowed_ext)) {
        $unique_name = uniqid() . '.' . $image_ext;
        $target = $upload_dir . $unique_name;

        if (move_uploaded_file($image_tmp, $target)) {
            $query = "INSERT INTO product_222060 (product_name_222060, description_222060, price_222060, stock_222060, product_image_222060) 
                      VALUES ('$product_name', '$description', '$price', '$stock', '$unique_name')";
            if ($conn->query($query) === TRUE) {
                header("Location: products.php");
                exit();
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "Gagal mengupload gambar.";
        }
    } else {
        echo "Format gambar tidak diizinkan. Hanya jpg, jpeg, png, dan gif.";
    }
}

// Update stok produk
if (isset($_POST['update_stock'])) {
    $product_id = $_POST['product_id'];
    $new_stock = $_POST['new_stock'];
    
    $query = "UPDATE product_222060 SET stock_222060 = $new_stock WHERE id_222060 = $product_id";
    
    if ($conn->query($query) === TRUE) {
        header("Location: products.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Hapus produk
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Hapus gambar terkait
    $result = $conn->query("SELECT product_image_222060 FROM product_222060 WHERE id_222060 = $id");
    if ($result && $row = $result->fetch_assoc()) {
        $image_path = $upload_dir . $row['product_image_222060'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Hapus data produk
    $conn->query("DELETE FROM product_222060 WHERE id_222060 = $id");
    header("Location: products.php");
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
    <title>Kelola Produk</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/products.css">
    <style>
        /* Tambahan CSS untuk modal stok */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            animation: slideIn 0.3s;
        }
        
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-btn:hover {
            color: #555;
        }
        
        @keyframes fadeIn {
            from {opacity: 0}
            to {opacity: 1}
        }
        
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        /* Badge stok */
        .stock-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .stock-high {
            background-color: #d4edda;
            color: #155724;
        }
        
        .stock-medium {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .stock-low {
            background-color: #f8d7da;
            color: #721c24;
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
        <h2><i class="bi bi-plus-circle"></i> Tambah Produk Baru</h2>
        <div class="form-container">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="product_name">Nama Produk</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Masukkan nama produk" required>
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Deskripsi produk" required></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Harga (Rp)</label>
                    <input type="number" class="form-control" id="price" name="price" placeholder="Masukkan harga" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stok</label>
                    <input type="number" class="form-control" id="stock" name="stock" min="0" placeholder="Masukkan jumlah stok" required>
                </div>
                <div class="form-group">
                    <label for="product_image">Gambar Produk</label>
                    <input type="file" class="form-control" id="product_image" name="product_image" required>
                    <small class="text-muted">Format yang diizinkan: JPG, JPEG, PNG, GIF</small>
                </div>
                <button type="submit" name="add_product" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Produk
                </button>
            </form>
        </div>
    </section>

    <section>
        <h2><i class="bi bi-box-seam"></i> Daftar Produk</h2>
        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Produk</th>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Gambar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = $products->fetch_assoc()) { 
                        // Tentukan class badge berdasarkan jumlah stok
                        $stock_class = '';
                        $stock_value = isset($row['stock_222060']) ? $row['stock_222060'] : 0;
                        
                        if ($stock_value > 10) {
                            $stock_class = 'stock-high';
                        } elseif ($stock_value > 5) {
                            $stock_class = 'stock-medium';
                        } else {
                            $stock_class = 'stock-low';
                        }
                    ?>
                        <tr>
                            <td data-label="No"><?= $no++ ?></td>
                            <td data-label="Nama Produk"><?= htmlspecialchars($row['product_name_222060']) ?></td>
                            <td data-label="Deskripsi" class="truncate" title="<?= htmlspecialchars($row['description_222060']) ?>"><?= htmlspecialchars($row['description_222060']) ?></td>
                            <td data-label="Harga">Rp<?= number_format($row['price_222060'], 0, ',', '.') ?></td>
                            <td data-label="Stok">
                                <span class="stock-badge <?= $stock_class ?>"><?= $stock_value ?></span>
                                <button class="btn btn-sm btn-outline-primary" onclick="openStockModal(<?= $row['id_222060'] ?>, <?= $stock_value ?>)">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                            <td data-label="Gambar">
                                <img src="../uploads/<?= htmlspecialchars($row['product_image_222060']) ?>" class="product-image" alt="<?= htmlspecialchars($row['product_name_222060']) ?>">
                            </td>
                            <td data-label="Aksi">
                                <div class="action-buttons">
                                    <a href="edit_products.php?id=<?= $row['id_222060'] ?>" class="btn btn-info btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="products.php?delete=<?= $row['id_222060'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Modal Update Stok -->
<div id="stockModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeStockModal()">&times;</span>
        <h3>Update Stok</h3>
        <form method="post" id="stockForm">
            <input type="hidden" name="product_id" id="product_id">
            <div class="form-group">
                <label for="new_stock">Jumlah Stok</label>
                <input type="number" class="form-control" id="new_stock" name="new_stock" min="0" required>
            </div>
            <button type="submit" name="update_stock" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Simpan
            </button>
        </form>
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

    // Fungsi Modal Stok
    function openStockModal(productId, currentStock) {
        document.getElementById('product_id').value = productId;
        document.getElementById('new_stock').value = currentStock;
        document.getElementById('stockModal').style.display = 'block';
    }

    function closeStockModal() {
        document.getElementById('stockModal').style.display = 'none';
    }

    // Close modal jika user klik di luar modal
    window.onclick = function(event) {
        let modal = document.getElementById('stockModal');
        if (event.target == modal) {
            closeStockModal();
        }
    }
</script>

</body>
</html>
