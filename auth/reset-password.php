<?php
require_once "../config.php";
// Basic PHP form handling for password reset
try {
    $message = '';
    $error = '';
    $token = $_GET['token'] ?? '';
    $email = $_GET['email'] ?? '';


    if ($_POST) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $reset_token = $_POST['token'] ?? '';


        // Basic validation
        if (empty($reset_token)) {
            $error = 'Invalid reset token.';
        } elseif (empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all password fields.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            // In a real application, you would verify the token and update the password in the database
            $check_time = "SELECT ver_code, email FROM user_table WHERE email = :email AND expiry >= now() - INTERVAL '1 hour'";
            $check_stmt = $dbh->prepare($check_time);
            $check_stmt->execute([':email' => $email]);
            $token_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            if ($token_result) {
                if (isset($token_result['ver_code']) && $token_result['ver_code'] == $reset_token) {
                    $new_pass = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pw = "UPDATE user_table SET pw = :pw WHERE email = :email";
                    $update_stmt = $dbh->prepare($update_pw);
                    $update_stmt->execute([':pw' => $new_pass, ':email' => $email]);
                    $message = 'Your password has been successfully reset! You can now log in with your new password.';
                    header("Location: login.php");
                    exit;
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
} catch (Exception $e) {
    error_log($e->getMessage());
}
include_once("../navbar.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .is-reset {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(33, 150, 243, 0.1);
            width: 100%;
            max-width: 450px;
            border: 1px solid #e3f2fd;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-header h1 {
            color: #1976d2;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .reset-header p {
            color: #64b5f6;
            font-size: 14px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1976d2;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e3f2fd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2196f3;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .form-group input.valid {
            border-color: #4caf50;
            background-color: #f1f8e9;
        }

        .form-group input.invalid {
            border-color: #f44336;
            background-color: #ffebee;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }

        .strength-bar {
            height: 4px;
            background: #e3f2fd;
            border-radius: 2px;
            margin: 5px 0;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak .strength-fill {
            width: 33%;
            background: linear-gradient(90deg, #ff9800, #ff5722);
        }

        .strength-medium .strength-fill {
            width: 66%;
            background: linear-gradient(90deg, #2196f3, #03a9f4);
        }

        .strength-strong .strength-fill {
            width: 100%;
            background: linear-gradient(90deg, #4caf50, #8bc34a);
        }

        .validation-message {
            font-size: 12px;
            margin-top: 5px;
            min-height: 16px;
        }

        .validation-message.error {
            color: #f44336;
        }

        .validation-message.success {
            color: #4caf50;
        }

        .requirements {
            font-size: 12px;
            color: #64b5f6;
            margin-top: 8px;
            background: #f8fcff;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #2196f3;
        }

        .requirements ul {
            margin: 5px 0 0 16px;
        }

        .requirements li {
            margin: 3px 0;
            transition: color 0.3s ease;
        }

        .requirements li.met {
            color: #4caf50;
            font-weight: 500;
        }

        .reset-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .reset-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }

        .reset-btn:disabled {
            background: #bbdefb;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .back-link {
            text-align: center;
        }

        .back-link a {
            color: #2196f3;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #1976d2;
            text-decoration: underline;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .message.success {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .token-info {
            background: #f8fcff;
            border: 1px solid #e3f2fd;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #1976d2;
            text-align: center;
        }

        @media (max-width: 480px) {
            .reset-container {
                padding: 30px 20px;
            }

            .reset-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="is-reset">
        <div class="reset-container">
            <div class="reset-header">
                <h1>Set New Password</h1>
                <!--<p>Please enter your new password below. Make sure its strong and secure.</p>-->
            </div>

            <?php if (empty($token)): ?>
                <div class="message error">
                    Invalid or missing reset token. Please request a new password reset link.
                </div>
                <div class="back-link">
                    <a href="forgot-password.php">← Request New Reset Link</a>
                </div>
            <?php else: ?>
                <?php if ($message): ?>
                    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                    <div class="back-link">
                        <a href="login.php">← Go to Login</a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="token-info">
                        Please enter your new password below. Make sure its strong and secure.
                    </div>

                    <form id="resetForm" method="POST" action="">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill"></div>
                                </div>
                                <div class="strength-text">Password strength: <span id="strengthText">Enter password</span></div>
                            </div>
                            <div class="requirements">
                                <strong>Password Requirements:</strong>
                                <ul id="requirements">
                                    <li id="length">At least 8 characters</li>
                                    <li id="uppercase">One uppercase letter (A-Z)</li>
                                    <li id="lowercase">One lowercase letter (a-z)</li>
                                    <li id="number">One number (0-9)</li>
                                    <li id="special">One special character (!@#$%^&*)</li>
                                </ul>
                            </div>
                            <div class="validation-message" id="passwordMessage"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <div class="validation-message" id="confirmMessage"></div>
                        </div>

                        <button type="submit" class="reset-btn" id="submitBtn" disabled>
                            Update Password
                        </button>
                    </form>

                    <div class="back-link">
                        <a href="login.php">← Back to Login</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const newPasswordInput = document.getElementById('new_password');
                const confirmPasswordInput = document.getElementById('confirm_password');
                const submitBtn = document.getElementById('submitBtn');
                const strengthBar = document.querySelector('.strength-bar');
                const strengthFill = document.querySelector('.strength-fill');
                const strengthText = document.getElementById('strengthText');
                const passwordMessage = document.getElementById('passwordMessage');
                const confirmMessage = document.getElementById('confirmMessage');

                // Password requirements elements
                const requirements = {
                    length: document.getElementById('length'),
                    uppercase: document.getElementById('uppercase'),
                    lowercase: document.getElementById('lowercase'),
                    number: document.getElementById('number'),
                    special: document.getElementById('special')
                };

                function checkPasswordStrength(password) {
                    const checks = {
                        length: password.length >= 8,
                        uppercase: /[A-Z]/.test(password),
                        lowercase: /[a-z]/.test(password),
                        number: /\d/.test(password),
                        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                    };

                    // Update requirement list
                    Object.keys(checks).forEach(key => {
                        if (checks[key]) {
                            requirements[key].classList.add('met');
                        } else {
                            requirements[key].classList.remove('met');
                        }
                    });

                    const metCount = Object.values(checks).filter(Boolean).length;

                    // Update strength bar
                    strengthBar.className = 'strength-bar';
                    if (password.length === 0) {
                        strengthText.textContent = 'Enter password';
                        return 'none';
                    } else if (metCount < 3) {
                        strengthBar.classList.add('strength-weak');
                        strengthText.textContent = 'Weak';
                        return 'weak';
                    } else if (metCount < 5) {
                        strengthBar.classList.add('strength-medium');
                        strengthText.textContent = 'Medium';
                        return 'medium';
                    } else {
                        strengthBar.classList.add('strength-strong');
                        strengthText.textContent = 'Strong';
                        return 'strong';
                    }
                }

                function validateForm() {
                    const newPassword = newPasswordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    let isValid = true;

                    // Validate new password
                    if (newPassword.length === 0) {
                        newPasswordInput.className = '';
                        passwordMessage.textContent = '';
                    } else {
                        const strength = checkPasswordStrength(newPassword);

                        if (newPassword.length < 8) {
                            newPasswordInput.className = 'invalid';
                            passwordMessage.textContent = 'Password must be at least 8 characters long';
                            passwordMessage.className = 'validation-message error';
                            isValid = false;
                        } else if (strength === 'weak') {
                            newPasswordInput.className = 'invalid';
                            passwordMessage.textContent = 'Password is too weak - please meet more requirements';
                            passwordMessage.className = 'validation-message error';
                            isValid = false;
                        } else {
                            newPasswordInput.className = 'valid';
                            passwordMessage.textContent = 'Password strength is good';
                            passwordMessage.className = 'validation-message success';
                        }
                    }

                    // Validate confirm password
                    if (confirmPassword.length === 0) {
                        confirmPasswordInput.className = '';
                        confirmMessage.textContent = '';
                    } else if (newPassword !== confirmPassword) {
                        confirmPasswordInput.className = 'invalid';
                        confirmMessage.textContent = 'Passwords do not match';
                        confirmMessage.className = 'validation-message error';
                        isValid = false;
                    } else if (newPassword.length > 0) {
                        confirmPasswordInput.className = 'valid';
                        confirmMessage.textContent = 'Passwords match';
                        confirmMessage.className = 'validation-message success';
                    }

                    // Enable/disable submit button
                    const hasValidPassword = newPassword.length >= 8 && checkPasswordStrength(newPassword) !== 'weak';
                    const passwordsMatch = newPassword === confirmPassword && confirmPassword.length > 0;

                    submitBtn.disabled = !(hasValidPassword && passwordsMatch);
                }

                // Event listeners
                if (newPasswordInput) {
                    newPasswordInput.addEventListener('input', validateForm);
                    confirmPasswordInput.addEventListener('input', validateForm);

                    // Form submission validation
                    document.getElementById('resetForm').addEventListener('submit', function(e) {
                        const newPassword = newPasswordInput.value;
                        const confirmPassword = confirmPasswordInput.value;

                        if (newPassword !== confirmPassword) {
                            e.preventDefault();
                            alert('Passwords do not match!');
                            return false;
                        }

                        if (newPassword.length < 8) {
                            e.preventDefault();
                            alert('Password must be at least 8 characters long!');
                            return false;
                        }

                        const strength = checkPasswordStrength(newPassword);
                        if (strength === 'weak') {
                            e.preventDefault();
                            alert('Password is too weak. Please meet more security requirements!');
                            return false;
                        }
                    });

                    // Initialize validation
                    validateForm();
                }
            });
        </script>
    </div>
</body>

</html>