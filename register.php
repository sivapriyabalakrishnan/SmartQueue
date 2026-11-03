<?php
include 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'admin.php' : 'dashboard.php'));
    exit();
}

$error_message = '';
$success_message = '';
$name_error = '';
$password_error = '';

if (isset($_POST['register'])) {
    $name = sanitize_input($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Name validation - only alphabets and spaces allowed
    if (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $name_error = 'Name should contain only alphabets (no numbers allowed)';
    }
    
    // Password validation
    if (strlen($password) < 8) {
        $password_error = 'Password must be at least 8 characters';
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $password_error = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match("/[a-z]/", $password)) {
        $password_error = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match("/[0-9]/", $password)) {
        $password_error = 'Password must contain at least one number';
    } elseif (!preg_match("/[@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/", $password)) {
        $password_error = 'Password must contain at least one special character (@, #, $, etc.)';
    }
    
    // Validation
    if ($name_error) {
        $error_message = $name_error;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } elseif ($password_error) {
        $error_message = $password_error;
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = 'Email already registered';
        } else {
            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
            
            if ($stmt->execute()) {
                // Redirect to login page after successful registration
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error_message = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Queue Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #800020 0%, #1a1a1a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .register-container {
            background: white;
            max-width: 500px;
            width: 100%;
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            font-size: 2rem;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: #64748b;
            font-size: 1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #800020;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            gap: 0.75rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #800020;
            box-shadow: 0 0 0 4px rgba(128, 0, 32, 0.1);
        }

        .btn-register {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(128, 0, 32, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(128, 0, 32, 0.1);
            border-left: 4px solid #800020;
            color: #065f46;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
        }

        .login-link a {
            color: #800020;
            text-decoration: none;
            font-weight: 700;
        }

        .login-link a:hover {
            color: #5c0016;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="index.html" class="back-link">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Home
        </a>

        <div class="register-header">
            <h1>Create Account</h1>
            <p>Join Smart Queue Manager today</p>
        </div>

        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" id="name" class="form-input" placeholder="John Doe" required>
                <small id="nameError" class="error-text" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></small>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-input" placeholder="+1234567890" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-input" placeholder="Create a strong password" required>
                <div id="passwordRequirements" style="margin-top: 0.75rem; padding: 0.75rem; background: #f1f5f9; border-radius: 0.5rem; font-size: 0.8125rem;">
                    <p style="font-weight: 600; color: #0f172a; margin-bottom: 0.5rem;">Password must contain:</p>
                    <div id="req-length" class="requirement" style="color: #64748b; margin: 0.25rem 0;">✗ At least 8 characters</div>
                    <div id="req-uppercase" class="requirement" style="color: #64748b; margin: 0.25rem 0;">✗ One uppercase letter (A-Z)</div>
                    <div id="req-lowercase" class="requirement" style="color: #64748b; margin: 0.25rem 0;">✗ One lowercase letter (a-z)</div>
                    <div id="req-number" class="requirement" style="color: #64748b; margin: 0.25rem 0;">✗ One number (0-9)</div>
                    <div id="req-special" class="requirement" style="color: #64748b; margin: 0.25rem 0;">✗ One special character (@, #, $, etc.)</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder="Re-enter password" required>
                <small id="confirmError" class="error-text" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></small>
            </div>

            <button type="submit" name="register" class="btn-register" id="submitBtn">
                Create Account
            </button>
        </form>

        <script>
            // Name validation - only letters and spaces
            document.getElementById('name').addEventListener('input', function(e) {
                const name = e.target.value;
                const nameError = document.getElementById('nameError');
                const regex = /^[a-zA-Z ]*$/;
                
                if (name && !regex.test(name)) {
                    nameError.textContent = '✗ Name should contain only alphabets (no numbers allowed)';
                    nameError.style.display = 'block';
                    e.target.style.borderColor = '#ef4444';
                } else {
                    nameError.style.display = 'none';
                    e.target.style.borderColor = '#e2e8f0';
                }
            });

            // Password validation
            document.getElementById('password').addEventListener('input', function(e) {
                const password = e.target.value;
                
                // Check length
                const reqLength = document.getElementById('req-length');
                if (password.length >= 8) {
                    reqLength.style.color = '#10b981';
                    reqLength.innerHTML = '✓ At least 8 characters';
                } else {
                    reqLength.style.color = '#64748b';
                    reqLength.innerHTML = '✗ At least 8 characters';
                }
                
                // Check uppercase
                const reqUppercase = document.getElementById('req-uppercase');
                if (/[A-Z]/.test(password)) {
                    reqUppercase.style.color = '#10b981';
                    reqUppercase.innerHTML = '✓ One uppercase letter (A-Z)';
                } else {
                    reqUppercase.style.color = '#64748b';
                    reqUppercase.innerHTML = '✗ One uppercase letter (A-Z)';
                }
                
                // Check lowercase
                const reqLowercase = document.getElementById('req-lowercase');
                if (/[a-z]/.test(password)) {
                    reqLowercase.style.color = '#10b981';
                    reqLowercase.innerHTML = '✓ One lowercase letter (a-z)';
                } else {
                    reqLowercase.style.color = '#64748b';
                    reqLowercase.innerHTML = '✗ One lowercase letter (a-z)';
                }
                
                // Check number
                const reqNumber = document.getElementById('req-number');
                if (/[0-9]/.test(password)) {
                    reqNumber.style.color = '#10b981';
                    reqNumber.innerHTML = '✓ One number (0-9)';
                } else {
                    reqNumber.style.color = '#64748b';
                    reqNumber.innerHTML = '✗ One number (0-9)';
                }
                
                // Check special character
                const reqSpecial = document.getElementById('req-special');
                if (/[@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                    reqSpecial.style.color = '#10b981';
                    reqSpecial.innerHTML = '✓ One special character (@, #, $, etc.)';
                } else {
                    reqSpecial.style.color = '#64748b';
                    reqSpecial.innerHTML = '✗ One special character (@, #, $, etc.)';
                }
            });

            // Confirm password validation
            document.getElementById('confirm_password').addEventListener('input', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = e.target.value;
                const confirmError = document.getElementById('confirmError');
                
                if (confirmPassword && password !== confirmPassword) {
                    confirmError.textContent = '✗ Passwords do not match';
                    confirmError.style.display = 'block';
                    e.target.style.borderColor = '#ef4444';
                } else {
                    confirmError.style.display = 'none';
                    e.target.style.borderColor = '#e2e8f0';
                }
            });
        </script>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</body>
</html>
