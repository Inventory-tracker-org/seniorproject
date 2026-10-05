<?php
require_once "../../config.php";

check_auth();

$auditor = $_GET['auditor'] ?? '';
$dept_id = $_GET['dept_id'] ?? '';
$audit_id = $_GET['audit_id'] ?? '';

$select = "SELECT audit_id, auditor, dept_id FROM audit_history WHERE dept_id = :dept_id AND auditor = :auditor AND audit_id = :audit_id";
$stmt = $dbh->prepare($select);
$stmt->execute([":dept_id" => $dept_id, ":auditor" => $auditor, ":audit_id" => $audit_id]);
if ($stmt->rowCount() === 1) {
    $delete = "DELETE FROM audit_history WHERE dept_id = :dept_id AND auditor = :auditor AND audit_id = :audit_id";
    $stmt = $dbh->prepare($delete);
    $stmt->execute([":dept_id" => $dept_id, ":auditor" => $auditor, ":audit_id" => $audit_id]);

    $delete = 'DELETE FROM audited_asset WHERE dept_id = : dept_id AND audit_id = :audit_id';
    $stmt = $dbh->prepare($delete);
    $stmt->execute([":dept_id" => $dept_id, ":audit_id" => $audit_id]);
}
//header("Location: https://dataworks-7b7x.onrender.com/audit/audit-history/search-history.php");
header("Location: " . url('/audit/audit-history/search-history.php'));
exit;
