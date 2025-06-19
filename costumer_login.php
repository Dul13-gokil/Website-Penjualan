<?php
include 'config/config.php';
session_start();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cek Superadmin
    $result = $conn->prepare("SELECT * FROM super_admin_222060 WHERE username_222060=?");
    $result->bind_param("s", $username);
    $result->execute();
    $superadmin = $result->get_result()->fetch_assoc();
    if ($superadmin && password_verify($password, $superadmin['password_222060'])) {
        $_SESSION['super_admin_logged_in'] = true;
        $_SESSION['super_admin_id'] = $superadmin['id_222060'];
        $_SESSION['super_admin_username'] = $superadmin['username_222060'];
        header("Location: superadmin/dashboard.php");
        exit();
    }

    // Cek Admin
    $result = $conn->prepare("SELECT * FROM admin_222060 WHERE username_222060=?");
    $result->bind_param("s", $username);
    $result->execute();
    $admin = $result->get_result()->fetch_assoc();
    if ($admin && password_verify($password, $admin['password_222060'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id_222060'];
        $_SESSION['admin_username'] = $admin['username_222060'];
        header("Location: admin/dashboard.php");
        exit();
    }

// Cek User
$result = $conn->prepare("SELECT * FROM user_222060 WHERE username_222060=?");
$result->bind_param("s", $username);
$result->execute();
$user = $result->get_result()->fetch_assoc();
if ($user && password_verify($password, $user['password_222060'])) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id_222060'];
    $_SESSION['user_username'] = $user['username_222060'];
    $_SESSION['username'] = $user['username_222060']; // ✅ Tambahan perbaikan di sini
    header("Location: costumer_home.php");
    exit();
}

    $error = "Username atau Password salah!";
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            justify-content: center;
            align-items: center;
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
                radial-gradient(rgba(0, 123, 255, 0.1) 3px, transparent 3px),
                radial-gradient(rgba(0, 123, 255, 0.1) 3px, transparent 3px);
            background-size: 50px 50px;
            background-position: 0 0, 25px 25px;
            z-index: -1;
        }

/* Mode Malam */
.night-mode {
    background-color: #1a1a1a;
    color: #e0e0e0;
}

.night-mode .login-container {
    background-color: #2c2c2c;
    color: #e0e0e0;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.night-mode .login-container:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
}

.night-mode h2 {
    color: #4da6ff;
}

.night-mode h2:after {
    background-color: #4da6ff;
}

.night-mode .input-group input {
    background-color: #3a3a3a;
    border-color: #555;
    color: #e0e0e0;
}

.night-mode .input-group input:focus {
    border-color: #4da6ff;
    box-shadow: 0 0 0 2px rgba(77, 166, 255, 0.25);
}

.night-mode .input-group input::placeholder {
    color: #b0b0b0;
}

.night-mode .input-group i {
    color: #4da6ff;
}

.night-mode form button {
    background-color: #4da6ff;
}

.night-mode form button:hover {
    background-color: #3d8bff;
}

.night-mode .register-link {
    border-top-color: rgba(255, 255, 255, 0.2);
}

.night-mode .register-link p {
    color: #b0b0b0;
}

.night-mode .register-link a {
    color: #4da6ff;
}

.night-mode .register-link a:hover {
    color: #3d8bff;
}

.night-mode .error {
    background-color: rgba(220, 53, 69, 0.2);
    color: #ff6b6b;
}

.night-mode .social-links a {
    background-color: #3a3a3a;
    color: #4da6ff;
}

.night-mode .social-links a:hover {
    background-color: #4da6ff;
    color: #fff;
}

.night-mode .copyright {
    color: #b0b0b0;
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
        
        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
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
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group input {
            width: 100%;
            padding: 14px 45px 14px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .input-group input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        .input-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        
        .input-group:hover i {
            transform: translateY(-50%) scale(1.05);
        }
        
        form button {
            width: 100%;
            padding: 14px;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        form button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        
        .register-link {
            margin-top: 20px;
            text-align: center;
            padding-top: 15px;
            border-top: 1px dashed #e0e0e0;
        }
        
        .register-link p {
            color: #666;
            font-size: 15px;
        }
        
        .register-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            margin-left: 5px;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        
        .error {
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            padding: 10px;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 8px;
        }
        
        /* Social Media Links */
        .social-links {
            display: flex;
            justify-content: center;
            margin-top: 25px;
        }
        
        .social-links a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: #f8f9fa;
            margin: 0 8px;
            color: #007bff;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: #007bff;
            color: #fff;
            transform: translateY(-3px);
        }
        

        
        /* Copyright */
        .copyright {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="theme-toggle" title="Toggle Mode Malam">
    <i class="fas fa-moon" id="theme-icon"></i>
</button>
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>
    
    <div class="login-container">

        <h2>Login</h2>
        
        <?php if (isset($error)) { ?>
            <div class="error">
                <i class="bi bi-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php } ?>
        
        <form method="post">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
                <i class="bi bi-person"></i>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <i class="bi bi-lock"></i>
            </div>
            <button type="submit" name="login">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
            </button>
        </form>
        
        <div class="register-link">
            <p>Mau Menjadi Pelanggan? <a href="costumer_register.php">Daftar di sini</a></p>
        </div>
        
        <!-- Social Media Links -->
        <div class="social-links">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
        
        <!-- Copyright -->
        <div class="copyright">
            &copy; <?= date("Y"); ?> Toko Bangunan. All rights reserved.
        </div>
    </div>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function() {
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
</html>