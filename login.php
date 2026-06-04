<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, username, full_name, email, role FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['full_name'] = $row['full_name'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['role'] = $row['role'];
        
        if ($row['role'] == 'Admin' && !getenv('RAILWAY_PUBLIC_DOMAIN')) {
            header("Location: http://localhost:8080/ADSSU_LAMS");
        } else {
            header("Location: borrower/dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="bubbles">
        <div class="bubble" style="width:300px;height:300px;top:-100px;left:-100px;animation-duration:18s;"></div>
        <div class="bubble" style="width:200px;height:200px;bottom:-50px;right:-50px;animation-duration:22s;"></div>
        <div class="bubble" style="width:150px;height:150px;top:50%;left:10%;animation-duration:15s;"></div>
        <div class="bubble" style="width:100px;height:100px;bottom:20%;right:15%;animation-duration:20s;"></div>
        <div class="bubble" style="width:250px;height:250px;top:20%;right:-50px;animation-duration:17s;"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-microscope"></i>
                </div>
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Laboratory Asset Monitoring System</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <div class="register-link">
                <a href="register.php"><i class="fas fa-user-plus"></i> Create New Account</a>
            </div>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> ADSSU LAMS. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>
