<?php
require_once "../../../config.php";
check_auth();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Form Submissions <?= $_SESSION['info'][2] . ' ' . $_SESSION['info'][3] ?></title>
    <?php include_once "../../../navbar.php"; ?>
</head>

<body>
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
                <tr>
                    <td><?= $row['Unit'] ?></td>
                    <td><?= $row['Tag Number'] ?></td>
                    <td><?= $row['Descr'] ?></td>
                    <td><?= $row['Tag Status'] ?></td>
                    <td><?= $row['Found Note'] ?></td>
                    <td>
                        <select name="form-type" id="form-<?= $row['Tag Number'] ?>" data-tag="<?= $row['Tag Number'] ?>" class="forms-needed">
                            <option value="">No Form Needed</option>
                            <?php if (in_array($_SESSION['role'], ['admin', 'management'])) { ?>
                                <option value="bulk-transfer">Bulk Transfer</option>
                            <?php } ?>
                            <option value="psr">Property Survey Report</option>
                            <option value="lsd">Equipment Loss/Stolen/Destroyed</option>
                            <option value="check-out">Check Out</option>
                            <option value="check-in">Check In</option>
                        </select>
                    </td>
                    <!--PSR INFO FROM USER
    REASON FOR DISPOSTAL
    DISPOSITION CODE
    -->
                    <td class="psr-<?= $row['Tag Number'] ?>" style="display:none;">
                        <select id="psr-code-<?= $row['Tag Number'] ?>">
                            <option value="UNIVERSAL WASTE — SALVAGE DEALER, RECYCLER (E-WASTE)">UNIVERSAL WASTE — SALVAGE DEALER, RECYCLER (E-WASTE)</option>
                            <option value="VALUELESS UNABLE TO BE RECYCLED (TO BE LEGALLY/SAFELY DISPOSED OF)">VALUELESS UNABLE TO BE RECYCLED (TO BE LEGALLY/SAFELY DISPOSED O F)</option>
                            <option value="SHIPPED TO SCRAP / SALVAGE DEALER (TO BE RECYCLED) NOTE: FOR E-WASTE USE # 10">SHIPPED TO SCRAP / SALVAGE DEALER (TO BE RECYCLED) NOTE: FOR E-W ASTE USE # 10</option>
                        </select>
                    </td>
                    <td class="psr-<?= $row['Tag Number'] ?>" style="display:none;">
                        <input type="text" id="psr-reason-<?= $row['Tag Number'] ?>" placeholder="Reason for form...">
                    </td>
                    <!--EQUIP LSD INFO FROM USER
    MYSELF OR SOMEONE ELSE
    STAFF/FACULTY OR STUDENT
    NAME OF PERSON FILLING OUT FOR
    LOST STOLEN OR DESTROYED
    DETAILED NARRATIVE
    REPORTED TO PUBLIC SAFETY
     -->
                    <div>
                        <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                            <select id="lsd-who-<?= $row['Tag Number'] ?>">
                                <option value="Myself">Myself</option>
                                <option value="someone-else">I am initiating this submission on behalf of</option>
                            </select>
                        </td>
                        <td class="lsd-fill-<?= $row['Tag Number'] ?>" style="display:none;">
                            <input type="text" id="lsd-fill-for-<?= $row['Tag Number'] ?>" placeholder="Email of borrower...">
                        </td>
                        <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                            <select id="lsd-position-<?= $row['Tag Number'] ?>">
                                <option value="Staff/Faculty">Staff/Faculty</option>
                                <option value="Student">Student</option
                                    </select>
                        </td>
                        <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                            <select id="lsd-condition-<?= $row['Tag Number'] ?>">
                                <option value="Lost">Lost</option>
                                <option value="Stolen">Stolen</option>
                                <option value="Destroyed">Destroyed</option>
                            </select>
                        </td>
                        <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                            <textarea id="lsd-narrative-<?= $row['Tag Number'] ?>" placeholder="Detail Narrative">
</textarea>
                        </td>
                        <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                            <label>Reported to UPD?</label>
                            <select id="upd-<?= $row['Tag Number'] ?>">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </td>
                        <td class="lsd-<?= $row['Tag Number'] ?>" style="display:none;">
                            <select id="item-type-<?= $row['Tag Number'] ?>">
                                <option value="IT Equipment">IT Equipment</option>
                                <option value="Instructional Equipment">Instructional Equipment</option>
                                <option value="Other">Other</option>
                            </select>
                        </td>
                    </div>
                    <!-- CHECK IN/OUT -->
                    <div>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>" style="display:none;">
                            <select class="who-<?= $row['Tag Number'] ?>">
                                <option value="myself">Myself</option>
                                <option value="someone-else">Someone Else</option>
                            </select>
                        </td>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>">
                            <!-- SOMEONE ELSE -->
                            <input class="someone-else-<?= $row['Tag Number'] ?>" style="display:none;" type="text" name="full-name" id="full-name" placeholder="Full name of Borrower">
                        </td>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>" style="display:none;">
                            <select id="check-condition-<?= $row['Tag Number'] ?>">
                                <option value="new">New</option>
                                <option value="good">Good</option>
                                <option value="used">Used</option>
                                <option value="damanged">Damaged</option>
                            </select>
                        <td class="check-out-<?= $row['Tag Number'] ?> check-in-<?= $row['Tag Number'] ?>" style="display:none;">
                            <textarea id="check-notes-<?= $row['Tag Number'] ?>" placeholder="Notes..."></textarea>
                        </td>
                    </div>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <button id="submit">Submit Forms</button>
    <script>
        function hideUI(type, tag) {
            const form = document.querySelectorAll('.' + type + '-' + tag);
            form.forEach(el => {
                el.style.display = 'none';
            });

            return;
        };
        document.addEventListener("DOMContentLoaded", function() {
            const dept_id = <?= json_encode($_SESSION['info'][2], true) ?>;
            const audit_id = <?= json_encode($_SESSION['info'][3], true) ?>;
            const forms_needed = document.querySelectorAll('.forms-needed');
            forms_needed.forEach(form_type => {
                form_type.addEventListener('change', () => {
                    console.log('Changed input', form_type, form_type.value);
                    const tag = form_type.dataset.tag;
                    const form_class = document.querySelectorAll('.' + form_type.value + '-' + form_type.dataset.tag);
                    console.log(form_class, tag);
                    if (form_type.value === 'check-out') {
                        someone_else = document.querySelector('.who-' + tag);
                        someone_else.addEventListener('change', () => {
                            console.log(someone_else.value);
                            if (someone_else.value === 'someone-else') {
                                document.querySelector('.someone-else-' + tag).style.display = 'inline';
                            } else {
                                document.querySelector('.someone-else-' + tag).style.display = 'none';
                            }

                        });
                        hideUI('check-in', tag);
                        hideUI('lsd', tag);
                        hideUI('psr', tag);
                        hideUI('bulk-transfer', tag);
                    }
                    if (form_type.value === 'check-in') {
                        const someone_else = document.querySelector('.who-' + tag);
                        someone_else.addEventListener('change', () => {
                            console.log(someone_else.value);
                            if (someone_else.value === 'someone-else') {
                                document.querySelector('.someone-else-' + tag).style.display = 'inline';
                            } else {
                                document.querySelector('.someone-else-' + tag).style.display = 'none';
                            }

                        });
                        hideUI('check-in', tag);
                        hideUI('lsd', tag);
                        hideUI('psr', tag);
                        hideUI('bulk-transfer', tag);
                    }
                    if (form_type.value === 'psr') {
                        hideUI('check-in', tag);
                        hideUI('lsd', tag);
                        hideUI('check-out', tag);
                        hideUI('bulk-transfer', tag);
                    }
                    if (form_type.value === 'lsd') {
                        const someone_else = document.getElementById('lsd-who-' + tag);
                        someone_else.addEventListener('change', () => {
                            console.log(someone_else.value);
                            if (someone_else.value === 'someone-else') {
                                document.querySelector('lsd-fill-' + tag).style.display = 'inline';
                                console.log(document.querySelector('lsd-fill-' + tag));

                            } else {
                                document.querySelector('lsd-fill-' + tag).style.display = 'none';
                            }
                        });
                        hideUI('check-in', tag);
                        hideUI('check-out', tag);
                        hideUI('psr', tag);
                        hideUI('bulk-transfer', tag);
                    }
                    if (form_type.value === 'bulk-transfer') {
                        hideUI('check-in', tag);
                        hideUI('check-out', tag);
                        hideUI('psr', tag);
                        hideUI('lsd', tag);
                    }
                    form_class.forEach(el => {
                        el.style.display = 'inline';
                    });
                });
            });
            const btn = document.getElementById("submit").addEventListener("click", async () => {
                let bulk_t_tags = [],
                    t_tags = [],
                    b_psr_tags = [],
                    psr_tags = [],
                    out_tags = [],
                    in_tags = [],
                    lsd_tags = [];
                const forms_needed = document.querySelectorAll('.forms-needed');
                const dept_id = <?= json_encode($_SESSION['info'][2]) ?>;
                const audit_id = <?= json_encode($_SESSION['info'][5]) ?>;
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
                        const data = await form_submitted.json();
                        console.log("Update DB response (JSON):", data);
                    } catch {
                        const text = await clone.text();
                        console.log("Update DB response (text):", text);
                    }
                }


                forms_needed.forEach(async (type) => {
                    const val = type.value;
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
                        url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/check-out.php";
                        const check_type = document.querySelector('.who-' + type.dataset.tag).value;
                        const borrower = document.querySelector('.someonel-else-' + type.dataset.tag).value;
                        const condition = document.getElementById('check-condition-' + type.dataset.tag).value;
                        const notes = document.getElementById('check-notes-' + type.dataset.tag).value;
                        const split_name = borrower.split(' ');
                        if (check_type === 'Myself' && split_name < 2) {
                            document.getElementById('check-out-borrower-msg-' + type.dataset.tag).textContent = 'Incorrect Name Format';
                            exit;
                        }
                        const out_res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                form_type: 'Checking Out Equipment',
                                tag: type.dataset.tag,
                                type: check_type,
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
                                const data = await out_res.json();
                                console.log("Check out response (JSON):", data);
                            } catch {
                                const text = await clone.text();
                                console.log("Check out response (text):", text);
                            }
                        }
                    } else if (val == 'check-in') {
                        url = "https://dataworks-7b7x.onrender.com/kualiAPI/write/check-out.php";
                        const check_type = document.querySelector('.who-' + type.dataset.tag).value;
                        const borrower = document.querySelector('.someonel-else-' + type.dataset.tag).value;
                        const condition = document.getElementById('check-condition-' + type.dataset.tag).value;
                        const notes = document.getElementById('check-notes-' + type.dataset.tag).value;
                        const split_name = borrower.split(' ');
                        if (check_type === 'Myself' && split_name < 2) {
                            document.getElementById('check-in-borrower-msg-' + type.dataset.tag).textContent = 'Incorrect Name Format';
                            exit;
                        }
                        const in_res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                form_type: 'Returning Equipment',
                                tag: type.dataset.tag,
                                type: check_type,
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
                                const data = await in_res.json();
                                console.log("Check in response (JSON):", data);
                            } catch {
                                const text = await clone.text();
                                console.log("Check in response (text):", text);
                            }
                        }
                    } else if (val === 'lsd') {
                        const who = document.getElementById('lsd-who-' + type.dataset.tag).value;
                        const borrower = document.getElementById('lsd-fill-for-' + type.dataset.tag).value;
                        const position = document.getElementById('lsd-position-' + type.dataset.tag).value;
                        const lsd = document.getElementById('lsd-condition-' + type.dataset.tag).value;
                        const reason = document.getElementById('lsd-narrative-' + type.dataset.tag).value;
                        const upd = document.getElementById('upd-' + type.dataset.tag).value;
                        const item_type = document.getElementById('item-type-' + type.dataset.tag).value;

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
                                position: position,
                                lsd: lsd,
                                reason: reason,
                                dept_id: dept_id,
                                audit_id: audit_id,
                                upd: upd,
                                item_type: item_type
                            })
                        });
                        if (!lsd_res.ok) {
                            const text = await lsd_res.text();
                            throw new Error(`HTTP ${lsd_res.status}: ${text}`);
                        } else {
                            const clone = lsd_res.clone();
                            try {
                                const data = await lsd_res.json();
                                console.log("bulk-transfer response (JSON):", data);
                            } catch {
                                const text = await clone.text();
                                console.log("bulk-transfer response (text):", text);
                            }
                        }
                    }
                });
                console.log('bulk_tags', bulk_t_tags);
                console.log('lsd', lsd_tags);
                console.log('psr', psr_tags);
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
                            const data = await psr_res.json();
                            console.log("bulk-transfer response (JSON):", data);
                        } catch {
                            const text = await clone.text();
                            console.log("bulk-transfer response (text):", text);
                        }
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
                            const data = await bulk_t_res.json();
                            console.log("bulk-transfer response (JSON):", data);
                        } catch {
                            const text = await clone.text();
                            console.log("bulk-transfer response (text):", text);
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>