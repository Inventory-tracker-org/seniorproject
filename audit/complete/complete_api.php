<?php
header("Content-Type: application/json");
try {
    header("Access-Control-Allow-Headers: Content-Type");
    require_once "../../config.php";

    check_auth();

    $result = json_decode(file_get_contents("php://input"), true);
    $content_type = $_SERVER["CONTENT_TYPE"] ?? '';
    $audit_data = $_SESSION['data'];
    $audit_type = $_SESSION['info'][3];
    $audited_with = $result['audited_with'];
    $index = 0;
    $dept = '';
    while (!preg_match('/^D\d+/', $dept, $matches)) {
        $dept = $_SESSION['data'][$index]["Dept"];
    }
    if (empty($dept)) {
        echo json_encode(['status' => 'failure', "Message" => 'Fail on dept ID grabbing']);
        exit;
    }


    $audit_id = match ($audit_type) {
        'cust' => 1,
        'ocust' => 3,
        'mgmt' => 4,
        'omgmt' => 6,
        'SPA'  => 7,
        'oSPA'  => 9
    };
    foreach ($audit_data as $index=>$tag) {
        if (!in_array($tag['Tag Status'], ['Found', 'Extra'])) {
            continue;
        }
        $insert_q = "INSERT INTO audited_asset (dept_id, audit_id, asset_tag, note, room_tag) VALUES (?, ?, ?, ?, ?) ON CONFLICT (dept_id, audit_id, asset_tag) DO UPDATE SET note = EXCLUDED.note";
        $stmt = $dbh->prepare($insert_q);
        $stmt->execute([$dept,$audit_id, $tag['Tag Number'], $tag['Found Note'], $tag['Room Tag']]);
    }


    $audited_asset_json = json_encode($audit_data);
    $auditor = $_SESSION['email'];
    try {
        $get_curr_audit_q = "SELECT curr_self_id, curr_mgmt_id, curr_spa_id FROM audit_freq";
        $get_id_stmt = $dbh->query($get_curr_audit_q);
        $id_results = $get_id_stmt->fetch(PDO::FETCH_ASSOC);

        $check_recent_audits = "SELECT dept_id, audit_id FROM audit_history WHERE audit_id = :audit_id AND dept_id = :dept_id";
        $check_stmt = $dbh->prepare($check_recent_audits);

        if (isset($_SESSION['info'][5])) {
            $id = (int)$_SESSION['info'][5];
        } else {
            if ($audit_id === 1) {
                $id = $id_results['curr_self_id'];
            } else if ($audit_id === 4) {
                $id = $id_results['curr_mgmt_id'];
            } else if ($audit_id === 7) {
                $id = $id_results['curr_spa_id'];
            } else if ($audit_id === 3 || $audit_id === 6 || $audit_id === 9) {
                $id = $audit_id;
            }
        }

        $check_stmt->execute([":dept_id" => $dept, ":audit_id" => $id]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            try {
                $update_q = "UPDATE audit_history SET audited_with = :with, finished_at = CURRENT_TIMESTAMP, auditor = :auditor, audit_data = :audit_data WHERE audit_id = :audit_id AND dept_id = :dept_id";
                $update_stmt = $dbh->prepare($update_q);
                $update_stmt->execute([":with"=>$audited_with, ":audit_id" => $id, ":dept_id" => $result['dept_id'], ":auditor" => $auditor, ":audit_data" => $audited_asset_json]);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'failure', "Message" => 'Fail on update ' . $e->getMessage()]);
                exit;
            }
            echo json_encode(['status' => 'success', 'Message' => 'Updated audit id ' . $audit_id]);
            exit;
        } else {
            try {
                $insert_q = "INSERT INTO audit_history (dept_id, audit_id, auditor, audit_data, audited_with) VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $dbh->prepare($insert_q);
                $insert_stmt->execute([$dept, $audit_id, $auditor, $audited_asset_json, $audited_with]);


                echo json_encode(['status' => 'success', 'message' => 'Insert audit id ' . $audit_id]);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'failed', 'Error on Insert' => $e->getMessage(),]);
                exit;
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'failed', 'Error on select' => $e->getMessage()]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'failed', 'Error' => $e->getMessage()]);
    exit;
}

