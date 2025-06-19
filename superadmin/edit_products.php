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

// Direktori Upload
$upload_dir = __DIR__ . '/../uploads/';

// Cek dan buat folder uploads jika belum ada
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Cek apakah ada ID yang dikirim
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM product_222060 WHERE id_222060 = $id");

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

// Proses update produk
if (isset($_POST['update_product'])) {
    $product_name = htmlspecialchars($_POST['product_name']);
    $description = htmlspecialchars($_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock']; // Tambahkan stock
    $old_image = $product['product_image_222060'];
    $update_image = false;

    // Cek apakah user mengunggah gambar baru
    if ($_FILES['product_image']['size'] > 0) {
        $image = $_FILES['product_image']['name'];
        $image_tmp = $_FILES['product_image']['tmp_name'];
        $image_ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        
        // Validasi ekstensi gambar
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($image_ext, $allowed_ext)) {
            $unique_name = uniqid() . '.' . $image_ext;
            $target = $upload_dir . $unique_name;
            
            if (move_uploaded_file($image_tmp, $target)) {
                // Hapus gambar lama jika berhasil mengupload yang baru
                if (!empty($old_image) && file_exists($upload_dir . $old_image)) {
                    unlink($upload_dir . $old_image);
                }
                $update_image = true;
            } else {
                $error_message = "Gagal mengupload gambar baru.";
            }
        } else {
            $error_message = "Format gambar tidak diizinkan. Hanya jpg, jpeg, png, dan gif.";
        }
    }

    // Update data ke database
    if (isset($error_message)) {
        // Jika ada error saat upload gambar
        echo $error_message;
    } else {
        if ($update_image) {
            $query = "UPDATE product_222060 SET 
                      product_name_222060 = '$product_name', 
                      description_222060 = '$description', 
                      price_222060 = '$price', 
                      stock_222060 = '$stock',
                      product_image_222060 = '$unique_name' 
                      WHERE id_222060 = $id";
        } else {
            $query = "UPDATE product_222060 SET 
                      product_name_222060 = '$product_name', 
                      description_222060 = '$description', 
                      price_222060 = '$price',
                      stock_222060 = '$stock'
                      WHERE id_222060 = $id";
        }
        
        if ($conn->query($query) === TRUE) {
            header("Location: products.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/edit_products.css">
    <style>
        /* Tambahan style untuk input stok */
        .stock-input {
            position: relative;
        }
        
        .stock-input .stock-controls {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .stock-input .stock-controls button {
            flex: 1;
            border: none;
            background-color: #f0f0f0;
            cursor: pointer;
            width: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stock-input .stock-controls button:hover {
            background-color: #e0e0e0;
        }
        
        .stock-input .stock-controls button:first-child {
            border-bottom: 1px solid #ddd;
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
    <section>
        <h2><i class="bi bi-pencil-square"></i> Edit Produk</h2>
        <div class="form-container">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="product_name">Nama Produk</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" value="<?= htmlspecialchars($product['product_name_222060']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($product['description_222060']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Harga (Rp)</label>
                    <input type="number" class="form-control" id="price" name="price" value="<?= $product['price_222060'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stok</label>
                    <div class="stock-input">
                        <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?= isset($product['stock_222060']) ? $product['stock_222060'] : 0 ?>" required>
                        <div class="stock-controls">
                            <button type="button" onclick="incrementStock()"><i class="bi bi-plus"></i></button>
                            <button type="button" onclick="decrementStock()"><i class="bi bi-dash"></i></button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="product_image">Gambar Produk</label>
                    <input type="file" class="form-control" id="product_image" name="product_image">
                    <small class="text-muted">Format yang diizinkan: JPG, JPEG, PNG, GIF. Biarkan kosong jika tidak ingin mengubah gambar.</small>
                    
                    <!-- Image Preview -->
                    <?php if(!empty($product['product_image_222060'])): ?>
                    <div class="image-preview-container">
                        <span class="preview-label">Gambar Saat Ini:</span>
                        <img src="../uploads/<?= htmlspecialchars($product['product_image_222060']) ?>" class="current-image" alt="Current Product Image">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="button-group" style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update_product" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Simpan Perubahan
                    </button>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Batal
                    </a>
                </div>
            </form>
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

        // Optional: Preview image before upload
        document.getElementById('product_image').addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Create preview container if it doesn't exist
                    let previewContainer = document.querySelector('.image-preview-container');
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'image-preview-container';
                        document.getElementById('product_image').parentNode.appendChild(previewContainer);
                    }
                    
                    // Update or create the preview label
                    let previewLabel = previewContainer.querySelector('.preview-label');
                    if (!previewLabel) {
                        previewLabel = document.createElement('span');
                        previewLabel.className = 'preview-label';
                        previewContainer.appendChild(previewLabel);
                    }
                    previewLabel.textContent = 'Pratinjau Gambar Baru:';
                    
                    // Update or create the image element
                    let previewImage = previewContainer.querySelector('.current-image');
                    if (!previewImage) {
                        previewImage = document.createElement('img');
                        previewImage.className = 'current-image';
                        previewContainer.appendChild(previewImage);
                    }
                    previewImage.src = event.target.result;
                    previewImage.alt = 'Preview';
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Fungsi untuk menambah/mengurangi stok
    function incrementStock() {
        const stockInput = document.getElementById('stock');
        let value = parseInt(stockInput.value) || 0;
        stockInput.value = value + 1;
    }
    
    function decrementStock() {
        const stockInput = document.getElementById('stock');
        let value = parseInt(stockInput.value) || 0;
        if (value > 0) {
            stockInput.value = value - 1;
        }
    }
</script>

</body>
</html>