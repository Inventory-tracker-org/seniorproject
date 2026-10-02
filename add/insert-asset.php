<?php
require_once("../config.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['add'])) {
    $tag = $_POST['tag'] ?? '';
    $descr = $_POST['descr'] ?? '';
    $sn = $_POST['sn'] ?? '';
    $po = (int)$_POST['po'] ?? '';
    $model = $_POST['model'] ?? '';
    $acq_date = str_replace("-", "/", $_POST['acq']) ?? '';
    $profile = $_POST['profile'] ?? '';
    $dept_id = $_POST['dept-id'] ?? '';
    $room_name = $_POST['room-loc'] ?? '';
    $bldg_name = $_POST['bldg-name'] ?? '';
    $type = $_POST['type'] ?? '';
    $type2 = $_POST['type2'] ?? '';
    $make = $_POST['make'] ?? '';

    $column = $params = [];
    $question_marks = ['?', '?', '?', '?', '?', '?'];
    $column[] = "asset_tag";
    $column[] = "asset_name";
    $column[] = "date_added";
    $column[] = "dept_id";
    $column[] = "po";
    $params[] = $tag;
    $params[] = $descr;
    $params[] = $acq_date;
    $params[] = $dept_id;
    $params[] = $po;

    if ($model !== '') {
        $column[] = "asset_model";
        $params[] = $model;
        $question_marks[] = '?';
    }
    if ($make !== '') {
        $column[] = "make";
        $params[] = $make;
        $question_marks[] = '?';
    }
    if ($sn !== '') {
        $column[] = "serial_num";
        $params[] = $sn;
        $question_marks[] = '?';
    }
    if ($type !== '') {
        $column[] = "asset_type";
        $params[] = $type;
        $question_marks[] = '?';
    }
    if ($type2 !== '') {
        $column[] = "type2";
        $params[] = $type2;
        $question_marks[] = '?';
    }
    try {
        $bldg_search[] = "bldg_name";
        $bldg_search[] = "room_loc";
        $bldg_q = "SELECT room_tag FROM room_table r NATURAL JOIN bldg_table b WHERE r.room_loc = :room_loc AND b.bldg_name = :bldg_name";
        $b_stmt = $dbh->prepare($bldg_q);
        $b_stmt->execute([":room_loc" => $room_name, ":bldg_name" => $bldg_name]);
        $room_tag = $b_stmt->fetch(PDO::FETCH_ASSOC);
        if ($room_tag) {
            $column[] = "room_tag";
            $params[] = $room_tag["room_tag"];
        } else {
            exit;
        }
        $tag_avail = "SELECT * FROM asset_info WHERE asset_tag = :tag";
        $tag_stmt = $dbh->prepare($tag_avail);
        $tag_stmt->execute([":tag" => $tag]);
        $is_avail = $tag_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$is_avail) {
            $column = implode(", ", $column);
            $question_marks = implode(", ", $question_marks);
            $insert_q = "INSERT INTO asset_info ($column) VALUES ($question_marks)";
            $insert_stmt = $dbh->prepare($insert_q);
            $insert_stmt->execute($params);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        exit;
    }
} else {
    exit;
}
//header('location: https://dataworks-7b7x.onrender.com/add/add-asset.php');
header('Location: ' . url('/add/add-asset.php'));
exit;
