<?php
require_once("../config.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_reporting(0);
//-------------------------------------------------------------------------
//  DYNAMIC SQL QUERIES
$query_start = "SELECT ";
$query_asset_from = " FROM asset_info AS a ";
$query_end = " LIMIT 50 OFFSET :offset";
//-------------------------------------------------------------------------
if (isset($_POST['audit'])) {

    $search = strtoupper(htmlspecialchars($_POST['search'] ?? '', ENT_QUOTES));
    $audit_id = $_POST['statusFilter'] ?? 6;
    $dept_id = $_POST['dept_id'];
    $dept_id_search = strtoupper($_POST['dept_id_search']);
    $room_tag = $_POST['room_tag'];
    $room_loc = $_POST['room_loc'];
    $asset_sn = $_POST['asset_sn'];
    $asset_price = ($_POST['asset_price'] !== '') ? (int)$_POST['asset_price'] : -1;
    $asset_price_check = $_POST['asset_price_check'];
    $asset_price_operation = $_POST['price_operation'];
    $asset_po = $_POST['asset_po'];
    $notes = $_POST['notes'] ?? 'false';
    $bldg_id = $_POST['bldg_id'];
    $bldg_id_val = $_POST['bldg_id_search'] ?? NULL;
    $bldg_name = $_POST['bldg_name'];
    $box_name = $_POST['box_name'];


    $where_dept = $where_price = $where_status = '';
    $location_from = '';

    $where = $and = $and1 = $and2 = '';
    $where_dept = $where_price = '';
    $params = [];
    $where_array = [];
    $count = 0;
    if (isset($_POST['dept_id_search']) && $_POST['dept_id_search'] !== '') {
        $where_dept = " a.dept_id = :dept_id ";
        $params[':dept_id'] = $dept_id_search;
        $and1 = ' AND ';
        $count++;
    }
    if (isset($_POST['asset_price']) && $_POST['asset_price'] !== '') {
        $params[':price'] = $asset_price;
        $where_price = " a.asset_price " . $asset_price_operation . " :price ";
        $and2 = ' AND ';
        $count++;
    }
    $where = ($count > 0) ? ' WHERE ' : '';
    if ($where_dept && $where_price) {
        $and = ' AND ';
    }

    if ($search === 'ALL') {
        //New query searches both audited_asset and asset_info tables for all assets, including those that have been audited and those that have not.
        $audit_query = "SELECT a.asset_tag, a.serial_num, a.po, a.bus_unit, 
        a.asset_name, a.asset_price, a. dept_id, a.room_tag, a.asset_status, 
        a.asset_notes, aa.dept_id AS found_dept, aa.note AS found_note, aa.room_tag 
        AS found_room_tag, b.bldg_name, r.room_loc,
        CASE 
            WHEN aa.asset_tag IS NOT NULL THEN 1 ELSE 0 
        END AS is_found 
        FROM asset_info a 
            LEFT JOIN audited_asset aa 
                ON aa.asset_tag = a.asset_tag 
                AND aa.audit_id = " . $audit_id .
            " JOIN room_table AS r ON a.room_tag = r.room_tag                                                   
             JOIN bldg_table AS b ON r.bldg_id = b.bldg_id";
        /*
        $audit_query = "SELECT a.asset_tag, a.serial_num, a.po,
            a.asset_name, a.asset_price, a.room_tag, a.dept_id, b.bldg_name, r.room_loc FROM asset_info AS a      
             JOIN room_table AS r ON a.room_tag = r.room_tag                                                   
             JOIN bldg_table AS b ON r.bldg_id = b.bldg_id " . $where . $where_price . $and . $where_dept;
             */
    } else {
        $params[':search'] = "%$search%";
        $where_array[] = "a.asset_tag ILIKE :search";

        if ($room_tag === 'true') {
            $where_array[] = "CAST(a.room_tag AS TEXT) ILIKE :search";
        }
        if ($box_name === 'true') {
            $where_array[] = "a.asset_name ILIKE :search";
        }
        if ($asset_sn === 'true') {
            $where_array[] = "a.serial_num ILIKE :search";
        }

        $where_array = implode(' OR ', $where_array);

        $audit_query = "SELECT a.asset_tag, a.serial_num, a.po, a.bus_unit, 
        a.asset_name, a.asset_price, a. dept_id, a.room_tag, a.asset_status, 
        a.asset_notes, aa.dept_id AS found_dept, aa.note AS found_note, aa.room_tag 
        AS found_room_tag, b.bldg_name, r.room_loc,
        CASE 
            WHEN aa.asset_tag IS NOT NULL THEN 1 ELSE 0 
        END AS is_found 
        FROM asset_info a 
            LEFT JOIN audited_asset aa 
                ON aa.asset_tag = a.asset_tag 
                AND aa.audit_id = " . $audit_id .
            " JOIN room_table AS r ON a.room_tag = r.room_tag                                                   
             JOIN bldg_table AS b ON r.bldg_id = b.bldg_id
            WHERE " . $where_array;
        /*
        $audit_query = "SELECT a.asset_tag, a.serial_num, a.po, a.bus_unit,
            a.asset_name, a.asset_price, a.room_tag, a.dept_id, b.bldg_name, r.room_loc FROM asset_info AS a 
            JOIN room_table AS r ON a.room_tag = r.room_tag 
            JOIN bldg_table AS b ON r.bldg_id = b.bldg_id 
            WHERE (" . $where_array . ") " . $and1 . $where_dept . $and2 . $where_price;
            */
    }
    $stmt = $dbh->prepare($audit_query);
    $stmt->execute($params);
    $data_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = json_encode($data_from_db);
    $_SESSION['data'] = $data_from_db;
    unset($_SESSION['saved_tags']);
    header('Content-Type: application/json');
    echo $data;
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once("tables_layout.php");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<?php
if (isset($_POST['search']) || isset($_GET['search'])) {

    //-------------------------------------------------------------------------
    //  POST TO GET FROM SCRIPT.JS SEARCH FORM
    $location_from = '';
    $tag = strtoupper(htmlspecialchars($_POST['search'] ?? '', ENT_QUOTES));
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 1;
    $category = $_POST['categories'];
    //$status = $_POST['statusFilter'] ?? 'In Service';
    $audit_id = $_POST['statusFilter'] ?? 6;
    $dept_id = $_POST['dept_id'];
    //$dept_id_search = strtoupper($_POST['dept_id_search']);
    $room_tag = $_POST['room_tag'];
    $room_loc = $_POST['room_loc'];
    $asset_sn = $_POST['asset_sn'];
    //$asset_price = ($_POST['asset_price'] !== '') ? (int)$_POST['asset_price'] : -1;
    $asset_price_check = $_POST['asset_price_check']; //Returns Asset Price
    //$asset_price_operation = $_POST['price_operation'];
    $asset_po = $_POST['asset_po'];
    $bldg_id = $_POST['bldg_id'];
    $bldg_id_val = $_POST['bldg_id_search'];
    $bldg_name = $_POST['bldg_name'];
    $box_name = $_POST['box_name'];
    $params = [':search' => "%$tag%"];
    $params2 = [':search' => "%$tag%"];
    $q_all_params = [];
    $notes = $_POST['notes'] ?? 'false';
    //-------------------------------------------------------------------------


    //-------------------------------------------------------------------------
    //  GET QUERY OFFSET
    $query_offset = max(0, (int)($offset - 1)) * 50;
    $row_num = isset($query_offset) ? $query_offset + 1 : 1;

    $result = [];
    //-------------------------------------------------------------------------


    //-------------------------------------------------------------------------
    //  RESET ARRAYS
    $header_true = [];
    $column_array = [];
    $where_array = [];
    //-------------------------------------------------------------------------


    if ($category === 'assets') {
        $dept_id_search_active = $price_search_active = 0;
        $where_price = $where_dept = $location = '';
        //-------------------------------------------------------------------------
        //      SET COLUMNS WITH WHERE CONDITIONING
        $column_array[] = "a.asset_tag";
        $column_array[] = "a.bus_unit";
        $column_array[] = "a.asset_status";
        $where_array[] = "a.asset_tag LIKE :search";
        $where_status = '';
        /*
        if ($status === 'In Service' || $status === 'Disposed') {
            $where_status = " AND a.asset_status = '" . $status . "'";
        }
            */
        if ($room_tag === 'true') {
            // Might be wasted, potentially will get rid of
            $header_true['room_tag'] = 'true';
            // FOR QUERYING
            $column_array[] = "a.room_tag";
            $where_array[] = "CAST(a.room_tag AS TEXT) ILIKE :search";
        }
        if ($notes === 'true') {
            $header_true['notes'] = 'true';
        }
        if ($box_name === 'true') {
            $header_true['asset_name'] = 'true';
            $column_array[] = "a.asset_name";
            $where_array[] = "a.asset_name ILIKE :search";
        }
        if ($asset_sn === 'true') {
            $header_true['asset_sn'] = 'true';
            $column_array[] = "a.serial_num";
            $where_array[] = "a.serial_num ILIKE :search";
        }/*
        if (isset($asset_price_operation)) {
            $params[':price'] = $asset_price;
            $params2[':price'] = $asset_price;
            $where_price = " AND a.asset_price " . $asset_price_operation . " :price";
        }*/
        if ($asset_price_check === 'true') {
            $header_true['asset_price'] = 'true';
            $column_array[] = "a.asset_price";
        }
        if ($asset_po === 'true') {
            $header_true['asset_po'] = 'true';
            $column_array[] = 'a.po';
            $where_array[] = "CAST(a.po AS TEXT) ILIKE :search";
        }
        if ($dept_id === 'true') {
            $header_true['dept_id'] = 'true';
            $column_array[] = "a.dept_id";
        }
        if (isset($dept_id_search) && $dept_id_search !== '') {
            $params[':dept_id'] = $dept_id_search;
            $params2[':dept_id'] = $dept_id_search;
            $where_dept = " AND a.dept_id = :dept_id";
        }
        if ($room_loc === 'true') {
            $header_true['room_loc'] = 'true';
            $header_true['bldg_name'] = 'true';
            $location_from = " JOIN room_table AS r on a.room_tag = r.room_tag JOIN bldg_table AS b on r.bldg_id = b.bldg_id ";
            $column_array[] = "b.bldg_name";
            $column_array[] = "r.room_loc";
        }

        $column_array = implode(', ', $column_array);
        $where_array = implode(' OR ', $where_array);
        $query = "SELECT a.asset_tag, a.serial_num, a.po, a.bus_unit, 
        a.asset_name, a.asset_price, a. dept_id, a.room_tag, a.asset_status, 
        a.asset_notes, aa.dept_id AS found_dept, aa.note AS found_note, aa.room_tag 
        AS found_room_tag, b.bldg_name, r.room_loc,
        CASE 
            WHEN aa.asset_tag IS NOT NULL THEN 1 ELSE 0 
        END AS is_found 
        FROM asset_info a 
            LEFT JOIN audited_asset aa 
                ON aa.asset_tag = a.asset_tag 
                AND aa.audit_id = " . $audit_id .
            " JOIN room_table AS r ON a.room_tag = r.room_tag                                                   
        JOIN bldg_table AS b ON r.bldg_id = b.bldg_id
        WHERE " . $where_array . $query_end;
        $query_count = "SELECT COUNT(*) as Rows FROM asset_info AS a WHERE (" . $where_array . ") " . $where_dept . $where_price . $where_status;
        //-------------------------------------------------------------------------

        //-------------------------------------------------------------------------
        //      SHOW CHECKBOXES & INPUT FOR ASSET FILTER
        echo "<script>addCheckboxes('.filter-assets');
        document.getElementById('search-label').textContent = 'Assets(Tag, Name, Custodian, Serial)';
        </script>";


        //-------------------------------------------------------------------------
        //      HIDE CHECKBOXES FOR BLDG & INPUT FOR BLDG FILTER
        echo "<script>removeCheckbox('.filter-bldg');</script>";
        if ($tag === 'ALL' || $tag === '') {
            $where = $and = $and2 = '';
            $where_dept = $where_price = '';
            $count = 0;
            $q_all_params = [':offset' => $query_offset];
            $where_status = '';
            /*
            if ($status === 'In Service' || $status === 'Disposed') {
                $where_status = " a.asset_status = '" . $status . "'";
                $count++;
            }
            if (isset($_POST['dept_id_search']) && $_POST['dept_id_search'] !== '') {
                $where_dept = " a.dept_id = :dept_id ";
                $q_all_params[':dept_id'] = $dept_id_search;
                $q_c_params[':dept_id'] = $dept_id_search;
                $count++;
            }
            if (isset($_POST['asset_price']) && $_POST['asset_price'] !== '') {
                $q_all_params[':price'] = $asset_price;
                $q_c_params[':price'] = $asset_price;
                $where_price = " a.asset_price " . $asset_price_operation . " :price ";
                $count++;
            }*/

            $where = ($count > 0) ? ' WHERE ' : '';

            /*
            if ($where_price && $where_dept) {
                $and = ' AND ';
            }
            if ($where_dept && $where_status) {
                $and2 = ' AND ';
            }
            if ($where_price && $where_status) {
                $and = ' AND ';
            }
            $query = $query_start . $column_array . " " . $query_asset_from . $location_from . " " . $where . $where_price . $and . $where_dept . $and2 . $where_status . ' ORDER BY a.asset_tag ' . $query_end;
*/
            $query = "SELECT a.asset_tag, a.serial_num, a.po, a.bus_unit, 
        a.asset_name, a.asset_price, a. dept_id, a.room_tag, a.asset_status, 
        a.asset_notes, aa.dept_id AS found_dept, aa.note AS found_note, aa.room_tag 
        AS found_room_tag, b.bldg_name, r.room_loc,
        CASE 
            WHEN aa.asset_tag IS NOT NULL THEN 1 ELSE 0 
        END AS is_found 
        FROM asset_info a 
            LEFT JOIN audited_asset aa 
                ON aa.asset_tag = a.asset_tag 
                AND aa.audit_id = " . $audit_id .
                " JOIN room_table AS r ON a.room_tag = r.room_tag                                                   
        JOIN bldg_table AS b ON r.bldg_id = b.bldg_id
         ORDER BY a.asset_tag
            LIMIT 50 OFFSET :offset";
            $query_count = "SELECT COUNT(*) as Rows FROM asset_info AS a JOIN room_table AS r ON a.room_tag = r.room_tag JOIN bldg_table AS b ON r.bldg_id = b.bldg_id " . $where . $where_price . $and . $where_dept . $and2 . $where_status;

            $exec_query = $dbh->prepare($query);
            $exec_query->execute($q_all_params);
            $result = $exec_query->fetchAll(PDO::FETCH_ASSOC);



            $exec_count = $dbh->prepare($query_count);
            if (isset($q_c_params)) {
                $exec_count->execute($q_c_params);
            } else {
                $exec_count->execute();
            }
            $total_rows = $exec_count->fetch(PDO::FETCH_ASSOC);
            if (count($result) === 0) {
                $query = $query_start . $column_array . ' ' . $query_asset_from . $location_from . " LEFT JOIN department d on d.dept_id = a.dept_id WHERE :search ILIKE ANY(d.custodian) AND asset_status = 'In Service' ORDER BY a.asset_tag " . $query_end;
                echo $query . '<br>';
                $stmt = $dbh->prepare($query);
                $stmt->execute([':offset' => $query_offset, ':search' => '%' . $tag . '%']);
                $result = $stmt->fetchAll();
                $query = "SELECT COUNT(*) AS Rows " . $query_asset_from . $location_from . " WHERE :search ILIKE ANY(custodian) AND asset_status = 'In Service ORDER BY a.asset_tag";
                $stmt = $dbh->prepare($query);
                $stmt->execute([':offset' => $query_offset, ':search' => '%' . $tag . '%']);
                $total_rows = $stmt->fetch();
            }
        } else {
            $exec_query = $dbh->prepare($query);
            $params['offset'] = $query_offset;
            $exec_query->execute($params);
            $result = $exec_query->fetchAll(PDO::FETCH_ASSOC);

            $exec_count = $dbh->prepare($query_count);
            $exec_count->execute($params2);
            $total_rows = $exec_count->fetch(PDO::FETCH_ASSOC);
        }
        $row_count = (int)$total_rows['rows'];


        if ($result) {
        }
    } else if ($category === 'buildings') {
        $and = $bldg_id_where = $where = '';
        //      SHOW CHECKBOXES & INPUT FOR BLDG FILTER
        echo "<script>addCheckboxes('.filter-bldg');
        document.getElementById('search-label').textContent = 'Buildings & Rooms(ID, Building Name, Room Number)';
        removeCheckbox('.filter-assets');</script>";

        $params_bldg = [":offset" => $query_offset];
        $params_count = [];
        $column_array[] = 'room_tag';
        $where_array[] = 'CAST(room_tag AS TEXT) ILIKE :search';
        if ($bldg_id_val !== NULL  && $bldg_id_val !== '') {
            $params_bldg[":bldg_id"] = $bldg_id_val;
            $params_count[":bldg_id"] = $bldg_id_val;
            $and = ' AND ';
            $where = ' WHERE ';
            $bldg_id_where = 'b.bldg_id = :bldg_id';
        }
        if ($bldg_id === 'true') {
            $header_true['bldg_id'] = 'true';
            $column_array[] = 'b.bldg_id';
        }
        if ($bldg_name === 'true') {
            $header_true['bldg_name'] = 'true';
            $column_array[] = 'b.bldg_name';
            $where_array[] = 'b.bldg_name ILIKE :search';
        }
        if ($room_loc === 'true') {
            $header_true['room_loc'] = 'true';
            $column_array[] = 'r.room_loc';
            $where_array[] = 'r.room_loc ILIKE :search';
        }
        $column_array = implode(', ', $column_array);
        $where_array = implode(' OR ', $where_array);
        $query_bldg_from = " FROM bldg_table b LEFT JOIN room_table r ON r.bldg_id = b.bldg_id ";
        if ($tag === 'ALL' || $tag === '') {
            $query = "SELECT " . $column_array . $query_bldg_from . $where . $bldg_id_where . ' ORDER BY r.room_loc ' . $query_end;
            $bldg_count = "SELECT COUNT(*) as Rows " . $query_bldg_from . $where . $bldg_id_where;

            $bldg_e = $dbh->prepare($query);
            $bldg_e->execute($params_bldg);
            $result = $bldg_e->fetchAll(PDO::FETCH_ASSOC);

            $exec_count = $dbh->prepare($bldg_count);
            if (!isset($params_count[":bldg_id"]) || ($params_count[":bldg_id"] === '' || $params_count[":bldg_id"] === NULL)) {
                $exec_count->execute();
            } else {
                $exec_count->execute($params_count);
            }
            $total_rows = $exec_count->fetch(PDO::FETCH_ASSOC);
        } else {
            $params_bldg[":search"] = "%$tag%";
            $params_count[":search"] = "%$tag%";
            $query = "SELECT " . $column_array . ' ' . $query_bldg_from . ' WHERE (' . $where_array . ')' . $and . $bldg_id_where . ' ORDER BY room_loc ' . $query_end;

            $bldg_count = "SELECT COUNT(*) as Rows
                FROM bldg_table b LEFT JOIN room_table r ON r.bldg_id = b.bldg_id 
                WHERE
                (b.bldg_name like :search OR
                r.room_loc like :search OR
                CAST(r.room_tag as TEXT) like :search)" . $and . $bldg_id_where;
            $bldg_e = $dbh->prepare($query);
            $bldg_e->execute($params_bldg);
            $result = $bldg_e->fetchAll(PDO::FETCH_ASSOC);

            $exec_count = $dbh->prepare($bldg_count);
            $exec_count->execute($params_count);
            $total_rows = $exec_count->fetch(PDO::FETCH_ASSOC);
        }
        $row_count = (int)$total_rows['rows'];


        if ($result) {
        }
    } else if ($category === 'departments') {
        echo "<script>removeCheckbox('.filter-assets');
        removeCheckbox('.filter-bldg');
        removeCheckbox('.filter-room');
        document.getElementById('search-label').textContent = 'Departments(ID, Name)';</script>";
        if ($tag === 'ALL') {
            $dept_query = "SELECT * FROM department ORDER BY dept_id LIMIT 50 OFFSET :offset";
            $dept_count_query = "SELECT COUNT(*) as Rows FROM department";
            $count_stmt = $dbh->prepare($dept_count_query);
            $count_stmt->execute();
            $total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC);

            $data_stmt = $dbh->prepare($dept_query);
            $data_stmt->execute([":offset" => $query_offset]);
            $result = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $dept_count_query = "SELECT COUNT(*) as Rows FROM department WHERE dept_id ILIKE :search OR dept_name ILIKE :search";
            $params = [":search" => "%$tag%"];
            $count_stmt = $dbh->prepare($dept_count_query);
            $count_stmt->execute($params);
            $total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC);

            $dept_query = "SELECT * FROM department WHERE dept_id ILIKE :search OR dept_name ILIKE :search ORDER BY dept_id LIMIT 50 OFFSET :offset";
            $params[":offset"] = $query_offset;
            $data_stmt = $dbh->prepare($dept_query);
            $data_stmt->execute($params);
            $result = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $row_count = (int)$total_rows['rows'];
    } else if ($category === 'users') {
        echo "<script>removeCheckbox('.filter-assets');
        removeCheckbox('.filter-bldg');
        removeCheckbox('.filter-room');
        document.getElementById('search-label').textContent = 'Users(Email, Department ID)';</script>";
        if ($tag === 'ALL') {
            $user_count_query = "SELECT COUNT(*) as Rows FROM user_table";
            $count_stmt = $dbh->prepare($user_count_query);
            $count_stmt->execute();
            $total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC);

            $user_query = "SELECT username,email,u_role,last_login,f_name,l_name, dept_id FROM user_table LIMIT 50 OFFSET :offset";
            $data_stmt = $dbh->prepare($user_query);
            $data_stmt->execute([":offset" => $query_offset]);
            $result = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $user_count_query = "SELECT COUNT(*) as Rows FROM user_table WHERE email ILIKE :search OR :search = ANY(dept_id)";
            $params = [":search" => "%$tag%"];
            $count_stmt = $dbh->prepare($user_count_query);
            $count_stmt->execute([":search" => "%$tag%"]);
            $total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC);

            $user_query = "select username,email,last_login,u_role,f_name,l_name, dept_id from user_table where :search = ANY(dept_id) OR email ILIKE :search LIMIT 50 OFFSET :offset";
            $params[":offset"] = $query_offset;
            $data_stmt = $dbh->prepare($user_query);
            $data_stmt->execute($params);
            $result = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $row_count = (int)$total_rows['rows'];
    }
?>

    <body>
        <nav aria-label="Page navigation example">
            <ul class="pagination d-flex justify-content-center">
                <?php
                $total_pages = $row_count / 50;
                if (($offset === '1' || $offset === 1) && $total_pages > 1) {
                ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                    </li>
                    <li class="page-item active">
                        <span class="page-link">
                            <?= $offset ?>
                            <span class="sr-only">(current)</span>
                        </span>
                    </li>

                    <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset + 1 ?>)'><?= $offset + 1 ?></a></li>
                    <?php if ($total_pages > 2) { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset + 2 ?>)'><?= $offset + 2 ?></a></li>
                    <?php }
                    if ($total_pages > 3) { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset + 3 ?>)'><?= $offset + 3 ?></a></li>
                    <?php }
                    if ($total_pages > 4) { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset + 4 ?>)'><?= $offset + 4 ?></a></li>

                    <?php }
                    if ($total_pages <= 2 && $offset = 2) { ?>

                    <?php } else { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset + 1 ?>)'>Next</a></li>

                    <?php }
                } else if ($total_pages < $offset && $total_pages > 1) {
                    ?>
                    <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset - 1 ?>)'>Previous</a></li>
                    <?php if ($total_pages > 4) { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset - 4 ?>)'><?= $offset - 4 ?></a></li>
                    <?php }
                    if ($total_pages > 3) { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset - 3 ?>)'><?= $offset - 3 ?></a></li>
                    <?php }
                    if ($total_pages > 2) { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset - 2 ?>)'><?= $offset - 2 ?></a></li>
                    <?php } ?>
                    <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset - 1 ?>)'><?= $offset - 1 ?></a></li>
                    <li class="page-item active">
                        <span class="page-link">
                            <?= $offset ?>
                            <span class="sr-only">(current)</span>
                        </span>
                    </li>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Next</a>
                    </li> <?php
                        } else if ($total_pages > 1) {
                            ?>
                    <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset - 1 ?>)'>Previous</a></li>
                    <?php if ($offset > 2) { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset - 2 ?>)'><?= $offset - 2 ?></a></li>
                    <?php } ?>
                    <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset - 1 ?>)'><?= $offset - 1 ?></a></li>
                    <li class="page-item active">
                        <span class="page-link">
                            <?= $offset ?>
                            <span class="sr-only">(current)</span>
                        </span>
                    </li>
                    <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset + 1 ?>)'><?= $offset + 1 ?></a></li>
                    <?php if ($total_pages > $offset + 1) { ?>
                        <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset + 2 ?>)'><?= $offset + 2 ?></a></li>
                    <?php } ?>
                    <li class="page-item"><a class="page-link" href="#" onclick='searchTriggerViaAjax(<?= $offset + 1 ?>)'>Next</a></li>
                <?php
                        }
                ?>
            </ul>
        </nav>
    <?php
    if ($category === 'assets') {
        asset_layout($result, $header_true, $row_num);
    } else if ($category === 'buildings') {
        bldg_layout($result, $header_true, $row_num);
    } else if ($category === 'departments') {
        dept_layout($result, $row_num);
    } else if ($category === 'users') {
        user_layout($result, $row_num);
    }
}
    ?>
    </body>