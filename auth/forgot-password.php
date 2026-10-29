<?php
require_once "../config.php";
require '../vendor/autoload.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $bytes = random_bytes(3);
    $token = bin2hex($bytes);

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'phpangele@gmail.com';
        $mail->Password   = $_SESSION['app_pass_email'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; //'tls';
        $mail->Port       = 587;
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('phpangele@gmail.com', 'Dataworks No Reply');
        $mail->addAddress($email, 'User');
        $mail->Subject = 'Password Reset';
        $mail->Body    = '<h4>Click this link to reset your password...</h4><br>
        <a href="https://dataworks-7b7x.onrender.com/auth/reset-password.php?token=' . $token . '&email=' . $email . '">Reset Password</a>';
        $mail->AltBody = 'Click this link to reset your password...';

        $mail->send();

        $add_token_q = "UPDATE user_table SET ver_code = :token, expiry = CURRENT_TIMESTAMP WHERE email = :email";
        $token_stmt = $dbh->prepare($add_token_q);
        $token_stmt->execute([':token' => $token, ':email' => $email]);

        $message = "If an account with that email exists, you will receive a password reset link shortly.";
        $success = true;
    } catch (PDOException $e) {
        $message = "Invalid Email Address: " . $email;
        $success = false;
    } catch (Exception $e) {
        $message = $e->getMessage();
        $success = false;
    }
}
include_once("../navbar.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CSUB Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .is-forgot {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        .is-forgot::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(33, 150, 243, 0.05) 0%, transparent 50%);
            animation: float 15s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(20px, -20px) rotate(120deg);
            }

            66% {
                transform: translate(-15px, 15px) rotate(240deg);
            }
        }

        .forgot-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(33, 150, 243, 0.15);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .forgot-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2196f3, #21cbf3, #2196f3);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {

            0%,
            100% {
                background-position: -200% 0;
            }

            50% {
                background-position: 200% 0;
            }
        }

        .forgot-header {
            background: linear-gradient(135deg, #1976d2 0%, #2196f3 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
            position: relative;
        }

        .forgot-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .forgot-header .icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
            position: relative;
            z-index: 1;
        }

        .forgot-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .forgot-header p {
            font-size: 14px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            line-height: 1.5;
        }

        .forgot-body {
            padding: 40px 30px 30px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #1976d2;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e3f2fd;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(145deg, #fafafa, #ffffff);
            color: #333;
        }

        .form-input:focus {
            outline: none;
            border-color: #2196f3;
            background: #ffffff;
            box-shadow:
                0 0 0 4px rgba(33, 150, 243, 0.1),
                0 4px 12px rgba(33, 150, 243, 0.15);
            transform: translateY(-1px);
        }

        .form-input::placeholder {
            color: #90a4ae;
            font-size: 14px;
        }

        .form-input.error {
            border-color: #f44336;
            background: linear-gradient(145deg, #ffebee, #ffffff);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .form-input.valid {
            border-color: #4caf50;
            background: linear-gradient(145deg, #f1f8e9, #ffffff);
        }

        .error-message {
            color: #f44336;
            font-size: 12px;
            margin-top: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
            min-height: 18px;
        }

        .error-message:not(:empty)::before {
            content: '⚠';
            font-size: 14px;
        }

        .reset-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }

        .reset-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .reset-button:hover:not(:disabled)::before {
            left: 100%;
        }

        .reset-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.4);
        }

        .reset-button:disabled {
            background: linear-gradient(135deg, #bbdefb, #e3f2fd);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            color: #90a4ae;
        }

        .reset-button:disabled::before {
            display: none;
        }

        .reset-button.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .back-link {
            text-align: center;
        }

        .back-link a {
            color: #2196f3;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-block;
        }

        .back-link a:hover {
            color: #1976d2;
            background: rgba(33, 150, 243, 0.05);
            transform: translateY(-1px);
        }

        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 15px;
            text-align: center;
            font-weight: 500;
            position: relative;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background: linear-gradient(145deg, #e8f5e8, #f1f8e9);
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.1);
        }

        .message.success::before {
            content: '✅';
            margin-right: 8px;
            font-size: 16px;
        }

        .message.error {
            background: linear-gradient(145deg, #ffebee, #fce4ec);
            color: #c62828;
            border: 1px solid #ffcdd2;
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.1);
        }

        .message.error::before {
            content: '❌';
            margin-right: 8px;
            font-size: 16px;
        }

        .info-box {
            background: linear-gradient(145deg, #f8fcff, #ffffff);
            border: 1px solid #e3f2fd;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #546e7a;
            line-height: 1.5;
        }

        .info-box::before {
            content: 'ℹ️';
            margin-right: 8px;
            font-size: 16px;
        }

        @media (max-width: 480px) {
            .forgot-container {
                margin: 10px;
                max-width: none;
            }

            .forgot-body {
                padding: 30px 20px 20px;
            }

            .forgot-header {
                padding: 30px 20px;
            }

            .forgot-header h2 {
                font-size: 24px;
            }

            .forgot-header .icon {
                font-size: 40px;
                margin-bottom: 12px;
            }
        }

        /* Focus ring for accessibility */
        .form-input:focus-visible,
        .reset-button:focus-visible,
        .back-link a:focus-visible {
            outline: 2px solid #2196f3;
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>
    <div class="is-forgot">
        <div class="forgot-container">
            <div class="forgot-header">
                <span class="icon">🔐</span>
                <h2>Reset Password</h2>
                <p>Enter your email address and we'll send you a link to reset your password</p>
            </div>

            <div class="forgot-body">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <div class="info-box">
                        <strong>Security Notice:</strong> For your protection, we'll only send reset links to registered CSUB email addresses.
                    </div>

                    <form id="forgot-form" method="post" action="forgot-password.php" onsubmit="return handleSubmit(event)">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input
                                class="form-input"
                                type="email"
                                name="email"
                                id="email"
                                placeholder="example@csub.edu"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                                autocomplete="email">
                            <div id="err_email" class="error-message"></div>
                        </div>

                        <button id="btn" class="reset-button" type="submit">
                            Send Reset Link
                        </button>
                    </form>
                <?php endif; ?>

                <div class="back-link">
                    <a href="login.php">← Back to Login</a>
                </div>
            </div>
        </div>

        <script>
            function validateEmail() {
                const email = document.getElementById("email").value;
                const emailInput = document.getElementById("email");
                const errEmail = document.getElementById("err_email");

                if (email.length === 0) {
                    emailInput.classList.remove('error', 'valid');
                    errEmail.textContent = "";
                    return false;
                }

                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    emailInput.classList.add('error');
                    emailInput.classList.remove('valid');
                    errEmail.textContent = "Please enter a valid email address";
                    return false;
                }

                // CSUB email validation
                const emailCheck = email.slice(-9);
                if (emailCheck !== '@csub.edu') {
                    emailInput.classList.add('error');
                    emailInput.classList.remove('valid');
                    errEmail.textContent = "Please use your CSUB email address";
                    return false;
                }

                emailInput.classList.remove('error');
                emailInput.classList.add('valid');
                errEmail.textContent = "";
                return true;
            }

            function handleSubmit(event) {
                const btn = document.getElementById("btn");

                if (!validateEmail()) {
                    event.preventDefault();
                    return false;
                }

                // Add loading state
                btn.classList.add('loading');
                btn.textContent = 'Sending...';
                btn.disabled = true;

                return true;
            }

            // Initialize form validation
            document.addEventListener('DOMContentLoaded', function() {
                const emailInput = document.getElementById('email');

                if (emailInput) {
                    emailInput.addEventListener('input', validateEmail);
                    emailInput.addEventListener('blur', validateEmail);

                    // Auto-focus email field
                    emailInput.focus();
                }
            });
        </script>
    </div>
</body>

</html>