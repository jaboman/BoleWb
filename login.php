<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password_hash, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if ($user['status'] !== 'Active') {
                $error = 'Your account is ' . strtolower($user['status']) . '. Please contact support.';
            } else {
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Update last login
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    $update_stmt->bind_param("i", $user['user_id']);
                    $update_stmt->execute();
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    
                    // Set remember me cookie if selected
                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        setcookie('remember_token', $token, $expiry, '/');
                        setcookie('remember_user', $user['user_id'], $expiry, '/');
                        
                        // Store token in database
                        $token_hash = hash('sha256', $token);
                        $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
                        $stmt->bind_param("si", $token_hash, $user['user_id']);
                        $stmt->execute();
                    }
                    
                    // Log activity
                    log_activity($user['user_id'], 'login', 'User logged in');
                    
                    // Add welcome notification
                    add_notification($user['user_id'], 'Welcome Back!', 'You have successfully logged in.', 'System');
                    
                    // Redirect to dashboard or previous page
                    if (isset($_SESSION['redirect_url'])) {
                        $redirect_url = $_SESSION['redirect_url'];
                        unset($_SESSION['redirect_url']);
                        header('Location: ' . $redirect_url);
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bole Town</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: white;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 3rem;
            color: #2c5aa0;
            margin-bottom: 15px;
        }
        .form-control {
            padding: 12px;
            border-radius: 8px;
        }
        .btn-login {
            padding: 12px;
            font-size: 1.1rem;
        }
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
        }
        .divider span {
            background: white;
            padding: 0 15px;
            color: #666;
        }
        .social-login {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .social-btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
            background: white;
            transition: all 0.3s;
        }
        .social-btn:hover {
            background: #f5f5f5;
        }
        .social-btn i {
            margin-right: 8px;
        }
        .facebook-btn {
            color: #3b5998;
            border-color: #3b5998;
        }
        .google-btn {
            color: #db4437;
            border-color: #db4437;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-user-circle"></i>
                <h1>Welcome Back</h1>
                <p>Login to access your Bole Town account</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group form-check">
                    <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                    <label class="form-check-label" for="remember_me">Remember me</label>
                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot Password?</a>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="divider">
                    <span>Or continue with</span>
                </div>
                
                <div class="social-login">
                    <a href="#" class="social-btn facebook-btn">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="#" class="social-btn google-btn">
                        <i class="fab fa-google"></i> Google
                    </a>
                </div>
                
                <div class="text-center">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                    <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Auto-focus email field
        document.getElementById('email').focus();
    </script>
</body>
</html>