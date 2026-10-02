<?php
include_once "../config.php";
?>
<link rel="stylesheet" href="ajax.css">

<?php
function asset_layout($result, $header_true, $row_num)
{
    global $dbh;
    $select = "select dept_name, dept_id FROM department";
    $select_stmt = $dbh->query($select);
    $dept_info = $select_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <datalist id="dept-names">
        <?php foreach ($dept_info as $dept) { ?>
            <option value="<?= $dept['dept_name'] ?>"><?= $dept['dept_name'] . '-' . $dept['dept_id'] ?></option>
        <?php } ?>
    </datalist>
    <?php
    $select = "select bldg_name, bldg_id FROM bldg_table";
    $select_stmt = $dbh->query($select);
    $bldg_info = $select_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <datalist id="bldg-names">
        <?php foreach ($bldg_info as $bldg) { ?>
            <option value="<?= $bldg['bldg_name'] ?>"><?= $bldg['bldg_name'] . '-' . $bldg['bldg_id'] ?></option>
        <?php } ?>
    </datalist>
    <?php
    $select = "select bldg_id, room_loc FROM room_table";
    $select_stmt = $dbh->query($select);
    $room_info = $select_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <datalist name="room-names">
        <?php foreach ($room_info as $room) { ?>
            <option value="<?= $room['room_loc'] . '-' . $room['bldg_id'] ?>"><?= $room['room_loc'] ?></option>
        <?php } ?>
    </datalist>
    <section class="is-ajax" id="is-ajax" style="opacity: 0;">
        <table id="asset-table">
            <thead>
                <tr>
                    <th class='row-even'>Row</th>
                    <th class='row-even'>Unit</th>
                    <th class='row-even'>Asset Tag</th>
                    <?php if (array_key_exists('asset_name', $header_true)) {
                        echo "<th class='row-even'>Description</th>";
                    }
                    if (array_key_exists('dept_id', $header_true)) {
                        echo "<th class='row-even'>Department</th>";
                    }
                    if (array_key_exists('room_tag', $header_true)) {
                        echo "<th class='row-even'>Room Tag</th>";
                    }
                    if (array_key_exists('room_loc', $header_true)) {
                        echo "<th class='row-even'>Room Number</th>";
                    }
                    if (array_key_exists('room_loc', $header_true)) {
                        echo "<th class='row-even'>Building Name</th>";
                    }
                    if (array_key_exists('asset_sn', $header_true)) {
                        echo "<th class='row-even'>Serial Number</th>";
                    }
                    echo "<th class='row-even'>Status</th>";
                    if (array_key_exists('asset_price', $header_true)) {
                        echo "<th class='row-even'>Price</th>";
                    }
                    if (array_key_exists('asset_po', $header_true)) {
                        echo "<th class='row-even'>Purchase Order</th>";
                    }
                    if (array_key_exists('notes', $header_true)) {
                        echo "<th class='row-even'>Notes</th>";
                    }
                    echo "<th class='row-even'>Found Status</th>"; ?>?>
                </tr>

            </thead>
            <tbody id="table-body"><?php
                                    foreach ($result as $row) {
                                        $color_class = ($row_num % 2 === 0) ? 'row-even' : 'row-odd';

                                        // Escape values for safety
                                        $safe_tag = htmlspecialchars($row['asset_tag'] ?? '', ENT_QUOTES);
                                        $asset_status = htmlspecialchars($row['asset_status'] ?? '', ENT_QUOTES);
                                        $bus_unit = htmlspecialchars($row['bus_unit'] ?? '', ENT_QUOTES);
                                        $safe_name = htmlspecialchars($row['asset_name'] ?? '', ENT_QUOTES);
                                        //$safe_deptid = htmlspecialchars($row['dept_id'] ?? '', ENT_QUOTES);
                                        $safe_deptid = ($row['found_dept'] ?? $row['dept_id'] ?? '');
                                        $safe_price = htmlspecialchars($row['asset_price'] ?? '', ENT_QUOTES);
                                        $safe_po = htmlspecialchars($row['po'] ?? '', ENT_QUOTES);
                                        $safe_room = htmlspecialchars($row['room_tag'] ?? '', ENT_QUOTES);
                                        $safe_serial = htmlspecialchars($row['serial_num'] ?? '', ENT_QUOTES);
                                        $bldg_name = htmlspecialchars($row['bldg_name'] ?? '', ENT_QUOTES);
                                        $room_loc = htmlspecialchars($row['room_loc'] ?? '', ENT_QUOTES);
                                        $notes = htmlspecialchars($row['found_note'] ?? $row['asset_notes'] ?? '', ENT_QUOTES);
                                        $is_found = ((int)$row['is_found'] === 1)
                                            ? 'Found'
                                            : 'Not Found';

                                    ?>
                    <tr>
                        <td class=<?= $color_class ?>><?= $row_num++ ?></td>
                        <td class=<?= $color_class ?>><?= $bus_unit ?></td>
                        <td class=<?= $color_class ?>>
                            <button id="button-9" data-toggle="modal" data-target="#modal<?= $safe_tag ?>"><?= $safe_tag ?></button>
                        </td>
                        <?php if (array_key_exists('asset_name', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $safe_name . "</td>";
                                        } ?>
                        <?php if (array_key_exists('dept_id', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $safe_deptid . "</td>";
                                        } ?>
                        <?php if (array_key_exists('room_tag', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $safe_room . "</td>";
                                        } ?>
                        <?php if (array_key_exists('room_loc', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $room_loc . "</td>";
                                        } ?>
                        <?php if (array_key_exists('room_loc', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $bldg_name . "</td>";
                                        } ?>
                        <?php if (array_key_exists('asset_sn', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $safe_serial . "</td>";
                                        } ?>
                        <td class=<?= $color_class ?>><?= $asset_status ?></td>
                        <?php if (array_key_exists('asset_price', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $safe_price . "</td>";
                                        } ?>
                        <?php if (array_key_exists('asset_po', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $safe_po . "</td>";
                                        } ?>
                        <?php if (array_key_exists('notes', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $notes . "</td>";
                                        } ?>
                        <td class="<?= $color_class ?>">
                            <?= $is_found ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>

        </table>
    </section>
    <?php foreach ($result as $row) {
        $safe_tag = htmlspecialchars($row['asset_tag'] ?? '', ENT_QUOTES);
        $safe_name = htmlspecialchars($row['asset_name'] ?? '', ENT_QUOTES);
        //$safe_deptid = htmlspecialchars($row['dept_id'] ?? '', ENT_QUOTES);
        $safe_deptid = htmlspecialchars($row['found_dept'] ?? $row['dept_id'] ?? '', ENT_QUOTES);
        $safe_price = htmlspecialchars($row['asset_price'] ?? '', ENT_QUOTES);
        $safe_po = htmlspecialchars($row['po'] ?? '', ENT_QUOTES);
        $safe_room = htmlspecialchars($row['room_tag'] ?? '', ENT_QUOTES);
        $safe_serial = htmlspecialchars($row['serial_num'] ?? '', ENT_QUOTES);
        $bldg_name = htmlspecialchars($row['bldg_name'] ?? '', ENT_QUOTES);
        $room_loc = htmlspecialchars($row['room_loc'] ?? '', ENT_QUOTES);
        $bus_unit = htmlspecialchars($row['bus_unit'] ?? '', ENT_QUOTES);
        $notes = htmlspecialchars($row['found_note'] ?? $row['asset_notes'] ?? '', ENT_QUOTES);
        $is_found = ((int)$row['is_found'] === 1)
            ? 'Found'
            : 'Not Found';
        $all_bus = [['BKCMP'], ['BKSPA'], ['BKASI'], ['BKSTU'], ['BKFDN']];
        $extra_bus = [];
        foreach ($all_bus as $bus) {
            if ($bus_unit == $bus) {
                continue;
            }
            $extra_bus[] = $bus;
        }
    ?>
        <div id="modal<?= $safe_tag ?>" class="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $safe_tag; ?>" aria-hidden="true">
            <!-- Modal content -->
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel<?= $safe_tag; ?>">Asset Details for <?= $safe_tag ?></h5>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <label for="asset_tag">Asset Tag:</label>
                        <input type="text" id="asset_tag" name="asset_tag" value="<?= $safe_tag ?>" readonly>
                        <br>
                        <label for="name">Asset Name:</label>
                        <input type="text" id="name" name="name" value="<?= $safe_name ?>">
                        <br>

                        <label for="deptid">Department ID:</label>
                        <input type="text" id="deptid" name="deptid" value="<?= $safe_deptid ?>" readonly>
                        <br>
                        <label for="location">Room Tag:</label>
                        <input type="text" id="location" name="location" value="<?= $safe_room ?>">
                        <br>
                        <label for="serial">Serial Number:</label>
                        <input type="text" id="serial" name="serial" value="<?= $safe_serial ?>">
                        <br>
                        <label for="price">Price:</label>
                        <input type="number" id="price" name="price" value="<?= $safe_price ?>">
                        <br>
                        <label for="po">Purchase Order:</label>
                        <input type="text" id="po" name="po" value="<?= $safe_po ?>">
                        <br>
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="in_service">In Service</option>
                            <option value="disposed">Disposed</option>
                        </select>
                        <br>
                        <label for="is_found">Found Status:</label>
                        <input type="text" id="is_found" name="is_found" value="<?= $is_found ?>">
                        <option value="found">Found</option>
                        <option value="not-found">Not Found</option>
                        <label for="notes">Notes:</label>
                        <input type="text" id="notes" name="notes" value="<?= $notes ?>">
                        <br>
                        </input>
                        <br>
                        <label>Kuali Form</label>
                        <select class="forms-needed" data-tag="<?= $safe_tag ?>" name="form-select-<?= $safe_tag ?>" id="form-select-<?= $safe_tag ?>" onchange="showFormType(this)">
                            <label>Kuali Form</label>
                            <option value=""></option>
                            <option value="check-out">Check Out</option>
                            <option value="check-in">Check In</option>
                            <option value="transfer">Transfer</option>
                        </select>
                        <br>

                        <!-- CHECK OUT/IN -->
                        <div class="check-<?= $safe_tag ?>" style="display: none;">
                            <div class="form-field-group">
                                <label>Filling out for</label>
                                <select id="who-<?= $safe_tag ?>">
                                    <option value="Myself">Myself</option>
                                    <option value="someone-else">Someone Else</option>
                                </select>
                            </div>
                            <div class="form-field-group">
                                <label id="someone-else-label-<?= $safe_tag ?>">Borrower</label>
                                <input id="someone-else-<?= $safe_tag ?>" type="text" placeholder="Email of Borrower" style="display:none;">
                            </div>
                            <div class="form-field-group">
                                <label>Condition</label>
                                <select id="check-condition-<?= $safe_tag ?>">
                                    <option value="New">New</option>
                                    <option value="Good">Good</option>
                                    <option value="Used">Used</option>
                                    <option value="Damanged">Damaged</option>
                                </select>
                            </div>
                            <div class="form-field-group">
                                <label>Equipment Type</label>
                                <select id="check-item-type-<?= $safe_tag ?>">
                                    <option value='Laptop'>Laptop</option>
                                    <option value='Desktop'>Desktop</option>
                                    <option value='Tablet'>Tablet</option>
                                </select>
                            </div>
                            <div class="form-field-group">
                                <label>Notes</label>
                                <textarea id="check-notes-<?= $safe_tag ?>" placeholder="Notes..."></textarea>
                            </div>
                        </div>
                        <!-- -->

                        <!-- TRANSFER -->
                        <div class="transfer-<?= $safe_tag ?>" style="display: none;">
                            <!-- HAVE NOT STARTED SOLO TRANSFER -->
                            <div class="form-field-group">
                                <label>Is This a</label>
                                <select id="transfer-form-type-<?= $safe_tag ?>">
                                    <option value=''></option>
                                    <option value='location'>Building/Room/Location change</option>
                                    <option value='dept'>Department Change</option>
                                </select>
                            </div>
                            <div class="form-field-group">
                                <label>Is this equipment kept inside a building?</label>
                                <select id="transfer-in-bldg-<?= $safe_tag ?>">
                                    <option value='Yes'>Yes</option>
                                    <option value='No'>No</option>
                                </select>
                            </div>
                            <div class="form-field-group transfer-bldg-text-<?= $safe_tag ?>" style='display:none;'>
                                <label>Where is your equipment stored, parked, or housed?</label>
                                <input type="text" id="transfer-bldg-text-<?= $safe_tag ?>">
                                <label></label>
                            </div>

                            <div class="form-field-group dept-change-<?= $safe_tag ?>" style='display:none;'>
                                <label>Why?</label>
                                <input type="text" id="transfer-why-<?= $safe_tag ?>">
                                <label class="error-label" id='transfer-why-feedback-<?= $safe_tag ?>'>
                                    <label>New Department</label>
                                    <input type="search" list="dept-names" id="transfer-dept-<?= $safe_tag ?>">
                            </div>
                            <div class="form-field-group">
                                <label>Notes</label>
                                <textarea id="transfer-notes-<?= $safe_tag ?>" placeholder="Notes..."></textarea>
                            </div>

                            <div class="form-field-group room-dept-change-<?= $safe_tag ?>" style="display:none;">
                                <label>New Building</label>
                                <input type="search" list="bldg-names" id="transfer-bldg-<?= $safe_tag ?>">
                                <label class="error-label" id="transfer-bldg-feedback-<?= $safe_tag ?>"></label>
                                <label>New Room</label>
                                <input type="text" id="transfer-room-<?= $safe_tag ?>">
                                <input class="error-label" id="transfer-room-feedback-<?= $safe_tag ?>">
                            </div>
                        </div>


                        <!-- -->

                        <button type="submit" value="<?= $safe_tag ?>" name="delete-asset">Delete Asset</button>
                        <button onclick="sendForm(this)" data-tag="<?= $safe_tag ?>" type="button">Send Form</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
<?php
}


function bldg_layout($result, $header_true, $row_num)
{
?>
    <section class="is-ajax" id="is-ajax" style="opacity: 0;">
        <table id="asset-table">
            <thead>
                <tr>
                    <th class='row-even'>Row</th>
                    <th class='row-even'>Room Tag</th>
                    <?php if (array_key_exists('bldg_name', $header_true)) {
                        echo "<th class='row-even'>Building Name</th>";
                    }
                    if (array_key_exists('room_loc', $header_true)) {
                        echo "<th class='row-even'>Room Number</th>";
                    }
                    if (array_key_exists('bldg_id', $header_true)) {
                        echo "<th class='row-even'>Building ID</th>";
                    }
                    ?>
                </tr>

            </thead>
            <tbody id="table-body"><?php
                                    foreach ($result as $row) {
                                        $color_class = ($row_num % 2 === 0) ? 'row-even' : 'row-odd';

                                        // Escape values for safety
                                        $bldg_id = htmlspecialchars($row['bldg_id'] ?? '', ENT_QUOTES);
                                        $bldg_name = htmlspecialchars($row['bldg_name'] ?? '', ENT_QUOTES);
                                        $room_num = htmlspecialchars($row['room_loc'] ?? '', ENT_QUOTES);
                                        $room_tag = htmlspecialchars($row['room_tag'] ?? '', ENT_QUOTES);

                                    ?>
                    <tr>
                        <td class=<?= $color_class ?>><?= $row_num++ ?></td>
                        <td class=<?= $color_class ?>>
                            <button id="button-9" data-toggle="modal" data-target="#modal<?= $room_tag ?>"><?= $room_tag ?></button>
                        </td>
                        <?php if (array_key_exists('bldg_name', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $bldg_name . "</td>";
                                        } ?>
                        <?php if (array_key_exists('room_loc', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $room_num . "</td>";
                                        } ?>
                        <?php if (array_key_exists('bldg_id', $header_true)) {
                                            echo "<td class=" . $color_class . ">" . $bldg_id . "</td>";
                                        } ?>
                    </tr>
                <?php } ?>
            </tbody>

        </table>
    </section>
    <?php foreach ($result as $row) {
        $bldg_id = htmlspecialchars($row['bldg_id'] ?? '', ENT_QUOTES);
        $bldg_name = htmlspecialchars($row['bldg_name'] ?? '', ENT_QUOTES);
        $room_num = htmlspecialchars($row['room_loc'] ?? '', ENT_QUOTES);
        $room_tag = htmlspecialchars($row['room_tag'] ?? '', ENT_QUOTES);
    ?>
        <div id="modal<?= $room_tag ?>" class="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $room_tag; ?>" aria-hidden="true">
            <!-- Modal content -->
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel<?= $room_tag; ?>">Room Details for <?= $room_tag ?></h5>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="crud/change_bldg_info.php" method="post">
                            <input type="hidden" id="old_bldg_id" name="old_bldg_id" value="<?= $bldg_id ?>">
                            <input type="hidden" id="old_name" name="old_name" value="<?= $bldg_name ?>">
                            <input type="hidden" id="old_room_loc" name="old_room_loc" value="<?= $room_num ?>">
                            <input type="hidden" id="old_room_tag" name="old_room_tag" value="<?= $room_tag ?>">
                            <label for="asset_tag">Building ID:</label>
                            <input type="number" id="bldg_id" name="bldg_id" value="<?= $bldg_id ?>">
                            <br>
                            <label for="name">Building Name:</label>
                            <input type="text" id="name" name="name" value="<?= $bldg_name ?>">
                            <br>

                            <label for="room_loc">Room Number/Name:</label>
                            <input type="text" id="room_loc" name="room_loc" value="<?= $room_num ?>">
                            <br>
                            <label for="location">Room Tag:</label>
                            <input type="text" id="room_tag" name="room_tag" value="<?= $room_tag ?>">
                            <br>
                            <button type="submit" value="<?= $room_tag ?>" name="delete-room">Delete Room</button>
                            <button type="submit" name="bldg">Update Room</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    <?php } ?>
<?php
}

function dept_layout($result, $row_num)
{ ?>
    <section class="is-ajax" id="is-ajax" style="opacity: 0;">
        <table id="asset-table">
            <thead>
                <tr>
                    <th class='row-even'>Row</th>
                    <th class='row-even'>Department ID</th>
                    <th class='row-even'>Department Name</th>
                    <th class='row-even'>Custodian</th>
                    <th class='row-even'>Manager</th>
                </tr>

            </thead>
            <tbody id="table-body"><?php
                                    foreach ($result as $row) {
                                        $color_class = ($row_num % 2 === 0) ? 'row-even' : 'row-odd';

                                        // Escape values for safety
                                        $dept_id = htmlspecialchars($row['dept_id'] ?? '', ENT_QUOTES);
                                        $dept_name = htmlspecialchars($row['dept_name'] ?? '', ENT_QUOTES);
                                        //$custodian = htmlspecialchars($row['custodian'] ?? '', ENT_QUOTES);
                                        $custodian = str_getcsv(trim($row['custodian'], '{}'), ',', '"', '\\');
                                        $manager = htmlspecialchars($row['dept_manager'] ?? '', ENT_QUOTES);

                                    ?>
                    <tr>
                        <td class=<?= $color_class ?>><?= $row_num++ ?></td>
                        <td class=<?= $color_class ?>>
                            <button id="button-9" data-toggle="modal" data-target="#modal<?= $dept_id ?>"><?= $dept_id ?></button>
                        </td>

                        <td class=<?= $color_class ?>><?= $dept_name ?></td>
                        <td class=<?= $color_class ?>>
                            <?php
                                        $count = count($custodian);
                                        foreach ($custodian as $index => $cust) {
                                            if ($count - 1 == $index) {
                                                echo $cust;
                                            } else {
                                                echo $cust . ',';
                                            }
                                        }
                            ?>
                        </td>
                        <td class=<?= $color_class ?>> <?= $manager ?></td>
                    </tr>
                <?php } ?>
            </tbody>

        </table>
    </section>
    <?php
    foreach ($result as $row) {
        // Escape values for safety
        $dept_id = htmlspecialchars($row['dept_id'] ?? '', ENT_QUOTES);
        $dept_name = htmlspecialchars($row['dept_name'] ?? '', ENT_QUOTES);
        //$custodian = htmlspecialchars($row['custodian'] ?? '', ENT_QUOTES);
        $custodian = str_getcsv(trim($row['custodian'], '{}'), ',', '"', '\\');
        $old_custs = implode(',', $custodian);
        $manager = htmlspecialchars($row['dept_manager'] ?? '', ENT_QUOTES);
    ?>
        <div id="modal<?= $dept_id ?>" class="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $dept_id; ?>" aria-hidden="true">
            <!-- Modal content -->
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel<?= $dept_id; ?>">Department Details for <?= $dept_id ?></h5>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="crud/change_dept_info.php" method="post">
                            <input type="hidden" id="old_dept" name="old_dept" value="<?= $dept_id ?>">
                            <input type="hidden" id="old_name" name="old_name" value="<?= $dept_name ?>">
                            <input type="hidden" id="old_cust" name="old_cust" value="<?= $old_custs ?>">
                            <input type="hidden" id="old_manager" name="old_manager" value="<?= $manager ?>">
                            <label for="asset_tag">Department ID:</label>
                            <input type="text" id="dept" name="new_dept" value="<?= $dept_id ?>">
                            <br>
                            <label for="name">Department Name:</label>
                            <input type="text" id="name" name="name" value="<?= $dept_name ?>">
                            <br>
                            <?php
                            $custs = $custodian[0];

                            foreach ($custodian as $index => $cust) {
                                if ($index === 0) continue;
                                $custs .= ',' . $cust;
                            } ?>
                            <label for="room_loc">Custodian(s):</label>
                            <input type="text" id="cust" name="cust" value="<?= $custs ?>">
                            <br>

                            <label for="location">Manager:</label>
                            <input type="text" id="manager" name="manager" value="<?= $manager ?>">
                            <br>
                            <button type="submit" value="<?= $dept_id ?>" name="delete-dept">Delete Department</button>
                            <button type="submit" name="dept">Update Department</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
<?php
}

function user_layout($result, $row_num)
{ ?>
    <section class="is-ajax" id="is-ajax" style="opacity: 0;">
        <table id="asset-table">
            <thead>
                <tr>
                    <th class='row-even'>Row</th>
                    <th class='row-even'>User Name</th>
                    <th class='row-even'>Email</th>
                    <th class='row-even'>Role</th>
                    <th class='row-even'>Last Login</th>
                    <th class='row-even'>First Name</th>
                    <th class='row-even'>Last Name</th>
                    <th class='row-even'>Department ID(s)</th>
                </tr>

            </thead>
            <tbody id="table-body"><?php
                                    foreach ($result as $row) {
                                        $color_class = ($row_num % 2 === 0) ? 'row-even' : 'row-odd';

                                        // Escape values for safety
                                        $username = htmlspecialchars($row['username'] ?? '', ENT_QUOTES);
                                        $email = htmlspecialchars($row['email'] ?? '', ENT_QUOTES);
                                        $u_role = htmlspecialchars($row['u_role'] ?? '', ENT_QUOTES);
                                        $last_login = htmlspecialchars($row['last_login'] ?? '', ENT_QUOTES);
                                        $f_name = htmlspecialchars($row['f_name'] ?? '', ENT_QUOTES);
                                        $l_name = htmlspecialchars($row['l_name'] ?? '', ENT_QUOTES);
                                        $dept = trim($row['dept_id'], '{}');

                                    ?>
                    <tr style="min-height:90px;">
                        <td class=<?= $color_class ?>><?= $row_num++ ?></td>
                        <td class=<?= $color_class ?>>
                            <button id="button-9" data-toggle="modal" data-target="#modal<?= $username ?>"><?= $username ?></button>
                        </td>

                        <td class=<?= $color_class ?>><?= $email ?></td>

                        <td class=<?= $color_class ?>><?= $u_role ?></td>
                        <td class=<?= $color_class ?>> <?= $last_login ?></td>
                        <td class=<?= $color_class ?>><?= $f_name ?></td>
                        <td class=<?= $color_class ?>><?= $l_name ?></td>
                        <td class=<?= $color_class ?>><?= $dept ?></td>
                    </tr>
                <?php } ?>
            </tbody>

        </table>
    </section>
    <?php
    foreach ($result as $row) {
        // Escape values for safety
        $username = htmlspecialchars($row['username'] ?? '', ENT_QUOTES);
        $email = htmlspecialchars($row['email'] ?? '', ENT_QUOTES);
        $u_role = htmlspecialchars($row['u_role'] ?? '', ENT_QUOTES);
        $last_login = htmlspecialchars($row['last_login'] ?? '', ENT_QUOTES);
        $f_name = htmlspecialchars($row['f_name'] ?? '', ENT_QUOTES);
        $l_name = htmlspecialchars($row['l_name'] ?? '', ENT_QUOTES);
        $dept = trim($row['dept_id'], '{}');
        $dept2 = explode(',', $dept);
        $dept = str_replace('"', '', $dept);

    ?>
        <div id="modal<?= $username ?>" class="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $username; ?>" aria-hidden="true">
            <!-- Modal content -->
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel<?= $username; ?>">Department Details for <?= $username ?></h5>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="crud/change_user_info.php" method="post">
                            <input type="hidden" id="old_email" name="old_email" value="<?= $email ?>">
                            <input type="hidden" id="old_name" name="old_name" value="<?= $username ?>">
                            <input type="hidden" id="old_dept" name="old_dept" value="<?= $dept ?>">
                            <input type="hidden" id="old_role" name="old_role" value="<?= $u_role ?>">
                            <input type="hidden" id="old_f_name" name="old_f_name" value="<?= $f_name ?>">
                            <input type="hidden" id="old_l_name" name="old_l_name" value="<?= $l_name ?>">

                            <label for="asset_tag">Username:</label>
                            <input type="text" id="username" name="username" value="<?= $username ?>" readonly>
                            <br>
                            <label for="name">Email:</label>
                            <input type="text" id="email" name="email" value="<?= $email ?>">
                            <br>

                            <label for="room_loc">User Role:</label>
                            <input type="text" id="old_role" name="old_role" value="<?= $u_role ?>" readonly>
                            <br>
                            <label for="room_loc">Change Role to:</label>
                            <select id="role" name="role">
                                <option value="management">Management</option>
                                <option value="admin">Admin</option>
                                <option value="user" selected>User</option>
                                <option value="custodian">Custodian</option>
                            </select>
                            <br>
                            <label for="location">Last Login:</label>
                            <input type="text" id="last_login" name="last_login" value="<?= $last_login ?>" readonly>
                            <br>
                            <label for="location">First Name:</label>
                            <input type="text" id="f_name" name="f_name" value="<?= $f_name ?>" readonly>
                            <br>
                            <label for="location">Last Name:</label>
                            <input type="text" id="l_name" name="l_name" value="<?= $l_name ?>" readonly>
                            <br>
                            <label for="location">Department ID:</label>
                            <input type="text" id="dept_ids" name="dept_ids" value="<?= $dept ?>">
                            <br>
                            <button type="submit" value="<?= $email ?>" name="delete-user">Delete User</button>
                            <button data-tag="<? $username ?>" type="submit" name="user">Update User</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
<?php
}
?>
<script>
    function hideUI(type, tag) {
        const form = document.querySelectorAll('.' + type + '-' + tag);
        form.forEach(row => {
            row.style.display = 'none';
        });
    }

    function showFormType(form) {
        const tag = form.dataset.tag;
        const type_value = form.value;
        if (type_value === '') {
            hideUI('lsd', tag);
            hideUI('transfer', tag);
            hideUI('psr', tag);
            hideUI('check', tag);
        }

        console.log(tag, type_value);
        if (type_value === 'check-out' || type_value === 'check-in') {
            document.querySelector('.check-' + tag).style.display = 'table-caption';
            const someone_else = document.getElementById('who-' + tag);
            console.log(someone_else);
            someone_else.addEventListener('change', () => {
                if (someone_else.value === 'someone-else') {
                    document.getElementById('someone-else-' + tag).style.display = 'table-caption';
                    document.getElementById('someone-else-label-' + tag).style.display = 'table-caption';
                } else {
                    document.getElementById('someone-else-' + tag).style.display = 'none';
                    document.getElementById('someone-else-label-' + tag).style.display = 'none';
                }
            });
            hideUI('lsd', tag);
            hideUI('transfer', tag);
            hideUI('psr', tag);
        }
        if (type_value === 'transfer') {
            document.querySelector('.transfer-' + tag).style.display = 'table-caption';
            const transfer_form_type_sel = document.getElementById("transfer-form-type-" + tag);
            hideUI('check', tag);
            const in_building = document.getElementById("transfer-in-bldg-" + tag);
            in_building.addEventListener('change', () => {
                if (in_building.value === 'No') {
                    document.querySelector('.transfer-bldg-text-' + tag).style.display = 'table-caption';
                } else {
                    document.querySelector('.transfer-bldg-text-' + tag).style.display = 'none';
                }
            });
            transfer_form_type_sel.addEventListener('change', () => {
                const transfer_form_type = transfer_form_type_sel.value;
                console.log(transfer_form_type);
                if (transfer_form_type === '') {
                    document.querySelector(".dept-change-" + tag).style.display = 'none';
                    document.querySelector(".room-dept-change-" + tag).style.display = 'none';

                } else if (transfer_form_type === 'location') {
                    document.querySelector(".room-dept-change-" + tag).style.display = 'table-caption';
                    document.querySelector(".dept-change-" + tag).style.display = 'none';

                } else if (transfer_form_type === 'dept') {
                    document.querySelector(".dept-change-" + tag).style.display = 'table-caption';
                    document.querySelector(".room-dept-change-" + tag).style.display = 'table-caption';
                }
            });
        }

    }
    async function sendForm(type) {
        console.log('sendForm called', type);
        const tag = type.dataset.tag;
        const form_type = document.getElementById('form-select-' + tag).value;
        if (form_type === 'check-out') {
            const check_type = document.getElementById("who-" + tag).value;
            const borrower = document.getElementById("someone-else-" + tag).value;
            const condition = document.getElementById("check-condition-" + tag).value;
            const notes = document.getElementById("check-notes-" + tag).value;
            const item_type = document.getElementById('check-item-type-' + tag).value;
            url = 'https://dataworks-7b7x.onrender.com/kualiAPI/write/kuali-search.php';
            const out_res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tag: tag,
                    form: 'check-out',
                    borrower: borrower,
                    condition: condition,
                    item_type: item_type,
                    notes: notes,
                    who: check_type
                })
            });
            if (!out_res.ok) {
                const text = await out_res.text();
                throw new Error(`HTTPS ${out_res.status}: ${text}`);
            } else {
                const clone = out_res.clone();
                try {
                    const data = await out_res.json();
                    console.log('Check-Out data reponse: ', data);
                } catch {
                    const text = await clone.text();
                    console.log('Check-Out text response: ', text);
                }
            }

        } else if (form_type === 'check-in') {
            const check_type = document.getElementById("who-" + tag).value;
            const borrower = document.getElementById("someone-else-" + tag).value;
            const condition = document.getElementById("check-condition-" + tag).value;
            const notes = document.getElementById("check-notes-" + tag).value;
            const item_type = document.getElementById('check-item-type-' + tag).value;
            url = 'https://dataworks-7b7x.onrender.com/kualiAPI/write/kuali-search.php';
            const in_res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tag: tag,
                    who: check_type,
                    borrower: borrower,
                    condition: condition,
                    notes: notes,
                    item_type: item_type,
                    form: 'check-in'
                })
            });
            if (!in_res.ok) {
                const text = await in_res.text();
                throw new Error(`HTTPS ${in_res.status}: ${text}`);
            } else {
                const clone = in_res.clone();
                try {
                    const data = await in_res.json();
                    console.log('Check-In data reponse: ', data);
                } catch {
                    const text = await clone.text();
                    console.log('Check-In text response: ', text);
                }
            }

        } else if (form_type === 'transfer') {
            const transfer_form_type = document.getElementById("transfer-form-type-" + tag).value;
            const in_bldg = document.getElementById("transfer-in-bldg-" + tag).value;
            const bldg_text = document.getElementById('transfer-bldg-text-' + tag).value;

            const dept = document.getElementById("transfer-dept-" + tag).value;
            const bldg = document.getElementById("transfer-bldg-" + tag).value;
            const room = document.getElementById("transfer-room-" + tag).value;
            const why = document.getElementById("transfer-why-" + tag).value;
            const notes = document.getElementById("transfer-notes-" + tag).value;
            url = 'https://dataworks-7b7x.onrender.com/kualiAPI/write/kuali-search.php';
            const trans_res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tag: tag,
                    dept_name: dept,
                    in_bldg: in_bldg,
                    where: bldg_text,
                    bldg: bldg,
                    room: room,
                    why: why,
                    form: 'transfer',
                    notes: notes,
                    form_type: transfer_form_type
                })
            });
            if (!trans_res.ok) {
                const text = await trans_res.text();
                throw new Error(`HTTPS ${trans_res.status}: ${text}`);
            } else {
                const clone = trans_res.clone();
                try {
                    const data = await trans_res.json();
                    console.log('Transfer data reponse: ', data);
                } catch {
                    const text = await clone.text();
                    console.log('Transfer text response: ', text);
                }
            }

        }
    }
</script>