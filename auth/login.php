<?php
include_once("../config.php");

$email_err = $pw_err = $err = "";
try {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'] ?? "";
        $pw = $_POST['pw'] ?? "";

        if (!empty($email) && !empty($pw)) {
            $stmt = "SELECT email, pw, id, u_role, dept_id FROM user_table WHERE email = ?";
            $stmt = $dbh->prepare($stmt);
            if (!($stmt->execute([$email]))) {
                $err = "Error getting info" . $stmt->errorInfo()[2];
            } else {
                $user_check = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user_check && password_verify($pw, $user_check['pw'])) {
                    /*
                $_SESSION['id'] = $user_check['id'];
                $_SESSION['role'] = $user_check['u_role'];
                $_SESSION['email'] = $user_check['email'];
                $_SESSION['deptid'] = trim($user_check['dept_id'], '{}');
                 */
                    $stmt = "UPDATE user_table SET last_login = CURRENT_TIMESTAMP WHERE email = ?";
                    $stmt = $dbh->prepare($stmt);

                    $update_audit_id_q = "select ((NOW()::DATE - spa_due)) as spa_check, (NOW()::DATE - self_due) as self_check, (NOW()::DATE - mgmt_due) as mgmt_check , curr_self_id, curr_mgmt_id, curr_spa_id from audit_freq";
                    $audit_id_stmt = $dbh->query($update_audit_id_q);
                    $audit_check = $audit_id_stmt->fetch(PDO::FETCH_ASSOC);
                    $spa_change = $self_change = $mgmt_change = false;
                    $params = $update_c = [];
                    if ($audit_check) {
                        if ((int)$audit_check['spa_check'] >= 0) {
                            $new_spa_id = (int)$audit_check['curr_spa_id'] === 7 ? 8 : 7;
                            $params[':spa_id'] = $new_spa_id;
                            $update_c[] = 'curr_spa_id = :spa_id';

                            $update_history_q = "UPDATE audit_history SET audit_id = 9 WHERE audit_id = :id AND audit_status = 'In Progress'";
                            $dbh->prepare($update_history_q)->execute([":id" => $audit_check['curr_spa_id']]);
                        }
                        if ((int)$audit_check['self_check'] >= 0) {
                            $new_self_id = (int)$audit_check['curr_self_id'] === 1 ? 2 : 1;
                            $params[':self_id'] = $new_self_id;
                            $update_c[] = 'curr_self_id = :self_id';

                            $update_history_q = "UPDATE audit_history SET audit_id = 3 WHERE audit_id = :id AND audit_status = 'In Progress'";
                            $dbh->prepare($update_history_q)->execute([":id" => $audit_check['curr_self_id']]);
                        }
                        if ((int)$audit_check['mgmt_check'] >= 0) {
                            $new_mgmt_id = (int)$audit_check['curr_mgmt_id'] === 4 ? 5 : 4;
                            $params[':mgmt_id'] = $new_mgmt_id;
                            $update_c[] = 'curr_mgmt_id = :mgmt_id';

                            $update_history_q = "UPDATE audit_history SET audit_id = 6 WHERE audit_id = :id AND audit_status = 'In Progress'";
                            $dbh->prepare($update_history_q)->execute([":id" => $audit_check['curr_mgmt_id']]);
                        }
                        if (!empty($update_c)) {
                            $ready_columns = implode(',', $update_c);
                            $update_id = "UPDATE audit_freq SET $ready_columns";
                            $update_stmt = $dbh->prepare($update_id)->execute($params);
                        }
                    }

                    if ($stmt->execute([$user_check['email']])) {
                        header("location: http://localhost:3000" /*"location: https://dataworks-7b7x.onrender.com/auth/2fa.php?id="*/ . urlencode($user_check['id']) . "&role=" . urlencode($user_check['u_role']) . "&email=" . urlencode($user_check['email']) . "&dept_id=" . urlencode($user_check['dept_id']));
                    } else {
                        error_log("Error updating last_login");
                    }
                }
            }
            $stmt = NULL;
        } else {
            $err = "Invalid email or password";
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
    <title>Login - CSUB Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        .has-login {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(33, 150, 243, 0.15);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            border: 1px solid #e3f2fd;
        }

        .login-header {
            background: linear-gradient(135deg, #1976d2 0%, #2196f3 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .login-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .login-body {
            padding: 40px 30px 30px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
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
            padding: 14px 16px;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #fafafa;
            color: #333;
        }

        .form-input:focus {
            outline: none;
            border-color: #2196f3;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
            transform: translateY(-1px);
        }

        .form-input::placeholder {
            color: #90a4ae;
            font-size: 14px;
        }

        .form-input.error {
            border-color: #f44336;
            background-color: #ffebee;
        }

        .error-message {
            color: #f44336;
            font-size: 12px;
            margin-top: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .error-message::before {
            content: '⚠';
            font-size: 14px;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 24px;
        }

        .forgot-password a {
            color: #2196f3;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: #1976d2;
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:hover {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
            background: #bbdefb;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .login-button:disabled::before {
            display: none;
        }

        .server-error {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #f44336;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .server-error::before {
            content: '❌';
            font-size: 16px;
        }

        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f8fcff;
            border-top: 1px solid #e3f2fd;
            font-size: 14px;
            color: #64b5f6;
        }

        /* Loading state */
        .login-button.loading {
            pointer-events: none;
        }

        .login-button.loading::after {
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

        /* Responsive design */
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
                max-width: none;
            }

            .login-body {
                padding: 30px 20px 20px;
            }

            .login-header {
                padding: 25px 20px;
            }

            .login-header h2 {
                font-size: 24px;
            }
        }

        /* Animation for form appearance */
        .login-container {
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

        /* Focus ring for accessibility */
        .form-input:focus-visible,
        .login-button:focus-visible,
        .forgot-password a:focus-visible {
            outline: 2px solid #2196f3;
            outline-offset: 2px;
        }
    </style>
</head>

<body>
    <div class="has-login">
        <div class="login-container">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your CSUB account</p>
            </div>

            <div class="login-body">
                <?php if (!empty($err)): ?>
                    <div class="server-error">
                        <?php echo htmlspecialchars($err); ?>
                    </div>
                <?php endif; ?>

                <form id="login-form" method="post" action="login.php" oninput="validateForm()" onsubmit="return handleSubmit(event)">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input
                            class="form-input"
                            type="email"
                            name="email"
                            id="email"
                            placeholder="example@csub.edu"
                            onblur="validateEmail()"
                            required
                            autocomplete="email">
                        <div id="err_email" class="error-message" style="display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pw">Password</label>
                        <input
                            class="form-input"
                            type="password"
                            name="pw"
                            id="pw"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password">
                    </div>

                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot your password?</a>
                    </div>

                    <button id="btn" class="login-button" type="submit" disabled>
                        Sign In
                    </button>
                </form>
            </div>

            <div class="login-footer">
                <p>Secure CSUB Portal Access</p>
            </div>
        </div>

        <script>
            function validateEmail() {
                const email = document.getElementById("email").value;
                const emailInput = document.getElementById("email");
                const errEmail = document.getElementById("err_email");

                if (email.length === 0) {
                    emailInput.classList.remove('error');
                    errEmail.style.display = 'none';
                    return false;
                }

                const emailCheck = email.slice(-9);
                if (emailCheck !== '@csub.edu') {
                    emailInput.classList.add('error');
                    errEmail.textContent = "Please use your CSUB email address";
                    errEmail.style.display = 'block';
                    return false;
                }

                emailInput.classList.remove('error');
                errEmail.style.display = 'none';
                return true;
            }

            function validateForm() {
                const isEmailValid = validateEmail();
                const password = document.getElementById("pw").value;
                const btn = document.getElementById("btn");

                const isFormValid = isEmailValid && password.length > 0;

                btn.disabled = !isFormValid;

                return isFormValid;
            }

            function handleSubmit(event) {
                const btn = document.getElementById("btn");

                if (!validateForm()) {
                    event.preventDefault();
                    return false;
                }

                // Add loading state
                btn.classList.add('loading');
                btn.textContent = 'Signing In...';

                return true;
            }

            // Initialize form validation on page load
            document.addEventListener('DOMContentLoaded', function() {
                validateForm();

                // Add real-time validation
                document.getElementById('email').addEventListener('input', validateForm);
                document.getElementById('pw').addEventListener('input', validateForm);
            });
        </script>
    </div>
</body>

</html>
