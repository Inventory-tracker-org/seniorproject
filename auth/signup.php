<?php
require_once("../config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: https://dataworks-7b7x.onrender.com/index.php");
    exit();
}
try {

    $err = 0;
    $user_err = $email_err = $f_name_err = $l_name_err = '';
    $pw_err = $cpw_err = $dept_err = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $status_type = $_POST['status_type'];
        $user_err = (($username = $_POST['username'] ?? '') != '') ? "": "Empty Username field";
        $email_err = (($email = $_POST['email'] ?? '') != '') ? "" : "Empty Email Field";
        $f_name = $_POST['f_name'] ?? '';
        $l_name = $_POST['l_name'] ?? '';
        $password = $_POST['pw'] ?? '';
        $con_password = $_POST['cpw'] ?? '';
        $deptid = $_POST['deptid'] ?? '';
        $dept_id_array = array_values(array_filter(explode(',', $deptid), fn($v) => trim($v) !== ''));
        $role = $_POST['role'] ?? ''; 
        $role = strtolower($role);

        if (!empty($username)) {
            $stmt = "SELECT * FROM user_table WHERE username = ? OR email = ?;";
            $stmt = $dbh->prepare($stmt);
            if (!($stmt->execute([$username, $email]))) {
                echo "<p> Error with database </p>";
                $err = 1;
            } else {
                $user_check = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user_check > 0) {
                    if ($user_check['username'] === $username) {
                        $user_err = "Username or Email already exists";
                        $err = 1;
                    } 
                    if ($user_check['email'] === $email) {
                        $user_err = "Username or Email already exists";
                        $err = 1;
                    }
                }
            }
        }

        if (!empty($con_password)) {
            if ($con_password === $password) {
                $password = password_hash($password, PASSWORD_DEFAULT);
            } else {
                $cpw_err = "Passwords must match.";
                $err = 1;
            }
        }

        if (!empty($deptid)) {
            $placeholder = implode(',', array_fill(0, count($dept_id_array), '?'));
            $stmt = "SELECT distinct dept_id FROM department where dept_id IN ($placeholder)";
            $stmt = $dbh->prepare($stmt);
            $stmt->execute($dept_id_array);
            if ($stmt->fetch(PDO::FETCH_ASSOC) === false) {
                $dept_err = "Department does not exist";
                $err = 1;
            }
        }

        if (!$err) {
            $stmt = "INSERT INTO user_table (username, pw, email, u_role, f_name, l_name, dept_id, position) 
                VALUES (:username, :pw, :email, :u_role, :f_name, :l_name, :dept::VARCHAR[], :status_type);";
            $stmt = $dbh->prepare($stmt);
            $full_name = $f_name . " " . $l_name;
            $dept_id_array = array_map('trim', $dept_id_array);
            $dept_pg_array = '{' . implode(',', array_map(function($val) {
                return '"' . addslashes($val) . '"';
            }, $dept_id_array)) . '}';

            try {
                if ($stmt->execute([':username'=>$username, ':pw'=>$password, 
                    ':email'=>$email, ':u_role'=>$role, ':f_name'=>$f_name, ':l_name'=>$l_name,
                    ':dept'=>$dept_pg_array, ":status_type"=>$status_type])) {
                    if ($role === 'custodian') {
                        $dept_cust = "UPDATE department SET custodian = ARRAY_APPEND(custodian, ?) WHERE dept_id IN ($placeholder)";
                        $dept_stmt = $dbh->prepare($dept_cust);
                        $dept_stmt->execute([$full_name, $dept_id_array]);
                    }
                    header("Location: https://dataworks-7b7x.onrender.com/index.php");
                    exit;
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
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
    <title>Create Account - CSUB Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .has-signup {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px 0 1px;
        }

        .signup-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(33, 150, 243, 0.15);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            border: 1px solid #e3f2fd;
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

        .signup-header {
            background: linear-gradient(135deg, #1976d2 0%, #2196f3 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
            position: relative;
        }

        .signup-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .signup-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .signup-header p {
            font-size: 14px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .signup-body {
            padding: 40px 30px 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #1976d2;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #fafafa;
            color: #333;
        }

        .form-input:focus, .form-select:focus {
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

        .form-input.error, .form-select.error {
            border-color: #f44336;
            background-color: #ffebee;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-input.valid, .form-select.valid {
            border-color: #4caf50;
            background-color: #f1f8e9;
        }

        .form-select {
            cursor: pointer;
            appearance: none;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%23666" d="M6 8L2 4h8z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }

        .error-message {
            color: #f44336;
            font-size: 12px;
            margin-top: 6px;
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

        .password-requirements {
            background: #f8fcff;
            border: 1px solid #e3f2fd;
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
            font-size: 12px;
            color: #546e7a;
        }

        .password-requirements ul {
            margin: 8px 0 0 16px;
        }

        .password-requirements li {
            margin: 4px 0;
        }

        .signup-button {
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
            margin-bottom: 20px;
        }

        .signup-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .signup-button:hover:not(:disabled)::before {
            left: 100%;
        }

        .signup-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }

        .signup-button:disabled {
            background: #bbdefb;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            color: #90a4ae;
        }

        .signup-button:disabled::before {
            display: none;
        }

        .signup-footer {
            text-align: center;
            padding: 20px;
            background: #f8fcff;
            border-top: 1px solid #e3f2fd;
            font-size: 14px;
            color: #64b5f6;
        }

        .signup-footer a {
            color: #2196f3;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-footer a:hover {
            text-decoration: underline;
        }

        .signup-footer p {
            margin: 8px 0;
        }

        /* Loading state */
        .signup-button.loading::after {
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .signup-container {
                margin: 10px;
                max-width: none;
            }
            
            .signup-body {
                padding: 30px 20px 20px;
                max-height: none;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .signup-header {
                padding: 25px 20px;
            }
            
            .signup-header h2 {
                font-size: 24px;
            }
        }

        /* Focus ring for accessibility */
        .form-input:focus-visible,
        .form-select:focus-visible,
        .signup-button:focus-visible {
            outline: 2px solid #2196f3;
            outline-offset: 2px;
        }

        /* Custom scrollbar for form body */
        .signup-body::-webkit-scrollbar {
            width: 6px;
        }

        .signup-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .signup-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .signup-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
<div class="has-signup">
    <div class="signup-container">
        <div class="signup-header">
            <h2>Create Account</h2>
            <p>Please fill in the form to create your CSUB account</p>
        </div>

        <div class="signup-body">
            <?php if (!empty($user_err) || !empty($dept_err)): ?>
                <div class="server-error">
                    <?php echo htmlspecialchars($user_err ?: $dept_err); ?>
                </div>
            <?php endif; ?>

            <form id="signup-form" method="post" action="signup.php" oninput="validateForm()" onsubmit="return handleSubmit(event)">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input 
                        class="form-input" 
                        type="text" 
                        name="username" 
                        id="username" 
                        placeholder="Choose a username (4-16 characters)"
                        onblur="validateUsername()" 
                        required
                        autocomplete="username"
                    >
                    <div id="err_user" class="error-message"></div>
                </div>

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
                        autocomplete="email"
                    >
                    <div id="err_email" class="error-message"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="f_name">First Name</label>
                        <input 
                            class="form-input" 
                            type="text" 
                            name="f_name" 
                            id="f_name" 
                            placeholder="First name"
                            required
                            autocomplete="given-name"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="l_name">Last Name</label>
                        <input 
                            class="form-input" 
                            type="text" 
                            name="l_name" 
                            id="l_name" 
                            placeholder="Last name"
                            required
                            autocomplete="family-name"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pw">Password</label>
                    <input 
                        class="form-input" 
                        type="password" 
                        name="pw" 
                        id="pw" 
                        placeholder="Create a strong password"
                        onblur="validatePassword()" 
                        required
                        autocomplete="new-password"
                    >
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>One uppercase letter (A-Z)</li>
                            <li>One lowercase letter (a-z)</li>
                            <li>One number (0-9)</li>
                            <li>One special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                    <div id="err_pw" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="cpw">Confirm Password</label>
                    <input 
                        class="form-input" 
                        type="password" 
                        name="cpw" 
                        id="cpw" 
                        placeholder="Confirm your password"
                        onblur="validatePassword()" 
                        required
                        autocomplete="new-password"
                    >
                    <div id="err_msg" class="error-message"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="deptid">Department ID</label>
                        <input 
                            class="form-input" 
                            type="text" 
                            name="deptid" 
                            id="deptid" 
                            placeholder="Ex: D20106,D21560"
                            onblur="validateDept()" 
                            required
                        >
                        <div id="dept_err" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">User Role</label>
                        <select class="form-select" name="role" id="role" required>
                            <option value="student">Student</option>
                            <option value="user">User</option>
                            <option value="custodian">Custodian</option>
                            <option value="management">Management</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status_type">Position</label>
                        <select class="form-select" name="status_type" id="status_type" required>
                            <option value="Staff">Staff</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Student">Student</option>
                        </select>
                    </div>
                </div>

                <button id="btn" class="signup-button" type="submit" disabled>
                    Create Account
                </button>
            </form>
        </div>

        <div class="signup-footer">
            <p>By signing up, you agree to our Terms of Service and Privacy Policy</p>
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

        function validateDept() {
            const dept = document.getElementById("deptid").value;
            const deptInput = document.getElementById("deptid");
            const errDept = document.getElementById("dept_err");
            //const deptRegex = /^[D]\d{5}$/;
            
            if (dept === "") {
                deptInput.classList.remove('error', 'valid');
                errDept.textContent = "";
                return false;
            }
            /*
            if (!deptRegex.test(dept.trim())) {
                deptInput.classList.add('error');
                deptInput.classList.remove('valid');
                errDept.textContent = "Department must start with D and have 5 numbers";
                return false;
            }
             */
            deptInput.classList.remove('error');
            deptInput.classList.add('valid');
            errDept.textContent = "";
            return true;
        }

        function validatePassword() {
            const pw = document.getElementById("pw").value;
            const cpw = document.getElementById("cpw").value;
            const pwInput = document.getElementById("pw");
            const cpwInput = document.getElementById("cpw");
            const errorMessage = document.getElementById("err_msg");
            const errPw = document.getElementById("err_pw");
            const passwordRegex = /^(?=.*?[0-9])(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[^0-9A-Za-z]).{8,32}$/;
            
            let isValid = true;
            
            if (pw === "" && cpw === "") {
                pwInput.classList.remove('error', 'valid');
                cpwInput.classList.remove('error', 'valid');
                errorMessage.textContent = "";
                errPw.textContent = "";
                return false;
            }
            
            if (pw !== "" && !passwordRegex.test(pw)) {
                pwInput.classList.add('error');
                pwInput.classList.remove('valid');
                errPw.textContent = "Password must meet all requirements above";
                isValid = false;
            } else if (pw !== "") {
                pwInput.classList.remove('error');
                pwInput.classList.add('valid');
                errPw.textContent = "";
            }
            
            if (cpw !== "" && pw !== cpw) {
                cpwInput.classList.add('error');
                cpwInput.classList.remove('valid');
                errorMessage.textContent = "Passwords do not match";
                isValid = false;
            } else if (cpw !== "" && pw === cpw && pw !== "") {
                cpwInput.classList.remove('error');
                cpwInput.classList.add('valid');
                errorMessage.textContent = "";
            }
            
            return isValid && pw !== "" && cpw !== "";
        }

        function validateUsername() {
            const username = document.getElementById("username").value;
            const usernameInput = document.getElementById("username");
            const errUser = document.getElementById("err_user");
            const userRegex = /^[0-9A-Za-z]{4,16}$/;
            
            if (username === "") {
                usernameInput.classList.remove('error', 'valid');
                errUser.textContent = "";
                return false;
            }
            
            if (!userRegex.test(username)) {
                usernameInput.classList.add('error');
                usernameInput.classList.remove('valid');
                errUser.textContent = "Username must be 4-16 characters (letters and numbers only)";
                return false;
            }
            
            usernameInput.classList.remove('error');
            usernameInput.classList.add('valid');
            errUser.textContent = "";
            return true;
        }

        function validateForm() {
            const isDeptValid = validateDept();
            const isEmailValid = validateEmail();
            const isPasswordValid = validatePassword();
            const isUsernameValid = validateUsername();
            const firstName = document.getElementById("f_name").value.trim();
            const lastName = document.getElementById("l_name").value.trim();
            
            const btn = document.getElementById("btn");
            const isFormValid = isDeptValid && isEmailValid && isPasswordValid && isUsernameValid && firstName !== "" && lastName !== "";
            
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
            btn.textContent = 'Creating Account...';
            
            return true;
        }

        // Initialize form validation on page load
        document.addEventListener('DOMContentLoaded', function() {
            validateForm();
            
            // Add real-time validation
            const inputs = ['username', 'email', 'pw', 'cpw', 'deptid', 'f_name', 'l_name'];
            inputs.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('input', validateForm);
                }
            });
        });
    </script>
</div>
</body>
</html>
