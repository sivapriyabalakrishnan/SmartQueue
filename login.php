<?php
include 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'admin.php' : 'dashboard.php'));
    exit();
}

$error_message = '';
$success_message = '';

// Check if user just registered
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success_message = 'Registration successful! Please login with your credentials.';
}

if (isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Auto-detect admin by email domain
            $role = $user['role'];
            if (strpos($email, '@admin.com') !== false) {
                // If email contains @admin.com, set as admin
                $role = 'admin';
                // Update role in database if not already admin
                if ($user['role'] !== 'admin') {
                    $updateStmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                    $updateStmt->bind_param("i", $user['id']);
                    $updateStmt->execute();
                }
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $role;
            $_SESSION['user_name'] = $user['name'];

            header("Location: " . ($role == 'admin' ? 'admin.php' : 'dashboard.php'));
            exit();
        } else {
            $error_message = 'Invalid email or password';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Queue Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Full Page Login Styles */
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #800020 0%, #1a1a1a 100%);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .login-page {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
            z-index: 1;
        }

        /* Left Side - Branding */
        .login-left {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            animation: slideInLeft 0.8s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .brand-section {
            max-width: 500px;
        }

        .brand-logo {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #800020, #7c3aed);
            border-radius: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .brand-logo svg {
            width: 60px;
            height: 60px;
            color: white;
        }

        .brand-title {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            line-height: 1.1;
            background: linear-gradient(135deg, #ffffff 0%, rgba(255, 255, 255, 0.8) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 3rem;
            font-weight: 300;
        }

        .features-list {
            text-align: left;
            margin-top: 3rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 1.125rem;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon svg {
            width: 24px;
            height: 24px;
        }

        /* Right Side - Login Form */
        .login-right {
            background: white;
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            animation: slideInRight 0.8s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .login-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }

        .login-header {
            margin-bottom: 3rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #800020;
            gap: 0.75rem;
        }

        .login-title {
            font-size: 2.5rem;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 1rem;
            color: #64748b;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #0f172a;
            font-size: 0.9375rem;
        }

        .form-input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-input:focus {
            outline: none;
            border-color: #800020;
            background: white;
            box-shadow: 0 0 0 4px rgba(128, 0, 32, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: #800020;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
            cursor: pointer;
        }

        .remember-me label {
            margin: 0;
            font-size: 0.9375rem;
            color: #475569;
            cursor: pointer;
        }

        .forgot-link {
            color: #800020;
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: #5c0016;
        }

        .btn-login {
            width: 100%;
            padding: 1.125rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(128, 0, 32, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(128, 0, 32, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 2rem 0;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .divider-text {
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .social-btn {
            padding: 0.875rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #475569;
        }

        .social-btn:hover {
            border-color: #800020;
            background: #f8fafc;
        }

        .signup-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
        }

        .signup-link a {
            color: #800020;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .signup-link a:hover {
            color: #5c0016;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }

        .alert-success {
            background: rgba(128, 0, 32, 0.1);
            border-left: 4px solid #800020;
            color: #065f46;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .login-left {
                display: none;
            }

            .login-right {
                padding: 2rem;
            }

            .brand-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 640px) {
            .login-right {
                padding: 1.5rem;
            }

            .login-title {
                font-size: 2rem;
            }

            .social-login {
                grid-template-columns: 1fr;
            }
        }

        /* Loading State */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <div class="brand-section">
                <div class="brand-logo">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="brand-title">Smart Queue Manager</h1>
                <p class="brand-subtitle">Digital queue and appointment management system designed to minimize waiting times</p>

                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span>Instant queue number generation</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span>Real-time position tracking</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span>Easy appointment scheduling</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span>Reduce wait times by 50%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-container">
                <div class="login-header">
                    <a href="index.html" class="back-link">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Home
                    </a>
                    <h2 class="login-title">Welcome Back!</h2>
                    <p class="login-subtitle">Please sign in to your account to continue</p>
                </div>

                <!-- Alert Messages -->
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
                    <!-- Email Field -->
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="form-input-wrapper">
                            <div class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input 
                                type="email" 
                                id="email"
                                name="email" 
                                class="form-input" 
                                placeholder="you@example.com" 
                                required 
                                autofocus
                                autocomplete="email"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="form-input-wrapper">
                            <div class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input 
                                type="password" 
                                id="password"
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password" 
                                required
                                autocomplete="current-password"
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePassword()"
                                aria-label="Toggle password visibility"
                            >
                                <svg id="eye-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="login" class="btn-login" id="loginBtn">
                        <span>Sign In</span>
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </form>

                <!-- Divider -->
                <div class="divider">
                    <div class="divider-line"></div>
                    <span class="divider-text">Or continue with</span>
                    <div class="divider-line"></div>
                </div>

                <!-- Social Login -->
                <div class="social-login">
                    <button class="social-btn" onclick="alert('Google login - to be implemented')">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google
                    </button>
                    <button class="social-btn" onclick="alert('Facebook login - to be implemented')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </button>
                </div>

                <!-- Sign Up Link -->
                <div class="signup-link">
                    Don't have an account? <a href="register.php">Sign up for free</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                `;
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<div class="spinner"></div><span>Signing in...</span>';
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.animation = 'fadeOut 0.5s ease-out';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });

        // Add fade out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-20px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>