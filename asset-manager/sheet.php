<?php
require_once '../config.php';
check_auth();
$result = NULL;
if (isset($_POST['profile_name'])) {
    $profile = $_POST['profile_name'];
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
    } else {
        $email = $_SESSION['email'];
    }


    $select_q = "SELECT p.color, p.asset_tag, a.asset_name, a.bus_unit,a.serial_num,
    a.room_tag, r.room_loc, b.bldg_name, a.dept_id, a.po, p.asset_note
    FROM user_asset_profile p LEFT JOIN asset_info a ON p.asset_tag = a.asset_tag
    LEFT JOIN room_table r ON a.room_tag = r.room_tag
    LEFT JOIN bldg_table b ON r.bldg_id = b.bldg_id
    WHERE p.profile_name = :profile_name AND p.email = :email ORDER BY p.asset_tag";
    try {
        $select_stmt = $dbh->prepare($select_q);
        $select_stmt->execute([":profile_name" => $profile, ":email" => $email]);
        $result = $select_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!--
    <script type="text/javascript" src="https://dataworks-7b7x.onrender.com/asset-manager/asset-ajax.js"></script>
    <link rel="stylesheet" href="https://dataworks-7b7x.onrender.com/asset-manager/manager.css">    -->
    <script type="text/javascript" src="<?= url('/asset-manager/asset-ajax.js') ?>"></script>
    <link rel="stylesheet" href="<?= url('/asset-manager/manager.css') ?>">
</head>

<body class="is-ajax">
    <div class="page-container">
        <?php if ($result) { ?>
            <div class="action-buttons">
                <!--     <a href="https://dataworks-7b7x.onrender.com/asset-manager/crud/excel-download.php?profile_name='<?= urlencode($profile) ?>'">
                    <button class="btn btn-excel"> Excel Sheet</button>
                </a>
                <a href="https://dataworks-7b7x.onrender.com/asset-manager/crud/pdf-download.php?profile_name='<?= urlencode($profile) ?>'">
                    <button class="btn btn-pdf"> PDF</button>
                </a>
                <a href="https://dataworks-7b7x.onrender.com/asset-manager/crud/setup-audit.php?profile_name='<?= urlencode($profile) ?>'">
                    <button class="btn btn-audit"> Audit</button>
                </a>
-->
                <a href="<?= url('/asset-manager/crud/excel-download.php?profile_name=' . urlencode($profile)) ?>">
                    <button class="btn btn-excel"> Excel Sheet</button>
                </a>
                <a href="<?= url('/asset-manager/crud/pdf-download.php?profile_name=' . urlencode($profile)) ?>">
                    <button class="btn btn-pdf"> PDF</button>
                </a>
                <a href="<?= url('/asset-manager/crud/setup-audit.php?profile_name=' . urlencode($profile)) ?>">
                    <button class="btn btn-audit"> Audit</button>
                </a>
            </div>
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Row</th>
                            <th>Business Unit</th>
                            <th>Asset Tag</th>
                            <th>Asset Name</th>
                            <th>Serial Number</th>
                            <th>Room Tag</th>
                            <th>Room Location</th>
                            <th>Building</th>
                            <th>Department ID</th>
                            <th>PO</th>
                            <th class='search-tags'><input onchange="filterTable()" id="search-asset" type="text" placeholder="Search assets..."></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($result as $index => $row) {
                            if (!empty($row['color'])) { ?>
                                <tr id="<?php echo htmlspecialchars($row['asset_tag']); ?>" style='background-color: <?= trim($row['color'] ?? '') ?>'>
                                <?php } else { ?>
                                <tr id="<?php echo htmlspecialchars($row['asset_tag']); ?>">
                                <?php } ?>
                                <td><select name="color" id="<?= htmlspecialchars($row['asset_tag']) ?>-color" onchange='changeBackgroundColor(<?= json_encode($row["asset_tag"]) ?>,this.value, <?= json_encode($profile) ?>)'>
                                        <option value=""></option>
                                        <option value="#FF796F">R</option>
                                        <option value="#90EE90">G</option>
                                        <option value="#ADD8E6">B</option>
                                    </select></td>
                                <td><?= $index + 1 ?></td>
                                <td><?= $row['bus_unit'] ?></td>
                                <td><span class="asset-tag"><?= $row['asset_tag'] ?></span></td>
                                <td><?= $row['asset_name'] ?></td>
                                <td><?= $row['serial_num'] ?></td>
                                <td><?= $row['room_tag'] ?></td>
                                <td><?= $row['room_loc'] ?></td>
                                <td><?= $row['bldg_name'] ?></td>
                                <td><?= $row['dept_id'] ?></td>
                                <td><?= $row['po'] ?></td>
                                <td>
                                    <button id="delete-asset" class='btn btn-delete asset-row' value="<?= $row['asset_tag'] ?>">🗑️ Delete</button>
                                </td>
                                <td>
                                    <textarea name="notes" class='asset-note' id="<?= $row['asset_tag'] ?>" placeholder="Add notes..."><?= $row['asset_note'] ?? '' ?></textarea>
                                </td>
                                </tr>
                            <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="empty-state">
                <h3>📋 No assets in profile</h3>
                <p>This profile doesn't contain any assets yet.</p>
            </div>
        <?php } ?>
    </div>
    <script>
        function changeBackgroundColor(asset_tag, color, profile) {
            const rows = document.querySelectorAll("tr");
            rows.forEach(row => {
                if (row.id === String(asset_tag)) {
                    row.style.backgroundColor = color;
                }
            });
            //fetch('https://dataworks-7b7x.onrender.com/asset-manager/color.php', {
            fetch('<?= url('/asset-manager/color.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-type': 'application/json'
                    },
                    body: JSON.stringify({
                        asset_tag: asset_tag,
                        color: color,
                        profile_name: profile
                    })
                }).then(response => response.json())
                .then(result => {
                    console.log("result", result)
                })
                .catch(e => console.error(e));
        }

        function filterTable() {
            var input, filter, table, tr, td, i, txt_value;
            input = document.getElementById("search-asset");
            filter = input.value.toUpperCase();
            table = document.querySelector(".modern-table");
            tr = table.getElementsByTagName("tr");
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[3];
                if (td) {
                    txt_value = td.textContent || td.innerText;
                    if (txt_value.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>

</html>