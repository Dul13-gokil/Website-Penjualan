<?php
include 'config/config.php';

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'customer';
    $created_at = date('Y-m-d H:i:s');

    // Cek apakah username atau email sudah terdaftar
    $check_query = "SELECT * FROM user_222060 WHERE username_222060='$username' OR email_222060='$email'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        $error = "Username atau Email sudah terdaftar!";
    } else {
        $query = "INSERT INTO user_222060 (username_222060, email_222060, password_222060, role_222060, created_at_222060) 
                  VALUES ('$username', '$email', '$password', '$role', '$created_at')";

        if ($conn->query($query) === TRUE) {
            header("Location: costumer_login.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pelanggan</title>
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
        
        .register-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .register-container:hover {
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
        }
        
        form button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        
        .login-link {
            margin-top: 20px;
            text-align: center;
            padding-top: 15px;
            border-top: 1px dashed #e0e0e0;
        }
        
        .login-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
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
        
        /* Logo */
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .login-logo i {
            font-size: 48px;
            color: #007bff;
        }
        
        /* Copyright */
        .copyright {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 25px;
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
    </style>
</head>
<body>

    <!-- Background Pattern -->
    <div class="bg-pattern"></div>

    <div class="register-container">
        <h2>Registrasi Pelanggan</h2>

        <?php if (isset($error)) { ?>
            <p class="error"><?= $error ?></p>
        <?php } ?>

        <form method="post">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
                <i class="bi bi-person"></i>
            </div>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
                <i class="bi bi-envelope"></i>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <i class="bi bi-lock"></i>
            </div>
            <button type="submit" name="register">
            <i class="bi bi-box-arrow-in-right"></i> Daftar
            </button>
            
        </form>

        <div class="login-link">
            <p>Sudah punya akun? <a href="costumer_login.php">Login di sini</a></p>
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
</html>
