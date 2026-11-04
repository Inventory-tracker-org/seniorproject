<?php
include_once "../config.php";
check_auth();

$query = "
    SELECT barcode, delivered_status, received_date, description, delivered_date, delivered_time
    FROM packages
    WHERE delivered_status IN ('received', 'delivered')
      AND package_type = 'freight'
    ORDER BY
        CASE
            WHEN delivered_status = 'delivered' THEN delivered_date
            ELSE received_date
        END DESC,
        delivered_time DESC
";
$stmt = $dbh->prepare($query);
$stmt->execute();
$freightItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$itemsReceived = [];
$itemsDelivered = [];

$sevendaysago = date('Y-m-d', strtotime('-7 days')); //gets the date from 7 days ago and stores it into sevendaysago

foreach ($freightItems as $item) {
    if ($item['delivered_status'] === 'received') {
        $itemsReceived[] = $item;
    }

    if ($item['delivered_status'] === 'delivered') {
        if ($item['delivered_date'] >= $sevendaysago) {
            $itemsDelivered[] = $item;
        }
    }
}

$receivedCount = count($itemsReceived);
$deliveredCount = count($itemsDelivered);

$totalItems = $receivedCount + $deliveredCount;

if ($totalItems > 0) {
    $receivedPercent = ($receivedCount / $totalItems) * 100;
} else {
    $receivedPercent = 0;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freight Dashboard</title>
    <!-- Favicon: replace this href with your actual logo image path. -->
    <link rel="icon" href="../images/logo.png">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
        }

        .page-content {
            width: min(1100px, calc(100% - 40px));
            margin: 0 auto;
            padding: 70px 0 90px;
        }

        .page-heading {
            margin: 0 0 8px;
            color: #172536;
            font-size: 30px;
            font-weight: 700;
        }

        .page-description {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 15px;
        }

        .summary-area {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 24px;
            margin-bottom: 28px;
        }

        .kpi-card,
        .chart-card {
            padding: 24px;
            background: white;
            border: 1px solid #cbd9e6;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(20, 54, 84, 0.12);
        }

        .kpi-card {
            text-align: center;
        }

        .kpi-card p,
        .chart-caption {
            color: #566573;
        }

        .donut-chart {
            display: grid;
            width: 190px;
            height: 190px;
            margin: 20px auto;
            place-items: center;
            border-radius: 50%;
            background: conic-gradient(#2196f3 0 var(--received-percent),
                    #398d4c var(--received-percent) 100%);
        }

        .donut-chart::before {
            width: 120px;
            height: 120px;
            grid-area: 1 / 1;
            border-radius: 50%;
            background: white;
            content: "";
        }

        .donut-number {
            z-index: 1;
            grid-area: 1 / 1;
            color: #172536;
            font-size: 28px;
            font-weight: bold;
        }

        .received-donut {
            background: #2196f3;
            color: inherit;
            text-decoration: none;
        }

        .received-donut.is-empty {
            background: #cbd9e6;
        }

        .received-donut:focus-visible {
            outline: 3px solid #003594;
            outline-offset: 5px;
        }

        .chart-links {
            display: flex;
            justify-content: center;
            gap: 24px;
        }

        .chart-links a {
            color: #172536;
            text-decoration: none;
        }

        .legend {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-right: 5px;
            border-radius: 50%;
        }

        .received-legend {
            background: #2196f3;
        }

        .delivered-legend {
            background: #398d4c;
        }

        .dashboard-boxes {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            align-items: start;
        }

        .items-box {
            width: 100%;
            min-width: 0;
            border: solid;
            border-color: black;
            min-height: 180px;
            background-color: white;
        }

        .box-heading {
            margin: 0;
            padding: 16px 20px;
            background: #2196f3;
            color: black;
            font-size: 15px;
        }

        .items-list {
            padding: 0;
            width: 100%;
        }

        .items-list--scrollable {
            max-height: 350px;
            /* Header plus approximately five standard-height rows. */
            overflow: auto;
        }

        .items-list--scrollable:focus-visible {
            outline: 2px solid #003594;
            outline-offset: 3px;
        }

        .item {
            padding: 18px 0;
            border-bottom: 1px solid var(--border);
        }

        .item:last-child {
            border-bottom: 0;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin: 7px 0;
            font-size: 14px;
        }

        .item-row:first-child {
            margin-top: 0;
        }

        .label {
            color: var(--muted);
        }

        .value {
            color: #243b53;
            font-weight: 600;
            text-align: right;
        }

        .delivered-status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 4px;
            background: #3f8d47;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
        }

        .received-status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 4px;
            background: #cc8316;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
        }

        .empty-message {
            padding: 55px 20px !important;
            color: #707070 !important;
            text-align: center;
        }

        .package-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 14px;
        }

        .received-table {
            min-width: 680px;
        }

        .delivered-table {
            min-width: 0;
        }

        .package-table thead {
            background: #2196f3;
        }

        .package-table th {
            padding: 12px 10px;
            border-bottom: 1px solid #dddddd;
            background: #2196f3;
            color: #111111;
            font-size: 13px;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        .items-list--scrollable .package-table th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .package-table td {
            min-height: 76px;
            padding: 18px 10px;
            border-bottom: 1px solid #e6e6e6;
            color: #444444;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        .package-table tbody tr {
            transition: background-color 0.15s ease;
        }

        .package-table tbody tr:hover {
            background: #e8f6fc;
        }

        .package-table tbody tr:last-child td {
            border-bottom: none;
        }

        .date-column {
            width: 145px;
        }

        .time-column {
            width: 135px;
        }

        .barcode-column {
            width: 215px;
        }

        .status-column {
            width: 185px;
        }

        .barcode-value {
            color: #333333;
            font-family: Consolas, "Courier New", monospace;
            font-size: 13px;
        }

        .table-header-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .table-link-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            border: 1px solid #ffffff;
            border-radius: 4px;
            background: #ffffff;
            color: #0b4b7a;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            text-decoration: none;
            white-space: nowrap;
        }

        .table-link-button:hover {
            background: #e3f2fd;
            border-color: #e3f2fd;
        }

        .table-link-button:focus-visible {
            outline: 2px solid #172536;
            outline-offset: 3px;
        }

        @media (max-width: 750px) {
            .page-content {
                width: min(100% - 24px, 600px);
                padding-top: 35px;
            }

            .dashboard-boxes {
                grid-template-columns: 1fr;
            }

            .summary-area {
                grid-template-columns: 1fr;
            }

            /* Keep the delivered table within the page without scrolling. */
            .delivered-table th {
                width: 20%;
                padding: 12px 6px;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .delivered-table td {
                padding: 18px 6px;
            }

            .delivered-table .table-header-actions {
                flex-wrap: wrap;
                gap: 6px;
            }

            .delivered-table .table-link-button {
                max-width: 100%;
                padding: 6px;
                font-size: 11px;
                white-space: normal;
            }

            .delivered-table .delivered-status {
                padding: 5px 6px;
                font-size: 11px;
            }
        }
    </style>

</head>

<body>
    <?php include_once "../navbar.php"; ?>

    <main class="page-content">

        <h1 class="page-heading">Freight Dashboard</h1>

        <section class="summary-area">
            <div class="kpi-card">
                <h2>Housed Freight</h2>
                <a
                    href="#received-section"
                    class="donut-chart received-donut<?= $receivedCount === 0 ? ' is-empty' : '' ?>"
                    aria-label="View received freight: <?= $receivedCount ?> items still in the warehouse">
                    <span class="donut-number">
                        <?= $receivedCount ?>
                    </span>
                </a>
            </div>

            <div class="chart-card">
                <h2>Recent Freight Details</h2>

                <div
                    class="donut-chart"
                    style="--received-percent: <?= $receivedPercent ?>%;">
                    <div class="donut-number">
                        <?= $totalItems ?>
                    </div>
                </div>

                <div class="chart-links">
                    <a href="#received-section">
                        <span class="legend received-legend"></span>
                        Received: <?= $receivedCount ?>
                    </a>

                    <a href="#delivered-section">
                        <span class="legend delivered-legend"></span>
                        Delivered: <?= $deliveredCount ?>
                    </a>
                </div>
            </div>

        </section>

        <div class="dashboard-boxes">
            <section id="received-section" class="items-box">
                <div class="items-list items-list--scrollable" tabindex="0" role="region" aria-label="Received freight table">

                    <table class="package-table received-table" aria-label="Received freight">
                        <thead>
                            <tr>
                                <th scope="col" class="date-column">Date Received</th>
                                <th scope="col" class="barcode-column">Tracking Number</th>
                                <th scope="col">Description</th>
                                <th scope="col" class="status-column">
                                    <div class="table-header-actions">
                                        <span>Status</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($itemsReceived)): ?>
                                <tr>
                                    <td colspan="4" class="empty-message">No received items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($itemsReceived as $item): ?>

                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($item['received_date']) ?>
                                        </td>

                                        <td>
                                            <span class="barcode-value"><?= htmlspecialchars($item['barcode']) ?></span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($item['description']) ?>
                                        </td>
                                        <td>
                                            <span class="received-status">
                                                Received
                                            </span>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>
            </section>

            <section id="delivered-section" class="items-box">
                <div class="items-list">

                    <table class="package-table delivered-table" aria-label="Delivered freight">
                        <thead>
                            <tr>
                                <th scope="col" class="date-column">Date Delivered</th>
                                <th scope="col" class="time-column">Time Delivered</th>
                                <th scope="col" class="barcode-column">Tracking Number</th>
                                <th scope="col">Description</th>
                                <th scope="col" class="status-column">
                                    <div class="table-header-actions">
                                        <span>Status</span>
                                        <!-- DELIVERED LINK: replace href="#" with your URL. -->
                                        <a href="https://dataworks-7b7x.onrender.com/package-tracking/package-view.php?search=&per_page=10&page=1&filter_packages=freight" class="table-link-button" aria-label="View all delivered freight">View all</a>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($itemsDelivered)): ?>
                                <tr>
                                    <td colspan="5" class="empty-message">No delivered items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($itemsDelivered, 0, 5) as $item): ?>

                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($item['delivered_date']) ?>
                                        </td>

                                        <td>
                                            <?php
                                            $dbTime = $item['delivered_time'];
                                            // Cut off everything after the seconds if it contains microseconds (e.g., "17:51:11.613102" becomes "17:51:11")
                                            $cleanTime = explode('.', $dbTime)[0];

                                            $deliveryTime = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d ') . $cleanTime, new DateTimeZone('UTC'));
                                            $deliveryTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
                                            echo htmlspecialchars($deliveryTime->format('g:i A'));
                                            ?>
                                        </td>

                                        <td>
                                            <span class="barcode-value"><?= htmlspecialchars($item['barcode']) ?></span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['description']) ?>
                                        </td>

                                        <td>
                                            <span class="delivered-status">
                                                Delivered
                                            </span>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>