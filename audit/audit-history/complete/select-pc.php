<?php
require_once "../../../config.php";
check_auth();

$custodians = $_SESSION['info'][7];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedIndex = $_POST['custodian_index'];

    if ($selectedIndex !== '' && isset($custodians[$selectedIndex])) {
        $_SESSION['selected_custodian_index'] = $selectedIndex + 1;
        $_SESSION['selected_custodian'] = $custodians[$selectedIndex];

        //header("Location: https://dataworks-7b7x.onrender.com/audit/audit-history/complete/select-forms.php");
        header("Location: " . url('/audit/audit-history/complete/select-forms.php'));
        exit;
    }
}

include_once("../navbar.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Property Custodian</title>

    <!-- <link rel="icon" href="https://dataworks-7b7x.onrender.com/pictures/home/favicon-32x32.png" sizes="32x32" type="image/x-icon"> -->
    <link rel="icon" href="<?php url('/pictures/home/favicon-32x32.png'); ?>" sizes="32x32" type="image/x-icon">
    <link rel="stylesheet" href="/navbar.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            min-height: 100vh;
        }

        .page-center {
            min-height: calc(100vh - 70px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .custodian-container {
            width: 100%;
            max-width: 650px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e3f2fd;
            box-shadow: 0 10px 40px rgba(33, 150, 243, 0.15);
            animation: slideUp .6s ease-out;
        }

        /* ---------- Header ---------- */

        .custodian-header {
            background: linear-gradient(135deg, #1976d2 0%, #2196f3 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }

        .custodian-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .custodian-header p {
            font-size: 14px;
            opacity: .9;
        }

        /* ---------- Body ---------- */

        .custodian-body {
            padding: 35px 30px;
        }

        .custodian-body h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: #111;
        }

        .custodian-body p {
            margin-bottom: 16px;
            color: #444;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #1976d2;
            font-weight: 600;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            font-size: 16px;
            background: #fafafa;
            transition: .3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #2196f3;
            background: white;
            box-shadow: 0 0 0 4px rgba(33, 150, 243, .10);
        }

        /* ---------- Footer ---------- */

        .custodian-footer {
            background: #f8fcff;
            border-top: 1px solid #e3f2fd;
            text-align: center;
            padding: 20px;
            color: #64b5f6;
            font-size: 14px;
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

        @media (max-width: 600px) {

            .custodian-container {
                margin: 10px;
            }

            .custodian-header h2 {
                font-size: 24px;
            }

            .custodian-body {
                padding: 25px 20px;
            }

        }
    </style>
</head>

<body>


    <main class="page-center">

        <div class="custodian-container">

            <div class="custodian-header">
                <h2>The department chosen has multiple </h2>
                <h2> Property Custodians</h2>
                <p>Select a Property Custodian to continue</p>
            </div>

            <div class="custodian-body">

                <form method="post" action="select-pc.php">
                    <label class="form-label" for="custodian">
                        Select Property Custodian
                    </label>

                    <select id="custodian" name="custodian_index" class="form-input" required>
                        <option value="">Select Property Custodian...</option>

                        <?php foreach ($custodians as $i => $custodian): ?>
                            <option value="<?= $i ?>">
                                <?= htmlspecialchars($custodian) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="continue-button">
                        Continue
                    </button>
                </form>

            </div>

            <div class="custodian-footer">
                CSUB Asset Management
            </div>

        </div>

    </main>

</body>

</html>