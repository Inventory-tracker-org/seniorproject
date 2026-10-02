<?php 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include_once "../../config.php";
$encoded_data = file_get_contents('php://input');
$data = json_decode($encoded_data, true);
$pw = trim($data['pw']);
$email = trim($data['email']);
$data_array = $data['data'];
$dept = trim($data['dept_name']);
file_put_contents('php//stdout', $encoded_data);
if (empty($email) || empty($pw)) {
    echo json_encode(['status'=>'Failed to login']);
    exit;
}

try {
    $select_user = "SELECT u_role, email, pw FROM user_table WHERE (email = :email OR username = :email) limit 1";
    $stmt = $dbh->prepare($select_user);
    $stmt->execute([":email"=>$email]);
} catch (PDOException $e) {
    $msg = $e->getMessage();
    echo json_encode(['status'=>'Error with database', 'error'=>$msg]);
    exit;
}
$info = $stmt->fetch();
if ($info) {
    if (!password_verify($pw, $info['pw'])) {
        echo json_encode(['status'=>'failed', 'reason'=>'invalid login']);
        exit;
    }
}
try {
    $select = "SELECT dept_id FROM department WHERE dept_name = :dept OR dept_id = :dept";
    $stmt = $dbh->prepare($select);
    $stmt->execute([':dept'=>$dept]);
    $dept_id = $stmt->fetchColumn();
} catch (PDOException $e) {
    $msg = $e->getMessage();
    echo json_encode(['status'=>'Error with database', 'error'=>$msg]);
    exit;
}
if (empty($dept_id)) {
    echo json_encode(['status'=>'Failed','reason'=>'Deptartment ID Not found']);
    exit;
}

try {
    $select = 'SELECT curr_self_id, curr_mgmt_id FROM audit_freq';
    $stmt = $dbh->query($select);
    $audit_ids = $stmt->fetch();
    $audit_id = ($info['u_role'] === 'admin' || $info['u_role'] === 'management') ?  $audit_ids['curr_mgmt_id'] : $audit_ids ['curr_self_ids'];
    $json_data = json_encode($data_array);
} catch (PDOException $e) {
    $msg = $e->getMessage();
    echo json_encode(['status'=>'Error with database', 'error'=>$msg]);
    exit;
}
$formatted_data = [];
foreach ($data_array as $index=>$row) {
    $formatted_data[$index]['Tag Number'] = $row['tag'];
    $formatted_data[$index]['Descr'] = $row['name'];
    $formatted_data[$index]['Dept'] = $row['dept_id'];
    $formatted_data[$index]['Unit'] = $row['bus_unit'];
    $formatted_data[$index]['PO No.'] = $row['po'];
    $formatted_data[$index]['Acq Date'] = $row['purchase_date'];
    //$formatted_data[$index]['Location'] = $row[''];
    $formatted_data[$index]['Serial ID'] = $row['serial'];
    $formatted_data[$index]['Found Note'] = $row['notes'];
    $formatted_data[$index]['Tag Status'] = $row['status'] ?? 'not-found';
    $formatted_data[$index]['Found Room Tag'] = $row['found_room_tag'];
    $formatted_data[$index]['COST Total Cost'] = $row['price'];
    $formatted_data[$index]['Found Timestamp'] = $row['found_timestamp'];
    $formatted_data[$index]['Found Room Number'] = $row['found_room_number'];
    $formatted_data[$index]['Found Building Name'] = $row['found_building'];
    $formatted_data[$index]['geo_x'] = $row['geo_x'];
    $formatted_data[$index]['geo_y'] = $row['geo_y'];
    $formatted_data[$index]['elevation'] = $row['elevation'];
    $formatted_data[$index]['Tag Status'] = $row['found_status'];
    if (in_array($row['found_status'], ['Extra', 'Found'])) {
        $insert = 'INSERT INTO audited_asset (dept_id, audit_id, asset_tag, note, room_tag) VALUES (?, ?, ?, ?, ?) ON CONFLICT (dept_id, audit_id, asset_tag) DO NOTHING';
        $stmt = $dbh->prepare($insert);
        $stmt->execute([$dept_id, $audit_id, $row['tag'], $row['notes'] ?? '', $row['found_room_tag']]);
    }

}
$json_data = json_encode($formatted_data);



try {
    $insert = "INSERT INTO audit_history (auditor, audit_id, audit_data, dept_id, mobile_audit) VALUES (?, ?, ?, ?, ?)";
    $stmt = $dbh->prepare($insert);
    $stmt->execute([$email, $audit_id, $json_data, $dept_id, 1]);
} catch (PDOException $e) { 
    error_log($e->getMessage());
    echo json_encode(['status'=>'Failed', 'reason'=>$e->getMessage()]);
    exit;
}

echo json_encode(['status'=>'Ok']);
exit;
