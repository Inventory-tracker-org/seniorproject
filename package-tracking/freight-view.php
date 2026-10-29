<?php
include_once "../config.php";
check_auth();
// Dummy freight data for UI testing
$freightItems = [
    [
        'barcode' => 'FRT-1001',
        'carrier' => 'FedEx Freight',
        'status' => 'received',
        'delivery_date' => date('Y-m-d')
    ],
    [
        'barcode' => 'FRT-1002',
        'carrier' => 'UPS Freight',
        'status' => 'received',
        'delivery_date' => date('Y-m-d')
    ],
    [
        'barcode' => 'FRT-1003',
        'carrier' => 'DHL Freight',
        'status' => 'delivered',
        'delivery_date' => date('Y-m-d', strtotime('-1 day')) //Gets yesterdays date (again dummy data)
    ]
];

$itemsReceived = [];
$itemsDelivered = [];

$twodaysago = date('Y-m-d', strtotime('-7 days')); //gets the date from 7 days ago and stores it into twodaysago

foreach ($freightItems as $item)
{
    if ($item['status'] === 'received')
    {
        $itemsReceived[] = $item;
    }

    if ($item['status'] === 'delivered')
    {
        if ($item['delivery_date'] >= $twodaysago)
        {
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
    <title>Freight Dashboard</title>
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

/*.kpi-card h2 {
    margin: 20px 0 8px;
    color: #003594;
    font-size: 56px;
}*/

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
    background: conic-gradient(
        #2196f3 0 var(--received-percent),
        #398d4c var(--received-percent) 100%
    );
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
    overflow-x: auto;
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

.status {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 4px;
    background: #398d4c;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
}

.empty-message {
    margin: 0;
    padding: 24px 0;
    color: var(--muted);
    font-size: 14px;
}

.package-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;

}

.package-table thead {
    background: #2196f3;
}

.package-table th {
    padding: 14px 16px;
    color: black;
    text-align: left;
    white-space: nowrap;
}

.package-table tbody tr:last-child td {
    border-bottom: none;    
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
}
    </style>

</head>
<body> 
 <?php include_once "../navbar.php"; ?>

    <main class="page-content">

        <h1 class="page-heading">Freight Dashboard</h1>

        <p class="page-description">
            Freight delivered and received
        </p>
<section class="summary-area">
        <div class="kpi-card">
            <a
                href="#received-section"
                class="donut-chart received-donut<?= $receivedCount === 0 ? ' is-empty' : '' ?>"
                aria-label="View received freight: <?= $receivedCount ?> items still in the warehouse"
            >
                <span class="donut-number">
                    <?= $receivedCount ?>
                </span>
            </a>

            <p>Items received and still in the warehouse</p>
        </div>

    <div class="chart-card">
        <h2>Freight Status</h2>

        <div
            class="donut-chart"
            style="--received-percent: <?= $receivedPercent ?>%;"
        >
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

        <p class="chart-caption">
            Click a status to view those freight items.
        </p>
    </div>

</section>

       <div class="dashboard-boxes">
            <section id="received-section" class="items-box">
                <div class="items-list">

                    <?php if (empty($itemsReceived)): ?>

                        <p class="empty-message">
                            No received items found.
                        </p>

                    <?php else: ?>

                        <table class="package-table">
                            <thead>
                                <tr>
                                    <th>Date Received</th>
                                    <th>Tracking Number</th>
                                    <th>Carrier</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($itemsReceived as $item): ?>

                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($item['delivery_date']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['barcode']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['carrier']) ?>
                                        </td>

                                        <td>
                                            <span class="status">
                                                Received
                                            </span>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php endif; ?>

                </div>
            </section>

            <!-- Delivered Items -->
            <section class="items-box">
                <div class="items-list">

                    <?php if (empty($itemsDelivered)): ?>

                        <p class="empty-message">
                            No delivered items found.
                        </p>

                    <?php else: ?>

                        <table class="package-table">
                            <thead>
                                <tr>
                                    <th>Date Delivered</th>
                                    <th>Time Delivered</th>
                                    <th>Tracking Number</th>
                                    <th>Carrier</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($itemsDelivered as $item): ?>

                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($item['delivery_date']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['delivery_time']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['barcode']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['carrier']) ?>
                                        </td>

                                        <td>
                                            <span class="status">
                                                Delivered
                                            </span>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php endif; ?>

                </div>
            </section>
        </div>
    </main>i
</body>
</html>
