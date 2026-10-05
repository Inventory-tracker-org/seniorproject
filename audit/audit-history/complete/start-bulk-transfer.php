<?php
require_once "../../../config.php";
check_auth();
$dept_id = $_GET['dept_id'];
$audit_id = (int)$_GET['audit_id'];
$audit_type = match ($audit_id) {
    1 => 'cust',
    2 => 'cust',
    3 => 'ocust',
    4 => 'mgmt',
    5 => 'mgmt',
    6 => 'omgmt',
    7 => 'SPA',
    8 => 'SPA',
    9 => 'oSPA'
};
try {
    $select_q = "SELECT auditor, audit_data, forms_submitted FROM audit_history WHERE dept_id = :dept_id AND audit_id = :audit_id";
    $select_stmt = $dbh->prepare($select_q);
    $select_stmt->execute([":dept_id" => $dept_id, ":audit_id" => $audit_id]);
    $data = $select_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error getting info: " . $e->getMessage());
    exit;
}
$audit_data = json_decode($data['audit_data'], true);
unset($_SESSION['data']);
$index = 0;
foreach ($audit_data as $row) {

    if (!empty($row['Tag Number'])) {
        $_SESSION['data'][$index]['Unit'] = $row['Unit'] ?? '';
        $_SESSION['data'][$index]['Tag Number'] = $row['Tag Number'];
        $_SESSION['data'][$index]['Descr'] = $row['Descr'] ?? '';
        $_SESSION['data'][$index]['Serial ID'] = $row['Serial ID'] ?? '';
        $_SESSION['data'][$index]['Location'] = $row['Location'] ?? '';
        $_SESSION['data'][$index]['VIN'] = $row['VIN'] ?? '';
        $_SESSION['data'][$index]['Custodian'] = $row['Custodian'] ?? '';
        $_SESSION['data'][$index]['Dept'] = $row['Dept'] ?? '';
        $_SESSION['data'][$index]['PO No.'] = $row['PO No.'] ?? '';
        $_SESSION['data'][$index]['Acq Date'] = $row['Acq Date'] ?? '';
        $_SESSION['data'][$index]['COST Total Cost'] = $row['COST Total Cost'] ?? '';
        $_SESSION['data'][$index]['Tag Status'] = $row['Tag Status'] ?? '';
        $_SESSION['data'][$index]['Found Room Tag'] = $row['Found Room Tag'] ?? '';
        $_SESSION['data'][$index]['Found Room Number'] = $row['Found Room Number'] ?? '';
        $_SESSION['data'][$index]['Found Building Name'] = $row['Found Building Name'] ?? '';
        $_SESSION['data'][$index]['Found Note'] = $row['Found Note'] ?? '';
        $_SESSION['data'][$index++]['Found Timestamp'] = $row['Found Timestamp'] ?? '';
    }
}
unset($_SESSION['info']);
$_SESSION['info'] = [$index, 1, $dept_id, $audit_type, $dept_id, $audit_id, $data['forms_submitted']];

$select_pc = "SELECT cardinality(custodian) FROM department WHERE dept_id = :id";
$stmt = $dbh->prepare($select_pc);
$stmt->execute([":id" => $dept_id]);
$number_pc = $stmt->fetchColumn();

$custodians = [];

if ($number_pc > 1) {
    for ($i = 1; $i <= $number_pc; $i++) {
        $select_pc = "SELECT custodian[:i] FROM department WHERE dept_id = :id";
        $stmt = $dbh->prepare($select_pc);
        $stmt->execute([":id" => $dept_id, ":i" => $i]);
        $custodians[] = $stmt->fetchColumn();
    }

    $_SESSION['info'] = [$index, 1, $dept_id, $audit_type, $dept_id, $audit_id, $data['forms_submitted'], $custodians];

    //header("Location: https://dataworks-7b7x.onrender.com/audit/audit-history/complete/select-pc.php");
    header("Location: " . url('/audit/audit-history/complete/select-pc.php'));
    exit;
} else if ($number_pc === 1) {
    $_SESSION['selected_custodian_index'] = 1;
    $_SESSION['selected_custodian'] = $custodians[1];

    $_SESSION['info'] = [$index, 1, $dept_id, $audit_type, $dept_id, $audit_id, $data['forms_submitted'], $custodians];

    //header("Location: https://dataworks-7b7x.onrender.com/audit/audit-history/complete/select-forms.php");
    header("Location: " . url('/audit/audit-history/complete/select-forms.php'));
    exit;
} else if ($number_pc <= 0) {
    echo '<br> Error no custodian labeled for ' . $dept_id;
}
exit;
