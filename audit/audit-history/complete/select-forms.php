<?php
require_once "../../../config.php";
check_auth();

$selectedCustodian = $_SESSION['selected_custodian'] ?? null;
$selectedCustodianIndex = $_SESSION['selected_custodian_index'] ?? null;

$select_dept = "SELECT dept_name FROM department WHERE dept_id = :id";
$stmt = $dbh->prepare($select_dept);
$stmt->execute([":id" => $_SESSION['info'][2]]);
$dept_name = $stmt->fetchColumn();
$audit_id = $_SESSION['info'][5];

$select_bldgs = "SELECT bldg_name, bldg_id FROM bldg_table ORDER BY bldg_name";
$stmt = $dbh->query($select_bldgs);
$bldgs_info = $stmt->fetchAll();

$select_depts = "SELECT dept_name, dept_id FROM department ORDER BY dept_name";
$stmt = $dbh->query($select_depts);
$depts_info = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Form Submissions <?= $_SESSION['info'][2] . ' ' . $dept_name ?></title>
    <?php include_once "../../../navbar.php"; ?>
    <?php include_once "../../../ui/toast.php"; ?>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #001a4d 0%, #003da5 50%, #ffffff 100%);
            margin: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 61, 165, 0.2);
            overflow: hidden;
            margin-top: 2rem;
            margin-bottom: 2rem;
            border: 3px solid #FFB81C;
        }

        .header {
            background: linear-gradient(135deg, #003DA5 0%, #001a4d 100%);
            color: white;
            padding: 2.5rem;
            text-align: center;
            border-bottom: 4px solid #FFB81C;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FFB81C 0%, #003DA5 50%, #FFB81C 100%);
        }

        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-wrapper {
            margin-top: 0;
            overflow-x: auto;
            padding: 2rem;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }

        .table thead tr {
            background: linear-gradient(90deg, #003DA5 0%, #FFB81C 100%);
            color: white;
        }

        .table thead th {
            padding: 1.25rem 1rem;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        .table thead th:first-child {
            border-top-left-radius: 12px;
        }

        .table thead th:last-child {
            border-top-right-radius: 12px;
        }

        .table tbody tr {
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody tr:hover {
            background: linear-gradient(90deg, #f0f9ff 0%, #fffbf0 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 61, 165, 0.15);
            border-left: 4px solid #FFB81C;
        }

        .table tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .table tbody td:not([style*="display: none"]) {
            opacity: 1;
            animation: slideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Updated form inputs to use CSUB colors */
        select,
        input[type="text"],
        textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #FFB81C;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f9f9f9;
            font-family: inherit;
        }

        select:focus,
        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #003DA5;
            background: #1fa958;
            box-shadow: 0 0 0 4px rgba(0, 61, 165, 0.1);
        }

        select:hover,
        input[type="text"]:hover,
        textarea:hover {
            border-color: #003DA5;
            background: #1FA958;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        /* Updated forms dropdown to use CSUB colors */
        .forms-needed {
            background: linear-gradient(135deg, #003DA5 0%, #001a4d 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            border-color: #FFB81C;
        }

        .forms-needed option {
            background: white;
            color: #003DA5;
        }

        label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #003DA5;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .error-label {
            color: #d32f2f;
            font-size: x-small;
        }

        /* Updated submit button to use CSUB colors */
        #submit {
            background: linear-gradient(135deg, #FFB81C 0%, #FFA500 100%);
            color: #003DA5;
            border: 2px solid #003DA5;
            padding: 1rem 3rem;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            margin: 2rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(255, 184, 28, 0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        #submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 61, 165, 0.3);
            background: linear-gradient(135deg, #FFA500 0%, #FFB81C 100%);
        }

        #submit:active {
            transform: translateY(-1px);
        }

        .form-field-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-field-group[style*="display: none"] {
            opacity: 0;
            transform: translateY(-10px);
        }

        .form-field-group:not([style*="display: none"]) {
            opacity: 1;
            transform: translateY(0);
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            background: linear-gradient(135deg, #003DA5 0%, #001a4d 100%);
            color: #FFB81C;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .table {
                font-size: 0.8rem;
            }

            .table thead th,
            .table tbody td {
                padding: 0.75rem 0.5rem;
            }

            #submit {
                padding: 0.875rem 2rem;
                margin: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <datalist id="bldg-list">
        <?php foreach ($bldgs_info as $bldg) { ?>
            <option value='<?= $bldg['bldg_name'] ?>'>
            <?php } ?>
    </datalist>
    <datalist id="dept-list">
        <?php foreach ($depts_info as $dept) { ?>
            <option value='<?= $dept['dept_name'] ?>'>
            <?php } ?>
    </datalist>
    <div class="container">
        <div class="header">
            <h1>Form Submissions - <?= $_SESSION['info'][2] . ' ' . $_SESSION['info'][3] ?></h1>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Asset Tag</th>
                        <th>Description</th>
                        <th>Tag Status</th>
                        <th>Audit Note</th>
                        <th>Form</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['data'] as $index => $row) { ?>
                        <tr class="row-<?= $row['Tag Number'] ?>">
                            <td style="font-weight: 700;background-color: #e5F3Fd;"><?= $row['Unit'] ?></td>
                            <td style="font-weight: 600;background-color: #e5F3Fd;"><?= $row['Tag Number'] ?></td>
                            <td style="font-weight: 600;background-color: #e5F3Fd;"><?= $row['Descr'] ?></td>
                            <td style="font-weight: 600;background-color: #e5F3Fd;"><?= $row['Tag Status'] ?></td>
                            <td style="font-weight: 600;background-color: #e5F3Fd;"><?= $row['Found Note'] ?></td>
                            <td style="font-weight: 600;background-color: #e5F3Fd;">
                                <select name="form-type" id="form-<?= $row['Tag Number'] ?>" data-tag="<?= $row['Tag Number'] ?>" class="forms-needed">
                                    <option value="">No Form Needed</option>
                                    <?php if (!empty($row['Found Room Tag'])) { ?>
                                        <option value="bulk-transfer">Bulk Transfer</option>
                                    <?php } ?>
                                    <option value="psr">Property Survey Report</option>
                                    <option value="lsd">Equipment Loss/Stolen/Destroyed</option>
                                    <option value="check-out">Check Out</option>
                                    <option value="check-in">Check In</option>
                                    <option value="transfer">Transfer</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="psr-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Disposition Code</label>
                                    <select id="psr-code-<?= $row['Tag Number'] ?>">
                                        <option value="UNIVERSAL WASTE — SALVAGE DEALER, RECYCLER (E-WASTE)">UNIVERSAL WASTE — SALVAGE DEALER, RECYCLER (E-WASTE)</option>
                                        <option value="VALUELESS UNABLE TO BE RECYCLED (TO BE LEGALLY/SAFELY DISPOSED OF)">VALUELESS UNABLE TO BE RECYCLED (TO BE LEGALLY/SAFELY DISPOSED OF)</option>
                                        <option value="SHIPPED TO SCRAP / SALVAGE DEALER (TO BE RECYCLED) NOTE: FOR E-WASTE USE # 10">SHIPPED TO SCRAP / SALVAGE DEALER (TO BE RECYCLED) NOTE: FOR E-WASTE USE # 10</option>
                                        <option value"LOST, STOLEN OR DESTROYED (REFER TO SAM SECTION 8643 FOR INSTRUCTIONS)">LOST, STOLEN OR DESTROYED (REFER TO SAM SECTION 8643 FOR INSTRUCTIONS)</option>
                                        <option value="TO BE CANABALIZED (SALVAGED FOR PARTS)">TO BE CANABALIZED (SALVAGED FOR PARTS)</option>
                                        <option value="DONATION TO AN ELIGIBLE PUBLIC SCHOOL, PUBLIC SCHOOL DISTRICT OR ELIGIBLE ORGANIZATION  (SEE SAM SECTION 3520.5)">DONATION TO AN ELIGIBLE PUBLIC SCHOOL, PUBLIC SCHOOL DISTRICT OR ELIGIBLE ORGANIZATION (SEE SAM SECTION 3520.5)</option>
                                        <option value="SHIP TO PROPERTY REUSE PROGRAM (NO POOR OR JUNK MATERIAL)">SHIP TO PROPERTY REUSE PROGRAM (NO POOR OR JUNK MATERIAL)</option>
                                        <option value="DONATION OF COMPUTERS FOR SCHOOLS PROGRAM">DONATION OF COMPUTERS FOR SCHOOLS PROGRAM</option>
                                        <option value="SALE (SEE SAM SECTION 3520)">SALE (SEE SAM SECTION 3520)</option>
                                        <option value="TRADE-IN (SHOW TRADE-IN PRICE OFFERED)">TRADE-IN (SHOW TRADE-IN PRICE OFFERED)</option>
                                    </select>
                                    <label class="error-label" id='psr-code-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="psr-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Reason for Disposal</label>
                                    <input type="text" id="psr-reason-<?= $row['Tag Number'] ?>" placeholder="Enter reason...">
                                    <label class="error-label" id='psr-reason-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <?php if (!in_array((int)$audit_id, [4, 5, 6])) { ?>
                                <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                                    <div class="form-field-group">
                                        <label>Submitting For</label>
                                        <select id="lsd-who-<?= $row['Tag Number'] ?>">
                                            <option value="Myself">Myself</option>
                                            <option value="someone-else">Someone Else</option>
                                        </select>
                                        <label class="error-label" id='lsd-who-feedback-<?= $row['Tag Number'] ?>'></label>
                                    </div>
                                </td>
                                <td class="lsd-fill-<?= $row['Tag Number'] ?>" style="display:none;">
                                    <div class="form-field-group">
                                        <label>Borrower Email</label>
                                        <input type="text" id="lsd-fill-for-<?= $row['Tag Number'] ?>" placeholder="Enter email...">
                                        <label class="error-label" id='lsd-fill-for-feedback-<?= $row['Tag Number'] ?>'></label>
                                    </div>
                                </td>
                                <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                                    <div class="form-field-group">
                                        <label>Position</label>
                                        <select id="lsd-position-<?= $row['Tag Number'] ?>">
                                            <option value="Staff/Faculty">Staff/Faculty</option>
                                            <option value="Student">Student</option>
                                        </select>
                                        <label class="error-label" id='lsd-position-feedback-<?= $row['Tag Number'] ?>'></label>
                                    </div>
                                </td>
                                <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                                    <div class="form-field-group">
                                        <label>Condition</label>
                                        <select id="lsd-condition-<?= $row['Tag Number'] ?>">
                                            <option value="Lost">Lost</option>
                                            <option value="Stolen">Stolen</option>
                                            <option value="Destroyed">Destroyed</option>
                                        </select>
                                        <label class="error-label" id='lsd-condition-feedback-<?= $row['Tag Number'] ?>'></label>
                                    </div>
                                </td>
                                <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                                    <div class="form-field-group">
                                        <label>Detailed Narrative</label>
                                        <textarea id="lsd-narrative-<?= $row['Tag Number'] ?>" placeholder="Provide detailed description..."></textarea>
                                    </div>
                                    <label class="error-label" id='lsd-narrative-feedback-<?= $row['Tag Number'] ?>'></label>
                                </td>
                                <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                                    <div class="form-field-group">
                                        <label>Date Discovered Missing</label>
                                        <input type="date" id="lsd-date-discovered-<?= $row['Tag Number'] ?>">
                                    </div>
                                    <label class="error-label" id='lsd-date-discovered-feedback-<?= $row['Tag Number'] ?>'></label>
                                </td>
                        </tr>
                        <tr>
                            <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Reported to UPD?</label>
                                    <select id="upd-<?= $row['Tag Number'] ?>">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                    <label class="error-label" id='upd-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Item Type</label>
                                    <select id="item-type-<?= $row['Tag Number'] ?>">
                                        <option value=""></option>
                                        <option value="IT Equipment">IT Equipment</option>
                                        <option value="Instructional Equipment">Instructional Equipment</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <label class="error-label" id='item-type-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="lsd-it-equip-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Describe the item lost</label>
                                    <input type="text"
                                        id="upd-describe-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-describe-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-it-equip-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Did this equipment have any confidential information stored on it?</label>
                                    <select id="lsd-it-equip-confidential-<?= $row['Tag Number'] ?>">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                    <label class="error-label" id='lsd-it-equip-confidential-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-it-equip-confidential-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Describe as completely as possible the nature of the confideial data that was stored on this equipment</label>
                                    <input type="text" placeholder="i.e. Names, Social Security Number's, Date of Bird, Driver License #'s, Credit Card #'s, etc"
                                        id="lsd-it-equip-confidential-input-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='lsd-it-equip-confidential-input-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-it-equip-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Was the confidential data stored on this asset encrypted and/or password protected?</label>
                                    <select id="lsd-it-equip-encrypted-<?= $row['Tag Number'] ?>">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                    <label class="error-label" id='lsd-it-equip-encrypted-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-it-equip-encrypted-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Please describe how the data was protected</label>
                                    <input type="text" id="lsd-it-equip-encrypted-input-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='lsd-it-equip-encrypted-input-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Date Reported</label>
                                    <input type="date" id="upd-date-reported-<?= $row['Tag Number'] ?>">
                                </div>
                                <label class="error-label" id='upd-date-reported-feedback-<?= $row['Tag Number'] ?>'></label>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Time Reported</label>
                                    <input type="time" id="upd-time-reported-<?= $row['Tag Number'] ?>">
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Date Last Seen</label>
                                    <input type="date" id="upd-date-last-seen-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-date-last-seen-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Time Last Seen</label>
                                    <input type="time" id="upd-time-last-seen-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-time-last-seen-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>By Whom?</label>
                                    <input type="text" id="upd-by-whom-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-by-whom-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Location</label>
                                    <input type="text" id="upd-location-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-location-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Was the area/room secured?</label>
                                    <select id='upd-secured-<?= $row['Tag Number'] ?>'>
                                        <option value='No'>No</option>
                                        <option value='Yes'>Yes</option>
                                    </select>
                                    <label class="error-label" id='upd-secured-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-access-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Who has access keys?</label>
                                    <input type="text" id="upd-access-keys-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-access-keys-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Does your department have and assigned staff member responsible for equipment?</label>
                                    <select id="upd-assigned-staff-<?= $row['Tag Number'] ?>">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                    <label class="error-label" id='upd-assigned-staff-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-yes-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Who?</label>
                                    <input type="text" id="upd-who-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-who-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>What steps were taken to recover the asset?</label>
                                    <input type="text" id="upd-recovery-steps-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-recovery-steps-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>What precautions are in effect to prevent loss or theft?</label>
                                    <input type="text" id="upd-precautions-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-precautions-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>If this equipment was used off-campus who authorized its use?</label>
                                    <input type="text" id="upd-authorized-<?= $row['Tag Number'] ?>">
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>What security provided off campus?</label>
                                    <input type="text" id="upd-security-<?= $row['Tag Number'] ?>">
                                </div>
                            </td>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Was the loss or theft reported to local authorities?</label>
                                    <select id="upd-reported-<?= $row['Tag Number'] ?>">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                    <label class="error-label" id='upd-reported-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="lsd-upd-explain-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Explain</label>
                                    <input type="text" id="upd-explain-<?= $row['Tag Number'] ?>">
                                    <label class="error-label" id='upd-explain-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="lsd-upd-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Do you have insurance?</label>
                                    <select id="upd-insurance-<?= $row['Tag Number'] ?>">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                            </td>
                            <td class="lsd-upd-insurance-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Company Name</label>
                                    <input type="text" id="upd-company-<?= $row['Tag Number'] ?>">
                                </div>
                            </td>
                            <td class="lsd-upd-insurance-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Street</label>
                                    <input type="text" id="upd-street-<?= $row['Tag Number'] ?>">
                                </div>
                            </td>
                            <td class="lsd-upd-insurance-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>City</label>
                                    <input type="text" id="upd-city-<?= $row['Tag Number'] ?>">
                                </div>
                            </td>
                            <td class="lsd-upd-insurance-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Zip Code</label>
                                    <input type="text" id="upd-zip-<?= $row['Tag Number'] ?>">
                                </div>
                            </td>
                            <td class="lsd-upd-insurance-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>State</label>
                                    <input type="text" id="upd-state-<?= $row['Tag Number'] ?>">
                                </div>
                            </td>
                        <?php } ?>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>" style="display:none;">
                            <div class="form-field-group">
                                <label>Checking Out For</label>
                                <select class="who-<?= $row['Tag Number'] ?>">
                                    <option value="Myself">Myself</option>
                                    <option value="someone-else">Someone Else</option>
                                </select>
                                <label class="error-label" id='who-feedback-<?= $row['Tag Number'] ?>'></label>
                            </div>
                        </td>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>" style="display:none;">
                            <div class="form-field-group someone-else-<?= $row['Tag Number'] ?>" style="display:none;">
                                <label>Borrower Name</label>
                                <input type="text" name="full-name" id="someone-else-<?= $row['Tag Number'] ?>" placeholder="Full name of borrower">
                                <label class="error-label" id='someone-else-feedback-<?= $row['Tag Number'] ?>'></label>
                            </div>
                        </td>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>" style="display:none;">
                            <div class="form-field-group">
                                <label>Condition</label>
                                <select id="check-condition-<?= $row['Tag Number'] ?>">
                                    <option value="New">New</option>
                                    <option value="Good">Good</option>
                                    <option value="Used">Used</option>
                                    <option value="Damaged">Damaged</option>
                                </select>
                                <label class="error-label" id='check-condition-feedback-<?= $row['Tag Number'] ?>'></label>
                            </div>
                        </td>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>" style="display:none;">
                            <div class="form-field-group">
                                <label>Notes</label>
                                <textarea id="check-notes-<?= $row['Tag Number'] ?>" placeholder="Additional notes..."></textarea>
                            </div>
                        </td>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>" style="display:none;">
                            <div class="form-field-group">
                                <label>Item Type</label>
                                <select id="check-item-type-<?= $row['Tag Number'] ?>">
                                    <option value="Laptop">Laptop</option>
                                    <option value="Desktop">Desktop</option>
                                    <option value="Tablet">Tablet</option>
                                </select>
                                <label class="error-label" id='check-item-type-feedback-<?= $row['Tag Number'] ?>'></label>
                            </div>
                        </td>
                        </tr>
                        <?php $spa_regex = '/^SPP\d+/';
                        if (preg_match($spa_regex, $row['Tag Number'])) { ?>
                            <tr>
                                <td class="transfer-<?= $row['Tag Number'] ?>" style="display:none;">
                                    <div class="form-field-group">
                                        <label>Private Investigator (PI) For This Asset</label>
                                        <input type="text" name="full-name" id="pi-<?= $row['Tag Number'] ?>" placeholder="Full name of PI">
                                        <label class="error-label" id='transfer-feedback-<?= $row['Tag Number'] ?>'></label>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td class="transfer-form-type-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Is This a</label>
                                    <select id="transfer-form-type-<?= $row['Tag Number'] ?>">
                                        <option value=""></option>
                                        <option value="location">Business/Room/Location change (Business Unit stays the same)</option>
                                        <option value="dept">From one department to another department</option>
                                    </select>
                                </div>
                            </td>
                            <td class="transfer-form-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Is this equipment kept inside a building?</label>
                                    <select id="transfer-loc-<?= $row['Tag Number'] ?>">
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </td>
                            <td class="transfer-bldg-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Where is your equipment stored, parked, or housed?</label>
                                    <input type="text" name="transfer-bldg-text" id="transfer-bldg-text-<?= $row['Tag Number'] ?>" placeholder="">
                                    <label class="error-label" id='transfer-bldg-text-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="transfer-form-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Notes</label>
                                    <input type="text" name="transfer-notes" id="transfer-notes-<?= $row['Tag Number'] ?>" placeholder="Notes">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="transfer-dept-change-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>Why?</label>
                                    <input type="text" name="transfer-notes" id="transfer-why-<?= $row['Tag Number'] ?>" placeholder="Why?">
                                    <label class="error-label" id='transfer-why-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="transfer-dept-change-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>New Custodian Name/Email</label>
                                    <input type="text" name="transfer-notes" id="transfer-new-cust-<?= $row['Tag Number'] ?>" placeholder="New Custodian">
                                    <label class="error-label" id='transfer-new-cust-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="transfer-dept-change-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>New Department</label>
                                    <input type="search" list="dept-list" name="transfer-notes" id="transfer-dept-location-<?= $row['Tag Number'] ?>" placeholder="New Department">
                                    <label class="error-label" id='transfer-dept-location-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="transfer-bldg-change-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>New Building</label>
                                    <input type="search" name="transfer-notes" list="bldg-list" id="transfer-bldg-location-<?= $row['Tag Number'] ?>" placeholder="New Building">
                                    <label class="error-label" id='transfer-bldg-location-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                            <td class="transfer-bldg-change-<?= $row['Tag Number'] ?>" style="display:none;">
                                <div class="form-field-group">
                                    <label>New Room</label>
                                    <input type="text" name="transfer-notes" id="transfer-room-location-<?= $row['Tag Number'] ?>" placeholder="New Room">
                                    <label class="error-label" id='transfer-room-location-feedback-<?= $row['Tag Number'] ?>'></label>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center;">
            <label class="error-label" id='sub-label'></label>
            <button class="submit-btn" id="submit">Submit Forms</button>
        </div>
    </div>
    <script>
        function binarySearch(arr, target) {
            let left = 0;
            let right = arr.length - 1;
            while (left <= right) {
                const mid = Math.floor((left + right) / 2);
                if (arr[mid].bldg_name === target) {
                    return true;
                } else if (arr[mid].bldg_name < target) {
                    left = mid + 1;
                } else {
                    right = mid - 1;
                }
            }
            return false;
        }
        const bldgs = <?= json_encode($bldgs_info); ?>;
        const depts = <?= json_encode($depts_info); ?>;
        const document_audit_id = parseInt(<?= json_encode([$audit_id]) ?>);

        function hideUI(type, tag) {
            //console.log(type, tag);
            const form = document.querySelectorAll('.' + type + '-' + tag);
            form.forEach(el => {
                el.style.display = 'none';
            });
            return;
        }

        function showUI(type, tag) {
            const form = document.querySelectorAll('.' + type + '-' + tag);
            form.forEach(el => {
                el.style.display = 'table-cell';
            });
        }

        function hideAll(tag) {
            hideUI('transfer-bldg-change', tag);
            hideUI('transfer-dept-change', tag);
            hideUI('transfer-form', tag);
            hideUI('transfer-form-type', tag);
            hideUI('lsd-it-equip-encrypted', tag);
            hideUI('lsd-it-equip-confidential', tag);
            hideUI('lsd-it-equip', tag);
            hideUI('lsd-upd-explain', tag);
            hideUI('lsd-upd-access', tag);
            hideUI('check-out', tag);
            hideUI('check-in', tag);
            hideUI('lsd', tag);
            hideUI('psr', tag);
            hideUI('bulk-transfer', tag);
            hideUI('lsd-upd-yes', tag);
            hideUI('lsd-upd', tag);
            hideUI('lsd-upd-yes', tag);
            hideUI('lsd-upd-insurance', tag);
            hideUI('lsd-fill-', tag);
            hideUI('transfer-', tag);
            return;
        }

        function displayError(type, reason, check, form_status) {
            console.log(type, reason, check, form_status);
            if (check === '') {
                document.getElementById(type).textContent = reason;
                return false;
            } else {
                document.getElementById(type).textContent = '';
            }

            return form_status;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const dept_id = <?= json_encode($_SESSION['info'][2], true) ?>;
            const audit_id = <?= json_encode($_SESSION['info'][3], true) ?>;
            const forms_needed = document.querySelectorAll('.forms-needed');

            let transfer_count = 0;
            const form_length = forms_needed.length;

            forms_needed.forEach(form_type => {
                form_type.addEventListener('change', () => {
                    //console.log('Changed input', form_type, form_type.value);
                    const tag = form_type.dataset.tag;
                    //.log(form_type.value, tag);
                    if (form_type.value === '') {
                        hideAll(tag);
                        return;
                    }
                    const form_class = document.querySelectorAll('.' + form_type.value + '-' + form_type.dataset.tag);
                    if (form_type.value === 'check-out') {
                        hideAll(tag);
                        someone_else = document.querySelector('.who-' + tag);
                        someone_else.addEventListener('change', () => {
                            //console.log(someone_else.value);
                            if (someone_else.value === 'someone-else') {
                                document.querySelector('.someone-else-' + tag).style.display = 'block';
                            } else {
                                document.querySelector('.someone-else-' + tag).style.display = 'none';
                            }
                        });
                    }

                    if (form_type.value === 'check-in') {
                        hideAll(tag);
                        const someone_else = document.querySelector('.who-' + tag);
                        someone_else.addEventListener('change', () => {
                            //console.log(someone_else.value);
                            if (someone_else.value === 'someone-else') {
                                document.querySelector('.someone-else-' + tag).style.display = 'block';
                            } else {
                                document.querySelector('.someone-else-' + tag).style.display = 'none';
                            }
                        });
                    }

                    if (form_type.value === 'psr') {
                        hideAll(tag);
                        hideUI('transfer-', tag);
                        hideUI('lsd-it-equip-encrypted', tag);
                        hideUI('lsd-it-equip-confidential', tag);
                        hideUI('lsd-upd-explain', tag);
                        hideUI('lsd-upd-access', tag);
                        hideUI('lsd-it-equip', tag);
                        hideUI('check-out', tag);
                        hideUI('lsd', tag);
                        hideUI('check-in', tag);
                        hideUI('bulk-transfer', tag);
                        hideUI('lsd-upd-yes', tag);
                        hideUI('lsd-upd', tag);
                        hideUI('lsd-upd-yes', tag);
                        hideUI('lsd-upd-insurance', tag);
                        hideUI('lsd-fill-', tag);
                    }

                    if (form_type.value === 'lsd' && (document_audit_id !== 4 && document_audit_id !== 5 && document_audit_id !== 6)) {
                        hideAll(tag);
                        const tag_regex = /^SP\d+/g;
                        if (tag_regex.test(tag)) {
                            //showUI('transfer-', tag);
                        }
                        const upd = document.getElementById('upd-' + tag);
                        upd.addEventListener('change', () => {
                            //console.log(upd.value);
                            if (upd.value === 'Yes') {
                                showUI('lsd-upd', tag);
                                const assigned = document.getElementById('upd-assigned-staff-' + tag);
                                assigned.addEventListener('change', () => {
                                    if (assigned.value === 'Yes') {
                                        showUI('lsd-upd-yes', tag);
                                    } else {
                                        hideUI('lsd-upd-yes', tag);
                                    }
                                });
                                const insurance = document.getElementById('upd-insurance-' + tag);
                                insurance.addEventListener('change', () => {
                                    if (insurance.value === 'Yes') {
                                        showUI('lsd-upd-insurance', tag);
                                    } else {
                                        hideUI('lsd-upd-insurance', tag);
                                    }
                                });
                                const secured = document.getElementById('upd-secured-' + tag);
                                secured.addEventListener('change', () => {
                                    if (secured.value === 'Yes') {
                                        showUI('lsd-upd-access', tag);
                                    } else {
                                        hideUI('lsd-upd-access', tag);
                                    }
                                });
                                const local = document.getElementById('upd-reported-' + tag);
                                local.addEventListener('change', () => {
                                    if (local.value === 'Yes') {
                                        showUI('lsd-upd-explain', tag);
                                    } else {
                                        hideUI('lsd-upd-explain', tag);
                                    }
                                });
                            } else {}
                        });
                        const someone_else = document.getElementById('lsd-who-' + tag);
                        someone_else.addEventListener('change', () => {
                            //console.log(someone_else.value);
                            if (someone_else.value === 'someone-else') {
                                document.querySelector('.lsd-fill-' + tag).style.display = 'table-cell';
                                //console.log(document.querySelector('.lsd-fill-' + tag));
                            } else {
                                document.querySelector('.lsd-fill-' + tag).style.display = 'none';
                            }
                            hideUI('check-out', tag);
                            hideUI('check-in', tag);
                            hideUI('psr', tag);
                            hideUI('bulk-transfer', tag);
                        });

                        const it_equip = document.getElementById('item-type-' + tag);
                        //console.log(it_equip);
                        it_equip.addEventListener('change', () => {
                            //console.log(it_equip.value);
                            if (it_equip.value === 'IT Equipment') {
                                //console.log(it_equip.value);
                                showUI('lsd-it-equip', tag);
                                const confidential = document.getElementById('lsd-it-equip-confidential-' + tag);
                                confidential.addEventListener('change', () => {
                                    if (confidential.value === 'Yes') {
                                        showUI('lsd-it-equip-confidential', tag);
                                    } else {
                                        hideUI('lsd-it-equip-confidential', tag);
                                    }
                                });
                                const encrypted = document.getElementById('lsd-it-equip-encrypted-' + tag);
                                encrypted.addEventListener('change', () => {
                                    if (encrypted.value === 'Yes') {
                                        showUI('lsd-it-equip-encrypted', tag);
                                    } else {
                                        hideUI('lsd-it-equip-encrypted', tag);
                                    }
                                });
                            } else {
                                hideUI('lsd-it-equip', tag);
                                hideUI('lsd-it-equip-encrypted', tag);
                                hideUI('lsd-it-equip-confidential', tag);
                            }
                        });
                    }
                    if (form_type.value === 'transfer') {
                        transfer_count++;
                        hideAll(tag);
                        showUI('transfer-form', tag);
                        showUI('transfer-form-type', tag);
                        const in_bldg = document.getElementById('transfer-loc-' + tag);
                        in_bldg.addEventListener('change', () => {
                            if (in_bldg.value === 'No') {
                                showUI('transfer-bldg', tag);
                            } else {
                                hideUI('transfer-bldg', tag);
                            }
                        });
                        const transfer_type = document.getElementById('transfer-form-type-' + tag);
                        transfer_type.addEventListener('change', () => {
                            if (transfer_type.value === 'dept') {
                                showUI('transfer-dept-change', tag);
                                showUI('transfer-bldg-change', tag);
                                /*
                                    showUI('receiving-custodian', tag);
                                showUI('transfer-why', tag);
                                 */
                            } else if (transfer_type.value === 'location') {
                                showUI('transfer-bldg-change', tag);
                            } else {
                                hideUI('transfer-dept-change', tag);
                                hideUI('transfer-bldg-change', tag);
                                /*
                                hideUI('receiving-custodian', tag);
                                hideUI('transfer-why', tag);
                                 */
                            }
                        });


                    }



                    if (form_type.value === 'bulk-transfer') {
                        hideAll(tag);
                        const tag_regex = /^SP\d+/g;
                        if (tag_regex.test(tag)) {
                            //showUI('transfer-', tag);
                        }
                    }

                    form_class.forEach(el => {
                        el.style.display = 'table-cell';
                    });
                });
            });

            // ------------------------------------------------------------------------------------------
            transfer_count = 0;
            const btn = document.getElementById("submit").addEventListener("click", async () => {
                let valid_forms = true;
                let bulk_t_tags = [],
                    t_tags = [],
                    psr_tags = [],
                    out_tags = [],
                    in_tags = [],
                    transfer_dept_array = [],
                    transfer_location_array = [],
                    lsd_tags = [];
                const forms_needed = document.querySelectorAll('.forms-needed');
                const dept_id = <?= json_encode($_SESSION['info'][2]) ?>;
                const audit_id = <?= json_encode($_SESSION['info'][5]) ?>;

                forms_needed.forEach(async (type) => {
                    const tag = type.dataset.tag;
                    const val = type.value;
                    if (val == 'psr') {
                        const reason = document.getElementById('psr-reason-' + type.dataset.tag).value;
                        const code = document.getElementById('psr-code-' + type.dataset.tag).value;
                        valid_forms = displayError('psr-reason-feedback-' + tag, 'Reason cannot be empty', reason, valid_forms);
                        valid_forms = displayError('psr-code-feedback-' + tag, 'Code cannot be empty', code, valid_forms);
                    } else if (val == 'check-out') {
                        const check_type = document.querySelector('.who-' + type.dataset.tag)?.value;
                        valid_forms = displayError('who-feedback-' + tag, 'Submitter type cannot be empty', check_type, valid_forms);

                        let borrower = '';
                        if (check_type === 'someone-else') {
                            let borrower = '';
                            borrower = document.getElementById('someone-else-' + type.dataset.tag)?.value;
                            valid_forms = displayError('someone-else-feedback-' + tag, 'Borrower cannot be empty', borrower, valid_forms);
                            const split_name = borrower.split(' ');
                            const email_regex = /(@csub.edu)/;

                            if (check_type === 'someone-else' && split_name.length < 2 && !email_regex.test(borrower)) {
                                valid_forms = displayError('someone-else-feedback-' + tag, 'Invalid Borrower', '', valid_forms);
                            } else {
                                valid_forms = displayError('someone-else-feedback-' + tag, 'Invalid Borrower', 'good', valid_forms);
                            }
                        }
                        const condition = document.getElementById('check-condition-' + type.dataset.tag)?.value;
                        valid_forms = displayError('check-condition-feedback-' + tag, 'Condition cannot be empty', condition, valid_forms);
                        const notes = document.getElementById('check-notes-' + type.dataset.tag)?.value;
                        const item_type = document.getElementById('check-item-type-' + type.dataset.tag)?.value;
                        valid_forms = displayError('check-item-type-feedback-' + tag, 'Item Type cannot be empty', item_type, valid_forms);

                    } else if (val == 'check-in') {
                        const check_type = document.querySelector('.who-' + type.dataset.tag)?.value;
                        let borrower = '';
                        if (check_type !== 'Myself') {
                            borrower = document.getElementById('someone-else-' + type.dataset.tag)?.value;
                            valid_forms = displayError('someone-else-feedback-' + tag, 'Borrower cannot be empty', borrower, valid_forms);
                            const split_name = borrower.split(' ');
                            const email_regex = /(@csub.edu)/;

                            if (check_type === 'someone-else' && split_name.length < 2 && !email_regex.test(borrower)) {
                                valid_forms = displayError('someone-else-feedback-' + tag, 'Invalid Borrower', '', valid_forms);
                            } else {
                                valid_forms = displayError('someone-else-feedback-' + tag, 'Invalid Borrower', 'good', valid_forms);
                            }
                        }
                        const condition = document.getElementById('check-condition-' + type.dataset.tag)?.value;
                        valid_forms = displayError('check-condition-feedback-' + tag, 'Condition cannot be empty', condition, valid_forms);
                        const notes = document.getElementById('check-notes-' + type.dataset.tag)?.value;
                        const item_type = document.getElementById('check-item-type-' + type.dataset.tag)?.value;
                        valid_forms = displayError('check-item-type-feedback-' + tag, 'Item Type cannot be empty', item_type, valid_forms);


                    } else if (val === 'lsd') {
                        if (document_audit_id !== 4 && document_audit_id !== 5 && document_audit_id !== 6) {
                            // REQUIRED
                            const who = document.getElementById('lsd-who-' + type.dataset.tag)?.value;
                            let borrower = '';
                            if (who !== 'Myself') {
                                borrower = document.getElementById('lsd-fill-for-' + type.dataset.tag)?.value;
                                valid_forms = displayError('lsd-fill-for-feedback-' + tag, 'Borrower cannot be empty', borrower, valid_forms);
                                const split_name = borrower.split(' ');
                                const email_regex = /(@csub.edu)/;

                                if (split_name.length < 2 && !email_regex.test(borrower)) {
                                    valid_forms = displayError('lsd-fill-for-feedback-' + tag, 'Invalid Borrower', '', valid_forms);
                                } else {}
                            }
                            // REQUIRED
                            const position = document.getElementById('lsd-position-' + type.dataset.tag).value;
                            valid_forms = displayError('lsd-position-feedback-' + type.dataset.tag, 'Please select the position.', position, valid_forms);

                            // REQUIRED
                            const lsd = document.getElementById('lsd-condition-' + type.dataset.tag).value;
                            valid_forms = displayError('lsd-condition-feedback-' + type.dataset.tag, 'Please specify the current condition.', lsd, valid_forms);

                            // REQUIRED
                            const reason = document.getElementById('lsd-narrative-' + type.dataset.tag).value;
                            valid_forms = displayError('lsd-narrative-feedback-' + type.dataset.tag, 'Please provide a reason or narrative.', reason, valid_forms);

                            // REQUIRED
                            const upd = document.getElementById('upd-' + type.dataset.tag).value;
                            valid_forms = displayError('upd-feedback-' + type.dataset.tag, 'Please specify if an update is required.', upd, valid_forms);

                            // REQUIRED
                            const item_type = document.getElementById('item-type-' + type.dataset.tag).value;
                            valid_forms = displayError('item-type-feedback-' + type.dataset.tag, 'Please select the item type.', item_type, valid_forms);

                            // REQUIRED
                            const date_discovered = document.getElementById('lsd-date-discovered-' + type.dataset.tag).value;
                            valid_forms = displayError('lsd-date-discovered-feedback-' + type.dataset.tag, 'Please select a date.', position, valid_forms);

                            if (upd === 'Yes') {
                                // REQUIRED
                                const date_reported = document.getElementById('upd-date-reported-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-date-reported-feedback-' + type.dataset.tag, 'Please enter the date reported.', date_reported, valid_forms);

                                const time_reported = document.getElementById('upd-time-reported-' + type.dataset.tag).value;

                                // REQUIRED
                                const date_last_seen = document.getElementById('upd-date-last-seen-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-date-last-seen-feedback-' + type.dataset.tag, 'Please enter the date the item was last seen.', date_last_seen, valid_forms);

                                // REQUIRED
                                const time_last_seen = document.getElementById('upd-time-last-seen-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-time-last-seen-feedback-' + type.dataset.tag, 'Please enter the time the item was last seen.', time_last_seen, valid_forms);

                                // REQUIRED
                                const by_whom = document.getElementById('upd-by-whom-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-by-whom-feedback-' + type.dataset.tag, 'Please specify who last saw or handled the item.', by_whom, valid_forms);

                                // REQUIRED
                                const upd_location = document.getElementById('upd-location-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-location-feedback-' + type.dataset.tag, 'Please enter the location of the update.', upd_location, valid_forms);

                                // REQUIRED
                                const secured = document.getElementById('upd-secured-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-secured-feedback-' + type.dataset.tag, 'Please indicate if the item is secured.', secured, valid_forms);

                                // REQUIRED
                                if (secured === 'Yes') {
                                    const access_keys = document.getElementById('upd-access-keys-' + type.dataset.tag).value;
                                    valid_forms = displayError('upd-access-keys-feedback-' + type.dataset.tag, 'Please specify who has access keys.', access_keys, valid_forms);
                                }

                                // REQUIRED
                                const assigned_staff = document.getElementById('upd-assigned-staff-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-assigned-staff-feedback-' + type.dataset.tag, 'Please specify the assigned staff member.', assigned_staff, valid_forms);

                                // REQUIRED
                                if (assigned_staff === 'Yes') {
                                    const who_assigned = document.getElementById('upd-who-' + type.dataset.tag).value;
                                    valid_forms = displayError('upd-who-feedback-' + type.dataset.tag, 'Please indicate who assigned the task.', who_assigned, valid_forms);
                                }

                                // REQUIRED
                                const recovery_steps = document.getElementById('upd-recovery-steps-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-recovery-steps-feedback-' + type.dataset.tag, 'Please describe the recovery steps taken.', recovery_steps, valid_forms);

                                // REQUIRED
                                const precautions = document.getElementById('upd-precautions-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-precautions-feedback-' + type.dataset.tag, 'Please describe the precautions in place.', precautions, valid_forms);

                                const authorized = document.getElementById('upd-authorized-' + type.dataset.tag).value;
                                const security = document.getElementById('upd-security-' + type.dataset.tag).value;

                                // REQUIRED
                                const reported = document.getElementById('upd-reported-' + type.dataset.tag).value;
                                valid_forms = displayError('upd-reported-feedback-' + type.dataset.tag, 'Please indicate if this has been reported.', reported, valid_forms);

                                // REQUIRED
                                if (reported === 'Yes') {
                                    const explain = document.getElementById('upd-explain-' + type.dataset.tag).value;
                                    valid_forms = displayError('upd-explain-feedback-' + type.dataset.tag, 'Please provide an explanation.', explain, valid_forms);
                                }

                                const insurance = document.getElementById('upd-insurance-' + type.dataset.tag).value;
                                const company = document.getElementById('upd-company-' + type.dataset.tag).value;
                                const street = document.getElementById('upd-street-' + type.dataset.tag).value;
                                const city = document.getElementById('upd-city-' + type.dataset.tag).value;
                                const zip = document.getElementById('upd-zip-' + type.dataset.tag).value;
                                const state = document.getElementById('upd-state-' + type.dataset.tag).value;

                                if (item_type === 'IT Equipment') {
                                    // REQUIRED
                                    const describe_asset = document.getElementById('upd-describe-' + type.dataset.tag).value;
                                    valid_forms = displayError('upd-describe-feedback-' + type.dataset.tag, 'Please describe the asset in detail.', describe_asset, valid_forms);

                                    // REQUIRED
                                    const encrypted = document.getElementById('lsd-it-equip-encrypted-' + type.dataset.tag).value;
                                    valid_forms = displayError('lsd-it-equip-encrypted-feedback-' + type.dataset.tag, 'Please indicate if the device is encrypted.', encrypted, valid_forms);

                                    if (encrypted === 'Yes') {
                                        // REQUIRED
                                        const encrypted_data = document.getElementById('lsd-it-equip-encrypted-input-' + type.dataset.tag).value;
                                        valid_forms = displayError('lsd-it-equip-encrypted-input-feedback-' + type.dataset.tag, 'Please specify what data is encrypted.', encrypted_data, valid_forms);
                                    }

                                    // REQUIRED
                                    const confidential = document.getElementById('lsd-it-equip-confidential-' + type.dataset.tag).value;
                                    valid_forms = displayError('lsd-it-equip-confidential-feedback-' + type.dataset.tag, 'Please indicate if the device contains confidential data.', confidential, valid_forms);

                                    if (confidential === 'Yes') {
                                        // REQUIRED
                                        const confidential_data = document.getElementById('lsd-it-equip-confidential-input-' + type.dataset.tag).value;
                                        valid_forms = displayError('lsd-it-equip-confidential-input-feedback-' + type.dataset.tag, 'Please specify what confidential data is stored.', confidential_data, valid_forms);
                                    }
                                }
                            }
                        }
                    } else if (val === 'transfer') {
                        const in_bldg = document.getElementById('transfer-loc-' + tag).value;
                        const notes = document.getElementById('transfer-notes-' + tag).value;
                        const where = document.getElementById('transfer-bldg-text-' + tag).value;
                        const transfer_type = document.getElementById('transfer-form-type-' + type.dataset.tag);
                        if (transfer_type.value === '') {
                            valid_forms = displayError('transfer-form-type-feedback-' + type.dataset.tag, 'Transfer Type Cannot Be Null.', transfer_type.value, valid_forms);
                        } else if (transfer_type.value === 'location') {
                            // FINISH CHECKING IF NEW ROOM AND BLDG ARE NOT EMPTY
                            const bldg_change = document.getElementById('transfer-bldg-location-' + type.dataset.tag).value;
                            if (bldg_change === '') {
                                valid_forms = displayError('transfer-bldg-location-feedback-' + type.dataset.tag, 'New Building cannot be empty.', bldg_change, valid_forms);
                            }
                            if (bldg_change !== '' && binarySearch(bldgs, bldg_change)) {
                                valid_forms = displayError('transfer-bldg-location-feedback-' + type.dataset.tag, 'New Buiding does not exist in database.', bldg_change, valid_forms);
                            }
                            const room_change = document.getElementById('transfer-room-location-' + type.dataset.tag).value;
                            if (room_change === '') {
                                valid_forms = displayError('transfer-room-location-feedback-' + type.dataset.tag, 'New Room cannot be empty.', room_change, valid_forms);
                            }
                            transfer_location_array.push({
                                'in_bldg': in_bldg,
                                'notes': notes,
                                'where': where,
                                'tag': tag,
                                'bldg': bldg_change,
                                'room': room_change
                            });

                        } else if (transfer_type.value === 'dept') {
                            // FINISH CHECKING IF NEW DEPT IS NOT EMPTY
                            const bldg_change = document.getElementById('transfer-bldg-location-' + tag).value;
                            const room_change = document.getElementById('transfer-room-location-' + tag).value;
                            if (bldg_change === '' && room_change !== '') {
                                valid_forms = displayError('transfer-bldg-location-feedback-' + type.dataset.tag, 'New Building cannot be empty.', bldg_change, valid_forms);
                            } else if (bldg_change !== '' && room_change === '') {
                                valid_forms = displayError('transfer-room-location-feedback-' + type.dataset.tag, 'New Room cannot be empty.', room_change, valid_forms);
                            }
                            if (bldg_change !== '' && binarySearch(bldgs, bldg_change)) {
                                valid_forms = displayError('transfer-bldg-location-feedback-' + type.dataset.tag, 'New Buiding does not exist in database.', bldg_change, valid_forms);
                            }

                            const dept_change = document.getElementById('transfer-dept-location-' + tag).value;
                            const tag_regex = /^SP\d+/g;
                            if (dept_change === '') {
                                valid_forms = displayError('transfer-dept-location-feedback-' + type.dataset.tag, 'New Department cannot be empty.', dept_change, valid_forms);
                            }
                            if (binarySearch(depts, dept_change)) {
                                valid_forms = displayError('transfer-dept-location-feedback-' + type.dataset.tag, 'Department is not in database.', dept_change, valid_forms);
                            }
                            const why = document.getElementById('transfer-why-' + type.dataset.tag).value;
                            if (why === '') {
                                valid_forms = displayError('transfer-why-feedback-' + type.dataset.tag, 'Reason Why cannot be empty.', why, valid_forms);
                            }
                            const new_cust = document.getElementById('transfer-new-cust-' + type.dataset.tag).value;
                            if (new_cust === '') {
                                valid_forms = displayError('transfer-new-cust-feedback-' + type.dataset.tag, 'New custodian cannot be empty.', new_cust, valid_forms);
                            }
                            transfer_dept_array.push({
                                'in_bldg': in_bldg,
                                'where': where,
                                'tag': tag,
                                'bldg': bldg_change,
                                'room': room_change,
                                'why': why,
                                'new_cust': new_cust,
                                'notes': notes,
                                'dept_name': dept_change
                            });
                        }
                    }
                });


                // ------------------------------------------------------------------------------------------
                if (valid_forms) {
                    displayError('sub-label', 'Missing form information.', 'good', valid_forms);
                    const form_submitted = await fetch('https://dataworks-7b7x.onrender.com/audit/audit-history/complete/change_db.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            dept_id: dept_id,
                            audit_id: audit_id
                        })
                    });

                    if (!form_submitted.ok) {
                        const text = await form_submitted.text();
                        throw new Error(`HTTP ${form_submitted.status}: ${text}`);
                    } else {
                        const clone = form_submitted.clone();
                        try {
                            const forms_data = await clone.text();
                            console.log("Update DB response (JSON):", forms_data);
                            const parse = JSON.parse(forms_data);
                            if (parse.status === 'Ok') {
                                showToast('Department Exists', 4000);
                            } else {
                                valid_forms = false;
                                showToast(parse.reason, 5000);

                            }
                        } catch (error) {
                            console.error("Error: ", error.message);
                        }
                    }
                } else {
                    displayError('sub-label', 'Missing form information.', '', valid_forms);
                }

                if (valid_forms) {
                    const btn = document.querySelector('.submit-btn');
                    btn.style.display = 'none';
                    forms_needed.forEach(async (type) => {
                        const val = type.value;
                        const tag = type.dataset.tag;

                        if (val == 'bulk-transfer') {
                            bulk_t_tags.push(type.dataset.tag);
                        } else if (val == 'psr') {
                            const reason = document.getElementById('psr-reason-' + type.dataset.tag).value;
                            const code = document.getElementById('psr-code-' + type.dataset.tag).value;
                            psr_tags.push({
                                tag: type.dataset.tag,
                                reason: reason,
                                code: code
                            });
                        } else if (val == 'check-out') {
                            out_tags.push(type.dataset.tag);
                            url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/check-forms.php";
                            const check_type = document.querySelector('.who-' + type.dataset.tag)?.value;
                            let borrower = '';
                            if (check_type === 'someone-else') {
                                borrower = document.getElementById('someone-else-' + type.dataset.tag)?.value;
                                const split_name = borrower.split(' ');
                                if (check_type === 'someone-else' && split_name.length < 2) {
                                    //document.getElementById('check-in-borrower-msg-' + type.dataset.tag).textContent = 'Incorrect Name Format';
                                }
                            }
                            const condition = document.getElementById('check-condition-' + type.dataset.tag)?.value;
                            const notes = document.getElementById('check-notes-' + type.dataset.tag)?.value;
                            const item_type = document.getElementById('check-item-type-' + type.dataset.tag)?.value;

                            const out_res = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    form_type: 'check-out',
                                    item_type: item_type,
                                    tag: type.dataset.tag,
                                    who: check_type,
                                    borrower: borrower,
                                    condition: condition,
                                    dept_id: dept_id,
                                    audit_id: audit_id,
                                    notes: notes
                                })
                            });

                            if (!out_res.ok) {
                                const text = await out_res.text();
                                throw new Error(`HTTP ${out_res.status}: ${text}`);
                            } else {
                                const clone = out_res.clone();
                                try {
                                    //const out_res_data = await clone.json();
                                    const out_res_data = await clone.text();
                                    console.log("Check out response (JSON):", out_res_data);
                                    const parse = JSON.parse(out_res_data);
                                    console.log(parse);
                                    if (parse.status === 'Check in/out Ok') {
                                        showToast('Check Out Form Submitted', 5000);
                                        console.log('success');
                                    } else {
                                        showToast('Check Out Form Failed', 5000);
                                        console.log('failed');
                                    }
                                } catch {}
                                hideUI('row', type.dataset.tag);
                                hideAll(type.dataset.tag);
                            }
                        } else if (val == 'check-in') {
                            url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/check-forms.php";
                            const check_type = document.querySelector('.who-' + type.dataset.tag)?.value;
                            let borrower = '';
                            if (check_type === 'someone-else') {
                                borrower = document.getElementById('someone-else-' + type.dataset.tag)?.value;
                                const split_name = borrower.split(' ');
                                if (check_type === 'someone-else' && split_name.length < 2) {
                                    //document.getElementById('check-in-borrower-msg-' + type.dataset.tag).textContent = 'Incorrect Name Format';
                                }
                            }
                            const condition = document.getElementById('check-condition-' + type.dataset.tag)?.value;
                            const notes = document.getElementById('check-notes-' + type.dataset.tag)?.value;
                            const item_type = document.getElementById('check-item-type-' + type.dataset.tag)?.value;


                            const in_res = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    form_type: 'check-in',
                                    item_type: item_type,
                                    tag: type.dataset.tag,
                                    who: check_type,
                                    borrower: borrower,
                                    condition: condition,
                                    dept_id: dept_id,
                                    audit_id: audit_id,
                                    notes: notes
                                })
                            });

                            if (!in_res.ok) {
                                const text = await in_res.text();
                                throw new Error(`HTTP ${in_res.status}: ${text}`);
                            } else {
                                const clone = in_res.clone();
                                try {
                                    const in_res_data = await clone.text();
                                    //const in_res_data = await clone.json();
                                    console.log("Check in response (JSON):", in_res_data);
                                    const parse = JSON.parse(in_res_data);
                                    console.log(parse);
                                    if (parse.status === 'Check in/out Ok') {
                                        showToast('Check In Form Submitted', 5000);
                                        console.log('success');
                                    } else {
                                        showToast('Check In Form Failed', 5000);
                                        console.log('failed');
                                    }
                                } catch {}
                                hideUI('row', type.dataset.tag);
                                hideAll(type.dataset.tag);
                            }
                        } else if (val === 'lsd') {
                            if (document_audit_id !== 4 && document_audit_id !== 5 && document_audit_id !== 6) {
                                const who = document.getElementById('lsd-who-' + type.dataset.tag)?.value;
                                const borrower = document.getElementById('lsd-fill-for-' + type.dataset.tag)?.value;
                                const position = document.getElementById('lsd-position-' + type.dataset.tag)?.value;
                                const lsd = document.getElementById('lsd-condition-' + type.dataset.tag)?.value;
                                const reason = document.getElementById('lsd-narrative-' + type.dataset.tag)?.value;
                                const upd = document.getElementById('upd-' + type.dataset.tag)?.value;
                                const item_type = document.getElementById('item-type-' + type.dataset.tag)?.value;
                                const date_reported = document.getElementById('upd-date-reported-' + type.dataset.tag)?.value;
                                const time_reported = document.getElementById('upd-time-reported-' + type.dataset.tag)?.value;
                                const date_last_seen = document.getElementById('upd-date-last-seen-' + type.dataset.tag)?.value;
                                const time_last_seen = document.getElementById('upd-time-last-seen-' + type.dataset.tag)?.value;
                                const by_whom = document.getElementById('upd-by-whom-' + type.dataset.tag)?.value;
                                const upd_location = document.getElementById('upd-location-' + type.dataset.tag)?.value;
                                const secured = document.getElementById('upd-secured-' + type.dataset.tag)?.value;
                                const access_keys = document.getElementById('upd-access-keys-' + type.dataset.tag)?.value;
                                const assigned_staff = document.getElementById('upd-assigned-staff-' + type.dataset.tag)?.value;
                                const who_assigned = document.getElementById('upd-who-' + type.dataset.tag)?.value;
                                const recovery_steps = document.getElementById('upd-recovery-steps-' + type.dataset.tag)?.value;
                                const precautions = document.getElementById('upd-precautions-' + type.dataset.tag)?.value;
                                const authorized = document.getElementById('upd-authorized-' + type.dataset.tag).value;
                                const security = document.getElementById('upd-security-' + type.dataset.tag).value;
                                const reported = document.getElementById('upd-reported-' + type.dataset.tag).value;
                                const explain = document.getElementById('upd-explain-' + type.dataset.tag).value;
                                const insurance = document.getElementById('upd-insurance-' + type.dataset.tag).value;
                                const company = document.getElementById('upd-company-' + type.dataset.tag).value;
                                const street = document.getElementById('upd-street-' + type.dataset.tag).value;
                                const city = document.getElementById('upd-city-' + type.dataset.tag).value;
                                const zip = document.getElementById('upd-zip-' + type.dataset.tag).value;
                                const state = document.getElementById('upd-state-' + type.dataset.tag).value;
                                const describe_asset = document.getElementById('upd-describe-' + type.dataset.tag).value;
                                const encrypted = document.getElementById('lsd-it-equip-encrypted-' + type.dataset.tag).value;
                                const encrypted_data = document.getElementById('lsd-it-equip-encrypted-input-' + type.dataset.tag).value;
                                const confidential = document.getElementById('lsd-it-equip-confidential-' + type.dataset.tag).value;
                                const confidential_data = document.getElementById('lsd-it-equip-confidential-input-' + type.dataset.tag).value;
                                const date_discovered = document.getElementById('lsd-date-discovered-' + type.dataset.tag).value;

                                url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/lsd.php";
                                const lsd_res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        tag: type.dataset.tag,
                                        who: who,
                                        borrower: borrower,
                                        date_discovered: date_discovered,
                                        position: position,
                                        lsd: lsd,
                                        reason: reason,
                                        dept_id: dept_id,
                                        audit_id: audit_id,
                                        upd: upd,
                                        item_type: item_type,
                                        date_reported: date_reported,
                                        time_reported: time_reported,
                                        date_last_seen: date_last_seen,
                                        time_last_seen: time_last_seen,
                                        by_whom: by_whom,
                                        upd_location: upd_location,
                                        secured: secured,
                                        access_keys: access_keys,
                                        assigned_staff: assigned_staff,
                                        who_assigned: who_assigned,
                                        recovery_steps: recovery_steps,
                                        precautions: precautions,
                                        authorized: authorized,
                                        security: security,
                                        reported: reported,
                                        explain: explain,
                                        insurance: insurance,
                                        company: company,
                                        street: street,
                                        city: city,
                                        zip: zip,
                                        state: state,
                                        encrypted: encrypted,
                                        describe_asset: describe_asset,
                                        encrypted_data: encrypted_data,
                                        confidential: confidential,
                                        confidential_data: confidential_data
                                    })
                                });
                                if (!lsd_res.ok) {
                                    const text = await lsd_res.text();
                                    throw new Error(`HTTP ${lsd_res.status}: ${text}`);
                                } else {
                                    const clone = lsd_res.clone();
                                    try {
                                        const lsd2_data = await clone.text();
                                        //const lsd2_data = await clone.json();
                                        console.log("Lsd response (JSON):", lsd2_data);
                                        const parse = JSON.parse(lsd2_data);
                                        console.log(parse);
                                        if (parse.status === 'Loss/Stolen/Dmg Ok') {
                                            showToast('Lost/Stolen/Dmg Form Submitted', 5000);
                                            console.log('success');
                                        } else {
                                            showToast('Lost/Stolen/Dmg Failed', 5000);
                                            console.log('failed');
                                        }
                                    } catch {}
                                    hideUI('row', type.dataset.tag);
                                    hideAll(type.dataset.tag);
                                }
                            } else {
                                url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/dw-lsd.php";
                                const lsd_res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        tag: type.dataset.tag,
                                        dept_id: dept_id,
                                        audit_id: audit_id
                                    })
                                });

                                if (!lsd_res.ok) {
                                    const text = await lsd_res.text();
                                    throw new Error(`HTTP ${lsd_res.status}: ${text}`);
                                } else {
                                    const clone = lsd_res.clone();
                                    try {
                                        const lsd_data = await clone.text();
                                        //const lsd_data = await clone.json();
                                        console.log("Lsd response (JSON):", lsd_data);
                                        const parse = JSON.parse(lsd_data);
                                        console.log(parse);
                                        if (parse.status === 'Loss/Stolen/Dmg Ok') {
                                            showToast('Lost/Stolen/Dmg Form Submitted', 5000);
                                            console.log('success');
                                        } else {
                                            showToast('Lost/Stolen/Dmg Failed', 5000);
                                            console.log('failed');
                                        }
                                    } catch {}
                                    hideUI('row', type.dataset.tag);
                                    hideAll(type.dataset.tag);
                                }
                            }
                        }
                    });


                    if (psr_tags.length !== 0) {
                        url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/psr.php";
                        const psr_res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                psr_tags,
                                dept_id: dept_id,
                                audit_id: audit_id
                            })
                        });

                        if (!psr_res.ok) {
                            const text = await psr_res.text();
                            throw new Error(`HTTP ${psr_res.status}: ${text}`);
                        } else {
                            const clone = psr_res.clone();
                            try {
                                const psr_data = await clone.text();
                                //const psr_data = await clone.json();
                                console.log("PSR response (JSON):", psr_data);
                                const parse = JSON.parse(psr_data);
                                console.log(parse);
                                if (parse.status === 'Property Survey Report Ok') {
                                    showToast('Property Suvery Report Form Submitted', 5000);
                                    console.log('success');
                                } else {
                                    showToast('Property Survey Report Form Failed', 5000);
                                    console.log('failed');
                                }
                            } catch {}
                            psr_tags.forEach(async (value) => {
                                const tag = (value && typeof value === 'object') ? value.tag : value;
                                if (!tag) {
                                    console.warn('Skipping bad tag:', value);
                                    return;
                                }
                                hideUI('row', tag);
                                hideAll(tag);
                            });
                        }
                    }
                    if (transfer_location_array.length !== 0) {
                        url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/transfer.php";
                        const transfer_res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                form_type: 'location',
                                tags: transfer_location_array,
                                dept_id: dept_id,
                                audit_id: audit_id
                            })
                        });

                        if (!transfer_res.ok) {
                            const text = await transfer_res.text();
                            throw new Error(`HTTP ${transfer_res.status}: ${text}`);
                        } else {
                            const clone = transfer_res.clone();
                            try {
                                const t2_data = await clone.text();
                                //const t2_data = await clone.json();
                                console.log("transfer dept response (text):", t2_data);
                                console.log("transfer dept response (text):", t2_data);
                                const parse = JSON.parse(t2_data);
                                console.log(parse);
                                if (parse.status === 'Transfer Ok') {
                                    showToast('Transfer Form Submitted', 5000);
                                    console.log('success');
                                } else {
                                    showToast('Transfer Form Failed', 5000);
                                    console.log('failed');
                                }
                            } catch {}
                            transfer_location_array.forEach(async (value) => {
                                hideUI('row', value.tag);
                                hideAll(value.tag);
                            });
                        }
                    }
                    if (transfer_dept_array.length !== 0) {
                        url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/transfer.php";
                        const transfer_res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                form_type: 'dept',
                                tags: transfer_dept_array,
                                dept_id: dept_id,
                                audit_id: audit_id
                            })
                        });

                        if (!transfer_res.ok) {
                            const text = await transfer_res.text();
                            throw new Error(`HTTP ${transfer_res.status}: ${text}`);
                        } else {
                            const clone = transfer_res.clone();
                            try {
                                //const t_data = await clone.json();
                                const t_data = await clone.text();
                                console.log("transfer dept response (text):", t_data);
                                const parse = JSON.parse(t_data);
                                console.log(parse);
                                if (parse.status === 'Transfer Ok') {
                                    showToast('Transfer Form Submitted', 5000);
                                    console.log('success');
                                } else {
                                    showToast('Transfer Form Failed', 5000);
                                    console.log('failed');
                                }
                            } catch {}
                            transfer_dept_array.forEach(async (value) => {
                                console.log(value);
                                hideUI('row', value.tag);
                                hideAll(value.tag);
                            });
                        }
                    }

                    if (bulk_t_tags.length !== 0) {
                        url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/bulk-transfer.php";
                        const bulk_t_res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                bulk_t_tags,
                                dept_id: dept_id,
                                audit_id: audit_id
                            })
                        });

                        if (!bulk_t_res.ok) {
                            const text = await bulk_t_res.text();
                            throw new Error(`HTTP ${bulk_t_res.status}: ${text}`);
                        } else {
                            const clone = bulk_t_res.clone();
                            try {
                                const b_t_data = await clone.text();
                                console.log("bulk-transfer response (JSON):", b_t_data);
                                const parse = JSON.parse(b_t_data);
                                console.log(parse);
                                if (parse.status === 'Bulk Transfer Ok') {
                                    showToast('Bulk Transfer Form Submitted', 5000);
                                    console.log('success');
                                } else {
                                    showToast('Bulk Transfer Form Failed', 5000);
                                    console.log('failed');
                                }
                            } catch {
                                //const b_t_data = await clone.json();
                                showToast('Bulk Transfer Form Failed', 5000);
                                console.log('failed');
                            }
                            bulk_t_tags.forEach((value) => {
                                hideUI('row', value);
                                hideAll(value);
                            });
                        }
                    }
                    btn.style.display = 'inline';
                    bulk_t_tags.length = 0, psr_tags.length = 0;
                    transfer_dept_array.length = 0;
                    transfer_location_array.length = 0;
                }
            });
        });
    </script>
</body>

</html>