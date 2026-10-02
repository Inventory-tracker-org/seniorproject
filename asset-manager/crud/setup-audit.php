<?php
require_once "../../config.php";

if (isset($_GET['profile_name'])) {
    $profile = trim($_GET['profile_name'], "'");
    $email = $_SESSION['email'];

    $select_q = "SELECT p.asset_tag, a.asset_name, a.bus_unit,
        a.room_tag, r.room_loc, b.bldg_name, a.dept_id, a.po, a.asset_notes,
        d.custodian, a.date_added, a.asset_price, a.serial_num, b.bldg_id
        FROM user_asset_profile p JOIN asset_info a ON p.asset_tag = a.asset_tag
        LEFT JOIN room_table r ON a.room_tag = r.room_tag
        LEFT JOIN bldg_table b ON r.bldg_id = b.bldg_id
        LEFT JOIN department d ON a.dept_id = d.dept_id
        WHERE p.profile_name = :profile_name AND p.email = :email ORDER BY p.asset_tag";
    $select_stmt = $dbh->prepare($select_q);
    $select_stmt->execute([":profile_name" => $profile, ":email" => $email]);
    $result = $select_stmt->fetchAll(PDO::FETCH_ASSOC);
    unset($_SESSION['data']);
    unset($_SESSION['info']);
    $highest_row = 1;
    $info = '';
    if (!empty($result)) {
        foreach ($result as $index => $row) {
            if (!empty($row['asset_notes'])) {
                $info = explode(',', $row['asset_notes']);
            }
            $_SESSION['data'][$index]['Unit'] = $row['bus_unit'];
            $_SESSION['data'][$index]['Tag Number'] = $row['asset_tag'];
            $_SESSION['data'][$index]['Descr'] = $row['asset_name'];
            $_SESSION['data'][$index]['Serial ID'] = $row['serial_num'];
            $_SESSION['data'][$index]['Location'] = $row['bldg_id'] . '-' . $row['room_loc'];
            $_SESSION['data'][$index]['VIN'] = '';
            $_SESSION['data'][$index]['Custodian'] = $row['custodian'] ?? '';
            $_SESSION['data'][$index]['Dept'] = $row['dept_id'];
            $_SESSION['data'][$index]['PO No.'] = $row['po'];
            $_SESSION['data'][$index]['Acq Date'] = $row['date_added'];
            $_SESSION['data'][$index]['COST Total Cost'] = $row['asset_price'];
            if (!empty($info)) {
                $_SESSION['data'][$index]['Tag Status'] = 'Found';
                $_SESSION['data'][$index]['Found Room Tag'] = $info[0];
                $_SESSION['data'][$index]['Found Note'] = $info[1];
                $_SESSION['data'][$index]['Found Timestamp'] = '';
            } else {
                $_SESSION['data'][$index]['Tag Status'] = '';
                $_SESSION['data'][$index]['Found Room Tag'] = '';
                $_SESSION['data'][$index]['Found Note'] = '';
                $_SESSION['data'][$index]['Found Timestamp'] = '';
            }
            $info = '';
            $highest_row++;
        }
    }
    $_SESSION['info'] = [$highest_row, NULL, $profile, 'cust', $profile];
    //header("Location: https://dataworks-7b7x.onrender.com/audit/auditing.php");
    header("Location: " . url('/audit/auditing.php'));
    exit;
}
