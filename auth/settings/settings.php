<?php
include_once("../../config.php");
check_auth("low");
?>
<?php include_once("../../navbar.php"); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    * {
        margin: 0;

    }

    .is-settings-footer,
    .is-settings-header,
    .is-settings-section {
        display: block;
        justify-content: center;
        align-items: center;
        margin: 0;
        justify-items: center;
    }

    .is-settings-section {
        border-radius: 50px;
        border: 2px solid gray;
    }

    .settings-body {
        height: 100vh;
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        margin: 0;
        justify-items: center;
    }

    .is-settings-header {
        color: #003594;
    }

    .is-settings-footer button {
        background-color: #4285f4;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.2s ease;
        padding: 10px 20px;
        margin: 6px;
    }

    .form-group-pass button {
        margin: -5px 30px;
        padding: 10px 20px;
        background-color: #4285f4;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.2s ease;
    }


    .form-group {
        display: flow;

        padding: 10px 0;
        margin: 15px 0.5vw;
        justify-self: self-end;
    }

    .editable {
        padding: 5px 10px;
        border: 2px solid #e3f2fd;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s ease;
        background-color: #fafafa;
        color: #000;
    }

    .not-editable {
        padding: 5px 10px;
        border: none;
        background-color: rgba(0, 0, 0, 0);
        margin: 0 1vw;
        font-size: 16px;
        overflow-x: hidden;
        max-width: 100%;
    }

    .not-editable:focus {
        border: none;

    }

    .form-group-pass {
        display: flex;
        flex-direction: row;
        justify-content: left;
        padding: 10px 10px;
        margin: 0 1vw;
    }

    .form-group-pass label,
    .form-group label {
        padding: 0 5px;
        font-weight: bold;
        justify-items: left;
        color: #003594;
    }

    #id {
        color: green;
    }

    .help-icon {
        cursor: pointer;
        transition: opacity 0.2s ease;
    }

    .help-icon:hover {
        opacity: 0.7;
    }

    /* Popup container */
    .popup {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    /* The actual popup (appears on top) */
    .popup .popuptext {
        visibility: hidden;
        width: 160px;
        background-color: white;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 8px 0;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -80px;
    }

    /* Popup arrow */
    .popup .popuptext::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #555 transparent transparent transparent;
    }

    /* Toggle this class when clicking on the popup container (hide and show the popup) */
    .popup .show {
        visibility: visible;
        -webkit-animation: fadeIn 1s;
        animation: fadeIn 1s
    }

    /* Add animation (fade in the popup) */
    @-webkit-keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }
</style>
<?php
try {
    $query = "SELECT * FROM user_table WHERE email = :email";
    $email = $_SESSION['email'];
    $user_stmt = $dbh->prepare($query);
    $user_stmt->execute([":email" => $email]);
    $result = $user_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "error: " . $e->getMessage();
}
$email = $result["email"];
$f_name = $result["f_name"];
$l_name = $result["l_name"];
$role = $result["u_role"];
$dept_id = trim($result["dept_id"], "{");
$dept_id = trim($dept_id, "}");

$kuali_key = $result["kuali_key"];
$pw = $result["pw"];
?>

<body>
    <section class="settings-body">
        <header class="is-settings-header">
            <h2>Account Settings</h2>
        </header>
        <section class="is-settings-section">
            <div class="form-group">
                <label for="f-name">First Name</label>
                <input class="editable" type="text" name="f-name" id="f-name" value="<?= $f_name ?>">
            </div>
            <div class="form-group">
                <label for="l-name">Last Name</label>
                <input class="editable" type="text" name="l-name" id="l-name" value="<?= $l_name ?>">
            </div>
            <div class="form-group">
                <label for="Email">Email</label>
                <input class="not-editable" type="text" name="email" id="email" value="<?= $email ?>" readonly>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <input class="not-editable" type="text" name="role" id="role" value="<?= $role ?>" readonly>
            </div>
            <div class="form-group">
                <label for="dept">Department(s)</label>
                <input class="not-editable" type="text" name="dept-id" id="dept-id" value="<?= $dept_id ?>" readonly>
            </div>
            <div class="form-group-pass">
                <label for="pw">Password</label>
                <button name="change-pw" id="change-pw">Change Password</button>
            </div>
            <div class="form-group">
                <label for="k-key">Kuali Key</label>
                <input class="editable" type="text" name="k-key" id="k-key" value="<?= $kuali_key ?>">
                <span onclick="popup();" class="help-icon popup">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.25 9A3.75 3.75 0 0 1 12 5.25a3.75 3.75 0 0 1 0 7.5m0 4.5h.008v.008H12v-.008Z" />
                    </svg>
                    <span class="popuptext" id="myPopup"><a href="https://dataworks-7b7x.onrender.com/faq/kuali-help.php">Click here for help</a></span>
                </span>
            </div>
        </section>
        <footer class="is-settings-footer">
            <button name="save" id="save">Save</button>
        </footer>
        <p class="msg" id="msg"></p>

    </section>
</body>
<script>
    document.getElementById("save").addEventListener("click", () => {
        const f_name = document.getElementById("f-name").value;
        const l_name = document.getElementById("l-name").value;
        const key = document.getElementById("k-key").value;
        const old_f_name = <?= json_encode($result["f_name"]) ?>;
        const old_l_name = <?= json_encode($result["l_name"]) ?>;
        const old_key = <?= json_encode($result["kuali_key"]) ?>;
        url = "https://dataworks-7b7x.onrender.com/auth/settings/update-settings.php";

        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    'f_name': f_name,
                    'l_name': l_name,
                    'key': key,
                    'old_f_name': old_f_name,
                    'old_l_name': old_l_name,
                    'old_key': old_key
                }),
            })
            .then(response => response.json())
            .then(result => {
                console.log(result);
                const display_msg = document.getElementById("msg");
                console.log(display_msg);
                if (result['status'] === 'success') {
                    display_msg.textContent = 'Saved settings';
                } else {
                    display_msg.textContent = 'Nothing to change';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });


    });
    document.getElementById("change-pw").addEventListener("click", () => {
        window.location.href = "https://dataworks-7b7x.onrender.com/auth/forgot-password.php?";
    });
    // When the user clicks on <div>, open the popup
    function popup() {
        var popup = document.getElementById("myPopup");
        popup.classList.toggle("show");
    }
</script>

</html>