<?php
/* Asset Management Dashboard - Department Audit System */
include_once "config.php";
check_auth();
/* QUERIES */
$dept_count_q = "SELECT COUNT(DISTINCT(dept_id)) AS total_depts FROM asset_info";

$dept_count_stmt = $dbh->query($dept_count_q);
$total_departments = $dept_count_stmt->fetchColumn();

/* GET FREQ DATA */
$due_dates_q = "SELECT * FROM audit_freq";

$due_dates_stmt = $dbh->query($due_dates_q);
$due_dates = $due_dates_stmt->fetch(PDO::FETCH_ASSOC);

$spa_due = $due_dates['spa_due'] ?? '2026-07-01';
$old_spa_id = (int)$due_dates['curr_spa_id'] === 8 ? 9 : 8;
$curr_spa_id = (int)$due_dates['curr_spa_id'];

$self_due = $due_dates['self_due'] ?? '2026-07-01';
$curr_self_id = (int)$due_dates['curr_self_id'];
$old_self_id = (int)$due_dates['curr_self_id'] === 1 ? 2 : 1;

$mgmt_due = $due_dates['mgmt_due'] ?? '2026-07-01';
$curr_mgmt_id = (int)$due_dates['curr_mgmt_id'];
$old_mgmt_id = (int)$due_dates['curr_mgmt_id'] === 4 ? 5 : 4;

/* GET AUDITS */
$audit_progress_q = "SELECT audit_id, dept_id, audit_status FROM audit_history ORDER BY finished_at desc";

$audit_progress_stmt = $dbh->query($audit_progress_q);
$audit_progress = $audit_progress_stmt->fetchAll(PDO::FETCH_ASSOC);

/* CHART DATA/CONFIGURING */
$depts = [];
$depts = $self_depts = $spa_depts = [];
$status_data[] = ["Audit Status", "Count"];
$self_status_data[] = ["Audit Status", "Count"];
$spa_status_data[] = ["Audit Status", "Count"];

$status_count['In Progress'] = $status_count['Complete'] = 0;
$status_count['Incomplete'] = $total_departments;
$mgmt_status_over_count['In Progress'] = $mgmt_status_over_count['Complete'] = 0;
$mgmt_status_over_count['Incomplete'] = $total_departments;

$self_status_count['In Progress'] = $self_status_count['Complete'] = 0;
$self_status_count['Incomplete'] = $total_departments;
$self_over_status_count['In Progress'] = $self_over_status_count['Complete'] = 0;
$self_over_status_count['Incomplete'] = $total_departments;

$spa_status_count['In Progress'] = $spa_status_count['Complete'] = 0;
$spa_status_count['Incomplete'] = 1;
$spa_over_status_count['In Progress'] = $spa_over_status_count['Complete'] = 0;
$spa_over_status_count['Incomplete'] = 1;

$status_data[] = ['Complete', $status_count['Complete']];
$status_data[] = ['In Progress', $status_count['In Progress']];
$status_data[] = ['Incomplete', $status_count['Incomplete']];

$self_status_data[] = ['Complete', $self_status_count['Complete']];
$self_status_data[] = ['In Progress', $self_status_count['In Progress']];
$self_status_data[] = ['Incomplete', $self_status_count['Incomplete']];

$spa_status_data[] = ['Complete', $spa_status_count['Complete']];
$spa_status_data[] = ['In Progress', $spa_status_count['In Progress']];
$spa_status_data[] = ['Incomplete', $spa_status_count['Incomplete']];

$spa_id = 0;
$self_ids = $mgmt_ids = $overdue_ids = [];
$self_prog_count = $mgmt_prog_count = $mgmt_over_prog_count = [];
$self_prog_count['In Progress'] = $self_prog_count['Complete'] = 0;
$mgmt_prog_count['In Progress'] = $mgmt_prog_count['Complete'] = 0;
$overdue_prog_count['In Progress'] = $overdue_prog_count['Complete'] = 0;
foreach ($audit_progress as $index => $row) {
    switch ($row['audit_id']) {
        case 3:
            $self_over_status_count[$row['audit_id']]++;
            $self_over_status_count['Incomplete']--;
            break;
        case 6:
            $mgmt_status_over_count[$row['audit_status']]++;
            $mgmt_status_over_count['Incomplete']--;
            break;
        case 9:
            $spa_over_status_count[$row['audit_status']]++;
            $spa_over_status_count['Incomplete']--;
            break;
        case $old_mgmt_id:
            $mgmt_status_over_count[$row['audit_status']]++;
            $mgmt_status_over_count['Incomplete']--;
            break;
        case $old_self_id:
            $self_over_status_count[$row['audit_status']]++;
            $self_over_status_count['Incomplete']--;
            break;
        case $old_spa_id:
            $spa_over_status_count[$row['audit_status']]++;
            $spa_over_status_count['Incomplete']--;
            break;
    }
    if ($row['audit_id'] === $curr_self_id) {
        if (!in_array($row['dept_id'], $self_ids)) {
            $self_prog_count[$row['audit_status']]++;
            $self_ids[] = [
                'audit_id' => $row['audit_id'],
                'dept_id' => $row['dept_id']
            ];
            if ($row['audit_status'] !== 'Incomplete') {
                $self_status_count[$row['audit_status']]++;
                $self_status_count['Incomplete']--;
            }
        }
    }
    if ($row['audit_id'] === $curr_mgmt_id) {
        if (!in_array($row['dept_id'], $mgmt_ids)) {
            $mgmt_prog_count[$row['audit_status']]++;
            $mgmt_ids[] = [
                'audit_id' => $row['audit_id'],
                'dept_id' => $row['dept_id']
            ];
            if ($row['audit_status'] !== 'Incomplete') {
                $status_count[$row['audit_status']]++;
                $status_count['Incomplete']--;
            }
        }
    }
    if ($row['audit_id'] === $curr_spa_id) {
        if ($spa_id === 0) {
            $spa_status = $row['audit_status'] ?? 'Incomplete';
            $spa_id = $row['audit_id'];
        }
        if (!in_array($row['dept_id'], $spa_depts)) {
            if ($row['audit_status'] !== 'Incomplete') {
                $spa_status_count[$row['audit_status']]++;
                $spa_status_count['Incomplete']--;
            }
            $spa_depts[] = $row['dept_id'];
        }
    }
}


$user_name = $_SESSION['email'] ?? "Audit Manager";
$current_date = date("M j, Y");
$current_year = date("Y");
$spa_due = new DateTime($spa_due);
$self_due = new DateTime($self_due);
$mgmt_due = new DateTime($mgmt_due);
$now = new DateTime();

/* SPA DIFF */
$spa_diff = $now->diff($spa_due);
$spa_per = (int)(((730 - $spa_diff->days) / 730) * 100);

$self_diff = $now->diff($self_due);
$self_per = (int)(((365 - $self_diff->days) / 365) * 100);

$mgmt_diff = $now->diff($mgmt_due);
$mgmt_per = (int)(((1085 - $mgmt_diff->days) / 1085) * 100);


$self_audits_in_progress = $self_prog_count['In Progress'] ?? 0;
$self_audits_complete = $self_prog_count['Complete'] ?? 0;
$self_prev_audits_complete = $self_over_status_count['Complete'];
$self_prev_audits_in_progress = $self_over_prog_count['In Progress'] ?? 0;

$mgmt_audits_in_progress = $mgmt_prog_count['In Progress'] ?? 0;
$mgmt_audits_complete = $mgmt_prog_count['Complete'] ?? 0;
$mgmt_prev_audits_complete = $mgmt_status_over_count['Complete'];
$mgmt_prev_audits_in_progress = $mgmt_status_over_count['In Progress'] ?? 0;

$spa_status = $spa_id === 0 ? 'Incomplete' : $spa_status;
$spa_completion_status = $spa_status === 'Complete' ? 100 : 50;
$spa_completion_status = $spa_status === 'Incomplete' ? 0 : $spa_completion_status;

$prev_spa_status = $spa_over_status_count['Incomplete'] === 1 ? 0 : ($spa_over_status_count['In Progress'] === 1 ? 50 : 100);
$prev_spa = $spa_over_status_count['Incomplete'] === 1 ? 'Incomplete' : 'In Progress';
$prev_spa = ($prev_spa === 'In Progress' && $spa_over_status_count['In Progress'] === 1) ? 'In Progress' : 'Complete';
if ($spa_over_status_count['Incomplete'] === 1) {
    $prev_spa = 'Incomplete';
} else if ($spa_over_status_count['In Progress'] === 1) {
    $prev_spa = 'In Progress';
} else {
    $prev_spa = 'Complete';
}



$spa_completion_rate = $spa_per;
$self_completion_rate = $self_per;
$mgmt_completion_rate = $mgmt_per;

$self_completion_status = (int)($self_audits_complete / $total_departments) * 100 === 0 ? 0 : (int)($self_audits_complete / $total_departments) * 100;
$mgmt_completion_status = (int)($mgmt_audits_complete / $total_departments) * 100 === 0 ? 0 : (int)($mgmt_audits_complete / $total_departments) * 100;

$self_prev_completion_status = round(($self_prev_audits_complete / $total_departments) * 100 ?? 0);
$mgmt_prev_completion_status = round(($mgmt_prev_audits_complete / $total_departments) * 100 ?? 0);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Audit Management Dashboard</title>
    <link rel="stylesheet" href="home.css">
    <?php include_once "navbar.php"; ?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>

<body>
    <div class="page-wrapper">
        <div class="dashboard-container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Department Audit Management Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user_name); ?> • <?php echo $current_date; ?> • Audit Year <?php echo $current_year; ?></p>
            </div>

            <!-- Audit Type Overview -->
            <div class="audit-overview">
                <div class="audit-type-card spa">

                    <div class="audit-type-header">
                        <div class="audit-type-title">SPA Audit</div>
                        <div class="audit-badge spa">Special Purpose</div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill spa" style="width: <?php echo $spa_completion_status ?? 0; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $spa_status; ?></div>
                    <div class="audit-stats">
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?php echo $current_date; ?></div>
                            <div class="audit-stat-label"><?= $spa_status ?? 'Incomplete' ?></div>
                        </div>
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?= $spa_due->format('M d, Y') ?></div>
                            <div class="audit-stat-label">Due By</div>
                        </div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill spa" style="width: <?php echo $spa_completion_rate; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $spa_diff->days; ?> days until Due</div>
                </div>

                <div class="audit-type-card self">

                    <div class="audit-type-header">
                        <div class="audit-type-title">Self Audits</div>
                        <div class="audit-badge self">Self Assessment</div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill self" style="width: <?php echo $self_completion_status ?? 0; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $self_completion_status ?? 0; ?>% Audits Finished</div>
                    <div class="audit-stats">
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?php echo $self_audits_in_progress; ?></div>
                            <div class="audit-stat-label">In Progress</div>
                        </div>
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?= $self_due->format('M d, Y') ?></div>
                            <div class="audit-stat-label">Due By</div>
                        </div>
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?= $self_audits_complete ?></div>
                            <div class="audit-stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill self" style="width: <?php echo $self_completion_rate; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $self_diff->days; ?> days Until Due</div>
                </div>

                <div class="audit-type-card mgmt">

                    <div class="audit-type-header">
                        <div class="audit-type-title">Management Audits</div>
                        <div class="audit-badge mgmt">Management</div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill mgmt" style="width: <?php echo $mgmt_completion_status ?? 0; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $mgmt_completion_status ?? 0; ?>% Audits Finished</div>
                    <div class="audit-stats">
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?php echo $mgmt_audits_in_progress; ?></div>
                            <div class="audit-stat-label">In Progress</div>
                        </div>
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?= $mgmt_due->format('M d, Y') ?></div>
                            <div class="audit-stat-label">Due By</div>
                        </div>
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?= $mgmt_audits_complete ?></div>
                            <div class="audit-stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill mgmt" style="width: <?php echo $mgmt_completion_rate; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $mgmt_diff->days; ?> days Until Due</div>
                </div>
            </div>
            <!-- Audit Overdue Type Overview -->
            <div class="audit-overview">
                <div class="audit-type-card spa">

                    <div class="audit-type-header">
                        <div class="audit-type-title">Previous SPA Audit</div>
                        <div class="audit-badge spa">Special Purpose</div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill spa" style="width: <?php echo $prev_spa_status ?? 0; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $prev_spa; ?></div>
                    <div class="audit-stats">
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?php echo $current_date; ?></div>
                            <div class="audit-stat-label"><?= $prev_spa ?? 'Incomplete' ?></div>
                        </div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill spa" style="width: 100%"></div>
                    </div>
                </div>

                <div class="audit-type-card self">

                    <div class="audit-type-header">
                        <div class="audit-type-title">Previous Self Audits</div>
                        <div class="audit-badge self">Self Assessment</div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill self" style="width: <?php echo $self_prev_completion_status ?? 0; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $self_prev_completion_status ?? 0; ?>% Audits Finished</div>
                    <div class="audit-stats">
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?php echo $self_prev_audits_in_progress; ?></div>
                            <div class="audit-stat-label">In Progress</div>
                        </div>
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?= $self_prev_audits_complete ?></div>
                            <div class="audit-stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill self" style="width: 100%"></div>
                    </div>
                </div>

                <div class="audit-type-card mgmt">

                    <div class="audit-type-header">
                        <div class="audit-type-title">Previous Management Audits</div>
                        <div class="audit-badge mgmt">Management</div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill mgmt" style="width: <?php echo $mgmt_prev_completion_status ?? 0; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $mgmt_prev_completion_status ?? 0; ?>% Audits Finished</div>
                    <div class="audit-stats">
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?php echo $mgmt_prev_audits_in_progress; ?></div>
                            <div class="audit-stat-label">In Progress</div>
                        </div>
                        <div class="audit-stat">
                            <div class="audit-stat-number"><?= $mgmt_prev_audits_complete ?></div>
                            <div class="audit-stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill mgmt" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Department Audit Distribution Chart -->
            <div class="chart-section">
                <div class="chart-header">
                    <div class="chart-title">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M11,2V22C5.9,21.5 2,17.2 2,12C2,6.8 5.9,2.5 11,2M13,2V11H22C21.5,6.2 17.8,2.5 13,2M13,13V22C17.7,21.5 21.5,17.7 22,13H13Z" />
                        </svg>
                        <h3>Department Audit Status</h3>
                    </div>
                    <div class="chart-controls">
                        <button class="chart-btn active" onclick="switchChart('department')">Management Audit Status</button>
                        <button class="chart-btn" onclick="switchChart('audit-type')">Self Audit Status</button>
                        <button class="chart-btn" onclick="switchChart('spa-audit-type')">SPA Audit Status</button>
                        <!-- <button class="chart-btn" onclick="switchChart('timeline')">Timeline View</button> -->
                    </div>
                </div>
                <div class="chart-placeholder">
                    <div id="audit-status-piechart"></div>
                    <div id="self-audit-piechart" style="display:none;"></div>
                    <div id="spa-audit-piechart" style="display:none;"></div>

                    <div class="chart-placeholder-subtext" id="piechart">Chart showing audit status by management audits</div>
                </div>
            </div>

            <!-- Quick Actions for Audit Management -->
            <div class="actions-section">
                <div class="actions-header">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
                    </svg>
                    <h3>Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <button class="action-btn" onclick="handleAction('help')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" />
                        </svg>
                        Help Pages
                    </button>
                    <button class="action-btn" onclick="handleAction('schedule-audit')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" />
                        </svg>
                        View Your Profiles
                    </button>
                    <button class="action-btn secondary" onclick="handleAction('start-self-audit')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z" />
                        </svg>
                        Start Audit
                    </button>
                    <button class="action-btn" onclick="handleAction('search-departments')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
                        </svg>
                        Search Assets
                    </button>
                    <button class="action-btn purple" onclick="handleAction('view-audit-history')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
                        </svg>
                        View Audit History
                    </button>
                    <?php if ($_SESSION['role'] === 'management' || $_SESSION['role'] === 'admin') { ?>
                        <button class="action-btn secondary" onclick="handleAction('mona-reports')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z" />
                            </svg>
                            Mailclerk's Monthly Report
                        </button>
                    <?php } ?>
                </div>
            </div>
            <div class="actions-section">
                <div class="ticket-box">
                    <div class="app" aria-label="Recent tickets">
                        <header>
                            <div class="title"><span class="dot" aria-hidden="true"></span> Recent Tickets</div>
                            <div class="controls">
                                <input id="search" type="search" placeholder="Search subject/text/email" aria-label="Search tickets" />
                                <select id="status" aria-label="Filter by status">
                                    <option value="">All statuses</option>
                                    <option value="Complete">Complete</option>
                                    <option value="Incomplete">Incomplete</option>
                                </select>
                                <select id="type" aria-label="Filter by type">
                                    <option value="">All types</option>
                                    <option>Asset</option>
                                    <option>Department</option>
                                    <option>Building</option>
                                    <option>Room</option>
                                    <option>Other</option>
                                </select>
                                <button id="refresh">Refresh</button>
                            </div>
                        </header>

                        <section class="feed" aria-live="polite">
                            <div id="list" class="scroll"></div>
                            <div class="footer">
                                <div><span id="count">0</span> tickets · auto-updates</div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
            <div class="actions-section">
                <div class="actions-header">
                    <h3>Audit Schedules</h3>
                </div>
                <section class="feed" aria-live="polite">
                    <div id="date-list" class="scroll">
                        <?php
                        $select = "SELECT a.custodian, a.dept_id, d.dept_name, a.audit_date FROM audit_schedule a LEFT JOIN department d ON a.dept_id = d.dept_id ORDER BY a.audit_date::date DESC, array_position(ARRAY[8,9,10,11,12,1,2,3,4,5], to_char(a.audit_date, 'HH12')::int) ASC";
                        $stmt = $dbh->query($select);
                        $audit_schedules = $stmt->fetchAll();
                        if ($audit_schedules) {
                            foreach ($audit_schedules as $index => $audit) {
                        ?>
                                <article class="schedule">
                                    <div class="row">
                                        <div class="who">

                                            <div class="avatar" title="<?= $audit['custodian'] ?>"><?= $audit['custodian'] ?></div>
                                            <div>
                                                <div><?= $audit['custodian'] . ' Department: ' . $audit['dept_id'] . ' - ' . $audit['dept_name'] ?></div>
                                                <div class="when" title="<?= $audit['audit_date'] ?>"><?= $audit['audit_date'] ?></div>
                                            </div>
                                        </div>
                                        <?php if ($_SESSION['role'] === 'management' || $_SESSION['role'] === 'admin') { ?>
                                            <div class="badges">
                                                <button class="ticket-btn" value="<?= $audit['dept_id'] ?>" onclick='updateSchedule(<?= json_encode($audit['dept_id']) ?>,<?= json_encode($audit['audit_date']) ?>, "delete")'>Delete</button>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="info"><?php $audit['dept_id'] . ' ' . $audit['audit_date'] ?></div>
                                </article>
                            <?php
                            }
                        } else {
                            ?>
                            <div class="empty">No audits scheduled.</div>
                        <?php
                        }
                        ?>
                    </div>
            </div>
        </div>

        <script>
            async function updateTicket(id, action) {
                const API_URL = '/api/update-tickets.php';
                const params = new URLSearchParams();
                if (id) params.set('id', id);
                if (action) params.set('action', action);

                console.log('params: ' + params);
                try {
                    const res = await fetch(`${API_URL}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                } catch (err) {
                    console.warn('Error updating ticket:', err);
                }
            }
            async function updateSchedule(id, delete_date, action) {
                const API_URL = '/api/update-schedule.php';
                const params = new URLSearchParams();
                if (id) params.set('id', id);
                if (delete_date) params.set('delete_date', delete_date);
                if (action) params.set('action', action);

                console.log('params: ' + params);
                try {
                    const res = await fetch(`${API_URL}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                } catch (err) {
                    console.warn('Error updating schedule:', err);
                }
            }
            async function updateAudit(id, action) {
                const API_URL = '/api/update-audits.php';
                const params = new URLSearchParams();
                if (id) params.set('id', id);
                if (action) params.set('action', action);

                console.log('params: ' + params);
                try {
                    const res = await fetch(`${API_URL}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                } catch (err) {
                    console.warn('Error updating ticket:', err);
                }
            }


            function handleAction(action) {
                const actions = {
                    'help': () => {
                        window.location.href = "<?= url('/faq/all-faqs.php') ?>";
                        //window.location.href = 'https://dataworks-7b7x.onrender.com/faq/all-faqs.php';
                    },
                    'schedule-audit': () => {
                        window.location.href = "<?= url('/asset-manager/manage/manage-profile.php') ?>";
                        //window.location.href = 'https://dataworks-7b7x.onrender.com/asset-manager/manage/manage-profile.php';
                    },
                    'start-self-audit': () => {
                        window.location.href = "<?= url('/audit/upload.php') ?>";
                        //window.location.href = 'https://dataworks-7b7x.onrender.com/audit/upload.php';
                    },
                    'search-departments': () => {
                        window.location.href = "<?= url('/search/search.php') ?>";
                        //window.location.href = 'https://dataworks-7b7x.onrender.com/search/search.php';
                    },
                    'view-audit-history': () => {
                        window.location.href = "<?= url('/audit/audit-history/search-history.php') ?>";
                        //window.location.href = 'https://dataworks-7b7x.onrender.com/audit/audit-history/search-history.php';
                    },
                    'download-reports': () => {
                        window.location.href = "<?= url('/download-reports.php') ?>";
                        //window.location.href = 'https://dataworks-7b7x.onrender.com/download-reports.php';
                    },
                    'mona-reports': () => {
                        window.location.href = "<?= url('/mail-clerk-reports/monas-report.php') ?>";
                        //window.location.href = 'https://dataworks-7b7x.onrender.com/mail-clerk-reports/monas-report.php';
                    }
                };

                if (actions[action]) {
                    actions[action]();
                } else {
                    console.log(`Action not implemented: ${action}`);
                    alert(`Redirecting to ${action} page...`);
                }
            }

            document.getElementById('audit-status-piechart').style.display = "block";

            document.getElementById('self-audit-piechart').style.display = "none";
            document.getElementById('spa-audit-piechart').style.display = "none";

            function switchChart(type) {
                /* Remove active class from all buttons */
                document.querySelectorAll('.chart-btn').forEach(btn => {
                    btn.classList.remove('active');
                });

                /* Add active class to clicked button */
                event.target.classList.add('active');

                /* Update chart placeholder text */
                const placeholder = document.querySelector('.chart-placeholder-text');
                const subtext = document.querySelector('.chart-placeholder-subtext');

                switch (type) {
                    case 'department':
                        console.log(document.getElementById('audit-status-piechart').style.display);

                        document.getElementById('audit-status-piechart').style.display = "block";

                        document.getElementById('self-audit-piechart').style.display = "none";
                        document.getElementById('spa-audit-piechart').style.display = "none";
                        console.log(document.getElementById('audit-status-piechart').style.display);
                        subtext.textContent = 'Chart showing audit status across all departments';
                        break;
                    case 'audit-type':
                        document.getElementById('audit-status-piechart').style.display = "none";
                        document.getElementById('self-audit-piechart').style.display = "block";
                        document.getElementById('spa-audit-piechart').style.display = "none";
                        console.log(document.getElementById('audit-status-piechart').style.display);

                        subtext.textContent = 'Chart showing SELF audit status';
                        break;
                    case 'spa-audit-type':
                        document.getElementById('audit-status-piechart').style.display = "none";
                        document.getElementById('self-audit-piechart').style.display = "none";
                        document.getElementById('spa-audit-piechart').style.display = "block";

                        subtext.textContent = 'Chart showing SPA audit status';
                        break;
                }

                console.log(`Switching chart to: ${type}`);
            }

            /* Smooth entrance animation */
            document.addEventListener('DOMContentLoaded', function() {
                /* Animate audit type cards */
                const auditCards = document.querySelectorAll('.audit-type-card');
                auditCards.forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        card.style.transform = 'translateY(0)';
                        card.style.opacity = '1';
                    }, 100 + (index * 150));
                });

                /* Animate completion bars */
                setTimeout(() => {
                    const completionBars = document.querySelectorAll('.completion-fill');
                    completionBars.forEach(bar => {
                        const width = bar.style.width;
                        bar.style.width = '0%';
                        setTimeout(() => {
                            bar.style.width = width;
                        }, 500);
                    });
                }, 800);
            });

            /* Auto-refresh audit data every 10 minutes */
            setInterval(() => {
                console.log('Checking for audit updates...');
                /* You can make an AJAX call here to refresh
                 * audit data */
            }, 60000);
        </script>
        <script type="text/javascript">
            google.charts.load('current', {
                packages: ['corechart']
            });
            google.charts.setOnLoadCallback(drawChart);


            function drawChart() {
                var data = google.visualization.arrayToDataTable(<?= json_encode($status_data, true) ?>);
                console.log(data);
                var options = {
                    title: 'Management Audits',
                    pieHole: 1,
                };

                var chart = new google.visualization.PieChart(document.getElementById('audit-status-piechart'));

                chart.draw(data, options);
                //----------------------------------------------------------------------------------------------------------------//
                var data = google.visualization.arrayToDataTable(<?= json_encode($self_status_data, true) ?>);
                console.log(data);
                var options = {
                    title: 'Department Self Audits',
                    pieHole: 1,
                };

                var chart = new google.visualization.PieChart(document.getElementById('self-audit-piechart'));

                chart.draw(data, options);
                //----------------------------------------------------------------------------------------------------------------//
                var data = google.visualization.arrayToDataTable(<?= json_encode($spa_status_data, true) ?>);
                console.log(data);
                var options = {
                    title: 'SPA Audit',
                    pieHole: 1,
                };

                var chart = new google.visualization.PieChart(document.getElementById('spa-audit-piechart'));

                chart.draw(data, options);
            }
        </script>
        <script>
            // ===== Configuration =====
            const API_URL = '/api/tickets.php';

            // ===== Utilities =====
            const el = (sel) => document.querySelector(sel);
            const fmtTime = (iso) => new Date(iso).toLocaleString();
            const initials = (email) => (email?.[0] || '?').toUpperCase();

            // ===== Render =====
            function ticketBadge(status) {
                if (!status) return '';
                const cls = status.toLowerCase() === 'complete' ? 'success' : 'warn';
                return `<span class="badge ${cls}">${status}</span>`;
            }

            function typeBadge(type) {
                return type ? `<span class="badge">${type}</span>` : '';
            }

            function ticketItem(t) {
                return `
        <article class="ticket" data-id="${t.id}">
          <div class="row">
            <div class="who">
              <div class="avatar" title="${t.email}">${initials(t.email)}</div>
              <div>
                <div>${t.email || 'unknown@local'}</div>
                <div class="when" title="${t.date_added}">${fmtTime(t.date_added)}</div>
              </div>
            </div>
            <div class="badges">
<?php if ($_SESSION['role'] === 'management' || $_SESSION['role'] === 'admin') { ?>
              <button class="ticket-btn" value="${t.id}" onclick="updateTicket(${t.id}, 'completed')">Complete</button>
              <button class="ticket-btn" value="${t.id}" onclick="updateTicket(${t.id}, 'delete')">Delete</button>
<?php } ?>
              ${typeBadge(t.ticket_type || t.type)}
              ${ticketBadge(t.ticket_status)}
            </div>
          </div>
          <div class="info">${escapeHtml(t.info || '')}</div>
        </article>`;
            }

            function render(list) {
                const wrap = el('#list');
                if (!list.length) {
                    wrap.innerHTML = `<div class="empty">No tickets yet.</div>`;
                    el('#count').textContent = '0';
                    return;
                }
                wrap.innerHTML = list.map(ticketItem).join('');
                el('#count').textContent = list.length;
            }

            function escapeHtml(str) {
                return String(str)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            // ===== Data fetching =====
            async function fetchTickets({
                q = '',
                status = '',
                type = '',
                beforeId = null,
                limit = 25
            } = {}) {
                const params = new URLSearchParams();
                if (q) params.set('q', q);
                if (status) params.set('status', status);
                if (type) params.set('type', type);
                if (beforeId) params.set('before_id', beforeId);
                params.set('limit', limit);

                try {
                    const res = await fetch(`${API_URL}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    // Expecting: { tickets: [ {id, email, ticket_type, date_added, info, ticket_status}, ... ] }
                    if (Array.isArray(data)) return data; // or plain array
                    if (data && Array.isArray(data.tickets)) return data.tickets;
                    return mockData();
                } catch (err) {
                    console.warn('Falling back to mock data:', err);
                    return mockData();
                }
            }

            // ===== Pagination helpers =====
            let current = [];
            async function loadInitial() {
                const q = el('#search').value.trim();
                const status = el('#status').value;
                const type = el('#type').value;
                current = await fetchTickets({
                    q,
                    status,
                    type,
                    limit: 25
                });
                // Newest first; UI is column-reverse, but keep data in descending order too
                current.sort((a, b) => new Date(b.date_added) - new Date(a.date_added));
                render(current);
            }

            // ===== Auto-refresh (polling) =====
            let pollId;

            function startPolling() {
                stopPolling();
                pollId = setInterval(async () => {
                    const q = el('#search').value.trim();
                    const status = el('#status').value;
                    const type = el('#type').value;
                    const latestId = current[0]?.id;
                    const fresh = await fetchTickets({
                        q,
                        status,
                        type,
                        limit: 25
                    });
                    // Merge by id
                    const byId = new Map(current.map(t => [t.id, t]));
                    for (const t of fresh) {
                        byId.set(t.id, t);
                    }
                    current = Array.from(byId.values()).sort((a, b) => new Date(b.date_added) - new Date(a.date_added));
                    render(current);
                }, 6000); // 15s
            }

            function stopPolling() {
                if (pollId) clearInterval(pollId);
            }

            // ===== Wire up UI =====
            const refresh_btn = el('#refresh');
            if (refresh_btn) {
                refresh_btn.addEventListener('click', loadInitial);
                el('#search').addEventListener('input', debounce(loadInitial, 300));
                el('#status').addEventListener('change', loadInitial);
                el('#type').addEventListener('change', loadInitial);
            }

            function debounce(fn, ms = 250) {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), ms);
                };
            }

            // ===== Demo fallback data (used when API not available) =====
            function mockData() {
                const now = Date.now();
                const sample = [{
                    id: 101,
                    email: 'jane.doe@example.com',
                    ticket_type: 'Asset',
                    date_added: new Date(now - 60_000).toISOString(),
                    info: 'No tickets at this time.',
                    ticket_status: 'Complete'
                }];
                return sample;
            }

            // Kick things off
            loadInitial().then(startPolling);
            var botmanWidget = {
                frameEndpoint: "<?= url('/chat/botman-widget.html') ?>",
                //frameEndpoint: 'https://dataworks-7b7x.onrender.com/chat/botman-widget.html',
                chatServer: "<?= url('/chat/chatbot.php') ?>",
                //chatServer: 'https://dataworks-7b7x.onrender.com/chat/chatbot.php',
                introMessage: "👋 Hello! I'm Chatbot. Reply with help for a list of things I can do!",
                title: "Chatbot",
                mainColor: "#ADD8E6",
                bubbleBackground: "#ADD8E6",
                placeholderText: "Type your question here..."
            };
        </script>
        <script src="https://cdn.jsdelivr.net/npm/botman-web-widget@0/build/js/widget.js"></script>
</body>

</html>