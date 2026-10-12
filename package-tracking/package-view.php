<?php

declare(strict_types=1);

include_once('../config.php');

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function escapeHtml(mixed $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function displayValue(mixed $value): string
{
    if (
        $value === null ||
        trim((string)$value) === ''
    ) {
        return '--';
    }

    return escapeHtml($value);
}

function buildPageUrl(
    int $page,
    int $perPage,
    string $search,
    string $packageType
): string {
    return '?' . http_build_query([
        'page' => $page,
        'per_page' => $perPage,
        'search' => $search,
        'filter_packages' => $packageType
    ]);
}

/*
|--------------------------------------------------------------------------
| Read and validate query parameters
|--------------------------------------------------------------------------
*/

$allowedPageSizes = [
    10,
    20,
    30,
    40,
    50
];

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$perPage = (int)(
    $_GET['per_page'] ?? 10
);

$search = trim(
    (string)($_GET['search'] ?? '')
);

if (
    !in_array(
        $perPage,
        $allowedPageSizes,
        true
    )
) {
    $perPage = 10;
}

/*
|--------------------------------------------------------------------------
| Build date/search conditions
|--------------------------------------------------------------------------
|
| All package browsing is limited to the last year.
|
| No search:
|   - only the newest 500 rows are available
|
| Search:
|   - searches all packages from the last year
|   - not limited to the newest 500
|
*/

$whereParts = [
    "
    delivered_date >=
        CURRENT_DATE - INTERVAL '1 year'
    "
];

$params = [];

$packageType = (string)($_GET['filter_packages'] ?? 'All');

$allowedPackageTypes = [
    'All',
    'package',
    'freight'
];

if (!in_array($packageType, $allowedPackageTypes, true)) {
    $packageType = 'All';
}

if ($packageType !== 'All') {
    $whereParts[] = 'package_type = :package_type';
    $params['package_type'] = $packageType;
}

if ($search !== '') {
    $whereParts[] = "
        (
            barcode ILIKE :search
            OR delivered_by ILIKE :search
            OR delivered_to ILIKE :search
            OR comments ILIKE :search
            OR carrier ILIKE :search
        )
    ";

    $params['search'] =
        '%' . $search . '%';
}

$whereSql =
    'WHERE ' .
    implode(
        ' AND ',
        $whereParts
    );

/*
|--------------------------------------------------------------------------
| Count matching packages
|--------------------------------------------------------------------------
|
| For the default page, cap the visible result set at 500.
| For searches, count every matching package from the last year.
|
*/

if ($search === '') {
    $countSql = "
        SELECT COUNT(*)
        FROM (
            SELECT barcode
            FROM packages
            {$whereSql}
            ORDER BY
                delivered_date DESC,
                delivered_time DESC
            LIMIT 500
        ) AS recent_packages
    ";
} else {
    $countSql = "
        SELECT COUNT(*)
        FROM packages
        {$whereSql}
    ";
}


$countStatement =
    $dbh->prepare(
        $countSql
    );

if ($packageType !== 'All') {
    $countStatement->bindValue(
        ':package_type',
        $params['package_type'],
        PDO::PARAM_STR
    );
}

if ($search !== '') {
    $countStatement->bindValue(
        ':search',
        $params['search'],
        PDO::PARAM_STR
    );
}

$countStatement->execute();

$totalRows =
    (int)$countStatement
        ->fetchColumn();

$totalPages = max(
    1,
    (int)ceil(
        $totalRows / $perPage
    )
);

/*
|--------------------------------------------------------------------------
| Prevent invalid page numbers
|--------------------------------------------------------------------------
*/

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset =
    ($page - 1) *
    $perPage;

/*
|--------------------------------------------------------------------------
| Retrieve current page
|--------------------------------------------------------------------------
*/

if ($search === '') {
    /*
     * Default view:
     * newest 500 packages from the last year,
     * then paginate inside that result set.
     */
    $packageSql = "
        SELECT
            barcode,
            delivered_date,
            delivered_time,
            delivered_by,
            delivered_to,
            comments,
            carrier,
            delivered_status,
            signature_path,
            photo_path,
            latitude,
            longitude
        FROM (
            SELECT
                barcode,
                delivered_date,
                delivered_time,
                delivered_by,
                delivered_to,
                comments,
                carrier,
                delivered_status,
                signature_path,
                photo_path,
                latitude,
                longitude
            FROM packages
            {$whereSql}
            ORDER BY
                delivered_date DESC,
                delivered_time DESC
            LIMIT 500
        ) AS recent_packages
        ORDER BY
            delivered_date DESC,
            delivered_time DESC
        LIMIT :limit
        OFFSET :offset
    ";
} else {
    /*
     * Search view:
     * search the entire last year.
     */
    $packageSql = "
        SELECT
            barcode,
            delivered_date,
            delivered_time,
            delivered_by,
            delivered_to,
            comments,
            carrier,
            delivered_status,
            signature_path,
            photo_path,
            latitude,
            longitude
        FROM packages
        {$whereSql}
        ORDER BY
            delivered_date DESC,
            delivered_time DESC
        LIMIT :limit
        OFFSET :offset
    ";
}

$packageStatement =
    $dbh->prepare(
        $packageSql
    );

$googleMapsApiKey = trim(
    (string)getenv(
        'GOOGLE_MAPS_API_KEY'
    )
);

if ($packageType !== 'All') {
    $packageStatement->bindValue(
        ':package_type',
        $params['package_type'],
        PDO::PARAM_STR
    );
}

if ($search !== '') {
    $packageStatement->bindValue(
        ':search',
        $params['search'],
        PDO::PARAM_STR
    );
}

$packageStatement->bindValue(
    ':limit',
    $perPage,
    PDO::PARAM_INT
);

$packageStatement->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);

$packageStatement->execute();

$packages =
    $packageStatement->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| Pagination range
|--------------------------------------------------------------------------
*/

$paginationStart = max(
    1,
    $page - 2
);

$paginationEnd = min(
    $totalPages,
    $page + 2
);

$startingRow =
    $totalRows === 0
    ? 0
    : $offset + 1;

$endingRow = min(
    $offset + $perPage,
    $totalRows
);
include('../navbar.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Package List</title>

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: #252525;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
        }

        a {
            color: #1683ff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .page-container {
            width: calc(100% - 60px);
            max-width: 1500px;
            margin: 0 auto;
            padding: 24px 0 40px;
        }

        .utility-links {
            margin-bottom: 22px;
            color: #1683ff;
            font-size: 13px;
        }

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 14px;
        }

        .page-title {
            margin: 0;
            font-size: 23px;
            font-weight: 600;
        }

        .search-form {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border: 1px solid #d6dadd;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 2px 7px rgba(0, 0, 0, 0.09);
        }

        .search-input {
            width: 230px;
            height: 34px;
            padding: 0 11px;
            border: 1px solid #cfd4d8;
            border-radius: 4px;
            outline: none;
            font-size: 14px;
        }

        .search-input:focus {
            border-color: #1683ff;
            box-shadow: 0 0 0 2px rgba(22, 131, 255, 0.12);
        }

        .search-button {
            height: 34px;
            padding: 0 15px;
            border: 0;
            border-radius: 4px;
            color: #ffffff;
            background: #2f3438;
            cursor: pointer;
        }

        .search-button:hover {
            background: #171a1c;
        }

        .clear-search {
            padding: 7px 6px;
            color: #555555;
            font-size: 13px;
        }

        .table-card {
            overflow: hidden;
            border: 1px solid #d9dde0;
            background: #ffffff;
        }

        .table-scroll {
            overflow-x: auto;
        }

        .package-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .package-table th {
            padding: 12px 10px;
            border-bottom: 1px solid #dddddd;
            color: #111111;
            background: linear-gradient(135deg, #2196f3 100%);
            font-size: 13px;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        .package-table td {
            min-height: 76px;
            padding: 18px 10px;
            border-bottom: 1px solid #e6e6e6;
            color: #444444;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        .package-table tbody tr:hover {
            background: #fafcff;
        }

        .package-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .date-column {
            width: 125px;
        }

        .time-column {
            width: 125px;
        }

        .barcode-column {
            width: 215px;
        }

        .person-column {
            width: 180px;
        }

        .delivered-to-column {
            width: 220px;
        }

        .carrier-column {
            width: 250px;
        }

        .status-column {
            width: 115px;
        }

        .barcode-value {
            color: #333333;
            font-family: Consolas, "Courier New", monospace;
            font-size: 13px;
        }

        .empty-value {
            color: #888888;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 4px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
        }

        .status-delivered {
            background: #3f8d47;
        }

        .status-pending {
            background: #b1781d;
        }

        .empty-table {
            padding: 55px 20px !important;
            color: #707070 !important;
            text-align: center;
        }

        .table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            padding: 17px 15px;
            border-top: 1px solid #dfe2e5;
            background: #f8fafb;
        }

        .result-count {
            color: #666666;
            font-size: 13px;
            white-space: nowrap;
        }

        .pagination-area {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .page-link,
        .page-disabled,
        .page-ellipsis {
            display: inline-flex;
            min-width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            padding: 0 9px;
            border: 1px solid #d6dadd;
            border-radius: 4px;
            background: #ffffff;
            color: #444444;
            font-size: 13px;
            text-decoration: none;
        }

        .page-link:hover {
            border-color: #1683ff;
            color: #1683ff;
            text-decoration: none;
        }

        .page-link.active {
            border-color: #1683ff;
            color: #1683ff;
            background: #f3f8ff;
        }

        .page-disabled {
            color: #b8b8b8;
            cursor: default;
        }

        .page-ellipsis {
            border-color: transparent;
            background: transparent;
        }

        .page-size-form {
            margin: 0;
        }

        .page-size-select {
            height: 36px;
            padding: 0 32px 0 11px;
            border: 1px solid #1683ff;
            border-radius: 4px;
            color: #444444;
            background: #ffffff;
            cursor: pointer;
            outline: none;
        }

        @media (max-width: 850px) {
            .page-container {
                width: calc(100% - 28px);
            }

            .page-header {
                align-items: stretch;
                flex-direction: column;
            }

            .search-form {
                width: 100%;
            }

            .search-input {
                width: 100%;
            }

            .table-footer {
                align-items: flex-start;
                flex-direction: column;
            }

            .pagination-area {
                width: 100%;
                align-items: flex-start;
                flex-direction: column;
            }

            .pagination {
                max-width: 100%;
                flex-wrap: wrap;
            }
        }

        .package-row {
            cursor: pointer;
            transition:
                background-color 0.15s ease,
                box-shadow 0.15s ease;
        }

        .package-row:hover,
        .package-row:focus {
            background: #e8f6fc !important;
            outline: none;
        }

        .package-row.selected {
            background: #dff3fb !important;
            box-shadow: inset 4px 0 0 #1683ff;
        }

        .package-detail-row>td {
            padding: 0 !important;
            background: #ffffff;
        }

        .package-detail-content {
            padding: 28px 46px 38px;
            border-bottom: 1px solid #dcdcdc;
        }

        .package-detail-loading {
            padding: 35px;
            color: #666666;
            text-align: center;
        }

        .package-detail-error {
            padding: 25px;
            color: #a12626;
            background: #fff2f2;
            text-align: center;
        }

        .package-detail-layout {
            display: grid;
            grid-template-columns: 170px minmax(0, 1fr) 250px;
            gap: 30px;
            max-width: 1120px;
            margin: 0 auto;
        }

        .package-detail-icon {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .package-placeholder-icon {
            display: flex;
            width: 125px;
            height: 105px;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5d8;
            color: #767676;
            background: #f4f6f7;
            font-size: 40px;
            font-weight: 700;
        }

        .package-main-details {
            min-width: 0;
        }

        .package-detail-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 17px;
            border-bottom: 1px solid #dedede;
        }

        .package-detail-barcode {
            margin: 0;
            color: #171717;
            font-size: 28px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .package-detail-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 18px;
        }

        .package-detail-field {
            min-height: 72px;
            padding: 12px 14px 12px 8px;
            border-bottom: 1px solid #e2e2e2;
        }

        .package-detail-field strong {
            display: block;
            margin-bottom: 6px;
            color: #111111;
        }

        .package-detail-field span {
            color: #333333;
            overflow-wrap: anywhere;
        }

        .package-signature-section {
            margin-top: 18px;
            padding: 15px 8px 5px;
        }

        .package-signature-section strong {
            display: block;
            margin-bottom: 10px;
        }

        .package-signature-image {
            display: block;
            width: 260px;
            max-width: 100%;
            max-height: 130px;
            object-fit: contain;
            border: 1px solid #e1e1e1;
            background: #f6f6f6;
        }

        .no-signature {
            display: flex;
            width: 260px;
            max-width: 100%;
            height: 105px;
            align-items: center;
            justify-content: center;
            border: 1px solid #dddddd;
            color: #777777;
            background: #f5f5f5;
        }

        .package-action-panel {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .package-action-button {
            width: 100%;
            min-height: 41px;
            padding: 10px 14px;
            border: 0;
            border-radius: 3px;
            color: #ffffff;
            background: linear-gradient(#3b3b3b, #202020);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
            cursor: pointer;
            font-size: 14px;
            text-align: center;
            text-decoration: none;
        }

        .package-action-button:hover {
            background: linear-gradient(#292929, #111111);
            text-decoration: none;
        }

        .package-action-button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .package-close-link {
            align-self: flex-end;
            margin-top: 22px;
            border: 0;
            color: #1683ff;
            background: transparent;
            cursor: pointer;
            text-decoration: underline;
        }

        .package-modal {
            position: fixed;
            z-index: 5000;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .package-modal.open {
            display: flex;
        }

        .modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.62);
        }

        .modal-window {
            position: relative;
            z-index: 1;
            display: flex;
            width: min(650px, 100%);
            max-height: calc(100vh - 40px);
            flex-direction: column;
            overflow: hidden;
            border-radius: 4px;
            background: #ffffff;
            box-shadow: 0 16px 50px rgba(0, 0, 0, 0.35);
        }

        .modal-header {
            display: flex;
            min-height: 56px;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px;
            border-bottom: 1px solid #dddddd;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 16px;
        }

        .modal-close {
            border: 0;
            color: #777777;
            background: transparent;
            cursor: pointer;
            font-size: 28px;
            line-height: 1;
        }

        .modal-body {
            overflow-y: auto;
            padding: 24px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 11px 16px;
            border-top: 1px solid #dddddd;
        }

        .secondary-button {
            min-width: 66px;
            min-height: 34px;
            padding: 7px 15px;
            border: 1px solid #cccccc;
            border-radius: 4px;
            color: #555555;
            background: #ffffff;
            cursor: pointer;
        }

        .full-delivery-photo {
            display: block;
            width: auto;
            max-width: 100%;
            max-height: 72vh;
            margin: 0 auto;
            object-fit: contain;
        }

        .photo-unavailable {
            margin: 40px 0;
            color: #777777;
            text-align: center;
        }

        .history-modal-window {
            width: min(650px, 100%);
        }

        .history-summary {
            display: grid;
            grid-template-columns: 165px minmax(0, 1fr);
            gap: 30px;
        }

        .history-package-icon {
            display: flex;
            width: 145px;
            height: 110px;
            align-items: center;
            justify-content: center;
            color: #666666;
            background: #eeeeee;
            font-size: 32px;
            font-weight: 700;
        }

        .history-barcode {
            margin: 0 0 12px;
            font-size: 27px;
            overflow-wrap: anywhere;
        }

        .history-subtitle {
            color: #777777;
            font-size: 16px;
            font-weight: 700;
        }

        .history-information-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;
            margin-top: 55px;
        }

        .history-field {
            min-width: 0;
        }

        .history-field strong {
            display: block;
            margin-bottom: 7px;
        }

        .history-signature {
            width: 270px;
            max-width: 100%;
            max-height: 140px;
            object-fit: contain;
            border: 1px solid #e1e1e1;
            background: #f4f4f4;
        }

        .history-photo {
            width: 270px;
            max-width: 100%;
            max-height: 210px;
            object-fit: contain;
            border: 1px solid #e1e1e1;
            background: #f4f4f4;
        }

        .map-modal-window {
            width: min(760px, 100%);
        }

        .map-modal-body {
            padding: 24px;
        }

        .delivery-map {
            width: 100%;
            height: min(62vh, 520px);
            min-height: 360px;
            overflow: hidden;
            border: 1px solid #d9dde0;
            border-radius: 4px;
            background: #eef1f3;
        }

        .header-controls {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .filter-by-type {
            margin: 0;
            padding: 8px;

            border: 1px solid #d6dadd;
            border-radius: 8px;

            background: #ffffff;

            box-shadow: 0 2px 7px rgba(0, 0, 0, 0.09);
        }

        .filter-by-type select {
            width: 170px;
            height: 34px;

            padding: 0 32px 0 11px;

            border: 1px solid #cfd4d8;
            border-radius: 4px;

            color: #444444;
            background: #ffffff;

            font-size: 14px;
            cursor: pointer;
            outline: none;
        }

        .filter-by-type select:focus {
            border-color: #1683ff;
            box-shadow: 0 0 0 2px rgba(22, 131, 255, 0.12);
        }

        .map-loading,
        .map-error,
        .map-unavailable {
            display: flex;
            min-height: 360px;
            align-items: center;
            justify-content: center;
            padding: 28px;
            text-align: center;
        }

        .map-loading,
        .map-unavailable {
            color: #666666;
        }

        .map-error {
            color: #a12626;
            background: #fff2f2;
        }

        .map-coordinate-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 24px;
            margin-top: 14px;
            padding: 13px 15px;
            border: 1px solid #e0e3e5;
            border-radius: 4px;
            color: #555555;
            background: #f8fafb;
            font-size: 13px;
        }

        .map-coordinate-summary strong {
            color: #222222;
        }

        .open-google-maps-link {
            display: inline-flex;
            min-height: 34px;
            align-items: center;
            justify-content: center;
            padding: 7px 15px;
            border: 1px solid #2f3438;
            border-radius: 4px;
            color: #ffffff;
            background: #2f3438;
            text-decoration: none;
        }

        .open-google-maps-link:hover {
            color: #ffffff;
            background: #171a1c;
            text-decoration: none;
        }

        body.modal-open {
            overflow: hidden;
        }

        @media (max-width: 900px) {
            .package-detail-layout {
                grid-template-columns: 120px minmax(0, 1fr);
            }

            .package-action-panel {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .package-detail-content {
                padding: 22px 18px 30px;
            }

            .package-detail-layout {
                grid-template-columns: 1fr;
            }

            .package-detail-icon {
                justify-content: flex-start;
            }

            .package-detail-grid,
            .history-information-grid {
                grid-template-columns: 1fr;
            }

            .package-action-panel {
                grid-template-columns: 1fr;
            }

            .history-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <main class="page-container">
        <div class="utility-links">
            <!--
            <a href="#">Customize Package Table</a>
            <span aria-hidden="true"> | </span>
            <a href="#">Advanced Search</a>
-->
        </div>

        <header class="page-header">
            <h1 class="page-title">Package List</h1>

            <div class="header-controls">

                <form method="get" class="filter-by-type">
                    <input
                        type="hidden"
                        name="search"
                        value="<?= escapeHtml($search) ?>">

                    <input
                        type="hidden"
                        name="per_page"
                        value="<?= $perPage ?>">

                    <input
                        type="hidden"
                        name="page"
                        value="1">

                    <select
                        id="filter_packages"
                        name="filter_packages"
                        onchange="this.form.submit()">

                        <option
                            value="All"
                            <?= $packageType === 'All' ? 'selected' : '' ?>>
                            All
                        </option>

                        <option
                            value="package"
                            <?= $packageType === 'package' ? 'selected' : '' ?>>
                            Standard
                        </option>

                        <option
                            value="freight"
                            <?= $packageType === 'freight' ? 'selected' : '' ?>>
                            Freight
                        </option>

                    </select>
                </form>


                <form method="get" class="search-form">
                    <input
                        type="search"
                        name="search"
                        class="search-input"
                        value="<?= escapeHtml($search) ?>"
                        placeholder="Search packages"
                        aria-label="Search packages">

                    <input
                        type="hidden"
                        name="per_page"
                        value="<?= $perPage ?>">

                    <input type="hidden" name="page" value="1">

                    <button type="submit" class="search-button">
                        Search
                    </button>

                    <?php if ($search !== ''): ?>
                        <a
                            class="clear-search"
                            href="<?= escapeHtml(
                                        buildPageUrl(1, $perPage, '', $packageType)
                                    ) ?>">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
        </header>

        <section class="table-card">
            <div class="table-scroll">
                <table class="package-table">
                    <thead>
                        <tr>
                            <th class="date-column">
                                Date Delivered
                            </th>

                            <th class="time-column">
                                Time Delivered
                            </th>

                            <th class="barcode-column">
                                Tracking Number
                            </th>

                            <th class="person-column">
                                Delivered By
                            </th>

                            <th class="delivered-to-column">
                                Delivered To
                            </th>

                            <th class="carrier-column">
                                Carrier
                            </th>

                            <th class="status-column">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($packages)): ?>
                            <tr>
                                <td colspan="7" class="empty-table">
                                    No packages were found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($packages as $package): ?>
                                <?php
                                if ($package['delivered_status'] === 'delivered') {
                                    $isDelivered = 'delivered';
                                } elseif ($package['delivered_status'] === 'received') {
                                    $isDelivered = 'received';
                                } else {
                                    $isDelivered = 'pending';
                                }
                                /*
                                $isDelivered =
                                    $package['delivered_status'] === 'delivered' /* ||
                                    $package['delivered_status'] === 't' ||
                                    $package['delivered_status'] === '1' ||
                                    $package['delivered_status'] === 1;
                                    */
                                ?>

                                <tr
                                    class="package-row"
                                    tabindex="0"
                                    role="button"
                                    aria-expanded="false"
                                    data-barcode="<?= escapeHtml($package['barcode']) ?>">
                                    <td>
                                        <?php if (empty($package['delivered_date'])): ?>
                                            <span class="empty-value">--</span>
                                        <?php else: ?>
                                            <?= escapeHtml(
                                                date(
                                                    'm/d/Y',
                                                    strtotime($package['delivered_date'])
                                                )
                                            ) ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (empty($package['delivered_time'])): ?>
                                            <span class="empty-value">--</span>
                                        <?php else: ?>
                                            <?= escapeHtml(
                                                date(
                                                    'g:i A',
                                                    strtotime($package['delivered_time'])
                                                )
                                            ) ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="barcode-value">
                                            <?= displayValue($package['barcode']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= displayValue($package['delivered_by']) ?>
                                    </td>

                                    <td>
                                        <?= displayValue($package['delivered_to']) ?>
                                    </td>

                                    <td>
                                        <?php
                                        $carrier = trim(
                                            (string)($package['carrier'] ?? '')
                                        );
                                        ?>

                                        <?php if (
                                            $carrier === '' ||
                                            strtolower($carrier) === 'unknown'
                                        ): ?>
                                            <span class="empty-value">--</span>
                                        <?php else: ?>
                                            <?= escapeHtml(
                                                match (strtolower($carrier)) {
                                                    'usps' => 'USPS',
                                                    'ups' => 'UPS',
                                                    'fedex' => 'FedEx',
                                                    'amazon' => 'Amazon',
                                                    'gofo' => 'GOFO',
                                                    'ontrac' => 'OnTrac',
                                                    'gls' => 'GLS',
                                                    'custom' => 'Custom',
                                                    default => ucfirst($carrier),
                                                }
                                            ) ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($isDelivered === 'delivered'): ?>
                                            <span class="status status-delivered">
                                                Delivered
                                            </span>
                                        <?php elseif ($isDelivered === 'received'): ?>
                                            <span class="status status-received">
                                                Received
                                            </span>
                                        <?php else: ?>
                                            <span class="status status-pending">
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <tr
                                    class="package-detail-row"
                                    id="details-<?= escapeHtml($package['barcode']) ?>"
                                    hidden>
                                    <td colspan="7">
                                        <div class="package-detail-content">
                                            Loading package details...
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <footer class="table-footer">
                <div class="result-count">
                    <?php if ($totalRows === 0): ?>
                        0 packages
                    <?php else: ?>
                        Showing <?= $startingRow ?>
                        –<?= $endingRow ?>
                        of <?= $totalRows ?> packages
                    <?php endif; ?>
                </div>

                <div class="pagination-area">
                    <nav
                        class="pagination"
                        aria-label="Package list pagination">
                        <?php if ($page > 1): ?>
                            <a
                                class="page-link"
                                href="<?= escapeHtml(
                                            buildPageUrl(
                                                $page - 1,
                                                $perPage,
                                                $search,
                                                $packageType
                                            )
                                        ) ?>"
                                aria-label="Previous page">
                                &lsaquo;
                            </a>
                        <?php else: ?>
                            <span
                                class="page-disabled"
                                aria-hidden="true">
                                &lsaquo;
                            </span>
                        <?php endif; ?>

                        <?php if ($paginationStart > 1): ?>
                            <a
                                class="page-link"
                                href="<?= escapeHtml(
                                            buildPageUrl(
                                                1,
                                                $perPage,
                                                $search,
                                                $packageType
                                            )
                                        ) ?>">
                                1
                            </a>

                            <?php if ($paginationStart > 2): ?>
                                <span class="page-ellipsis">
                                    &hellip;
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php
                        for (
                            $pageNumber = $paginationStart;
                            $pageNumber <= $paginationEnd;
                            $pageNumber++
                        ):
                        ?>
                            <a
                                class="
                                    page-link
                                    <?= $pageNumber === $page
                                        ? 'active'
                                        : '' ?>
                                "
                                href="<?= escapeHtml(
                                            buildPageUrl(
                                                $pageNumber,
                                                $perPage,
                                                $search,
                                                $packageType
                                            )
                                        ) ?>"
                                <?= $pageNumber === $page
                                    ? 'aria-current="page"'
                                    : '' ?>>
                                <?= $pageNumber ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($paginationEnd < $totalPages): ?>
                            <?php if (
                                $paginationEnd < $totalPages - 1
                            ): ?>
                                <span class="page-ellipsis">
                                    &hellip;
                                </span>
                            <?php endif; ?>

                            <a
                                class="page-link"
                                href="<?= escapeHtml(
                                            buildPageUrl(
                                                $totalPages,
                                                $perPage,
                                                $search,
                                                $packageType
                                            )
                                        ) ?>">
                                <?= $totalPages ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a
                                class="page-link"
                                href="<?= escapeHtml(
                                            buildPageUrl(
                                                $page + 1,
                                                $perPage,
                                                $search,
                                                $packageType
                                            )
                                        ) ?>"
                                aria-label="Next page">
                                &rsaquo;
                            </a>
                        <?php else: ?>
                            <span
                                class="page-disabled"
                                aria-hidden="true">
                                &rsaquo;
                            </span>
                        <?php endif; ?>
                    </nav>

                    <form method="get" class="page-size-form">
                        <input
                            type="hidden"
                            name="page"
                            value="1">

                        <input
                            type="hidden"
                            name="search"
                            value="<?= escapeHtml($search) ?>">

                        <input
                            type="hidden"
                            name="filter_packages"
                            value="<?= escapeHtml($packageType) ?>">

                        <select
                            name="per_page"
                            class="page-size-select"
                            aria-label="Packages per page"
                            onchange="this.form.submit()">
                            <?php foreach (
                                $allowedPageSizes as $pageSize
                            ): ?>
                                <option
                                    value="<?= $pageSize ?>"
                                    <?= $pageSize === $perPage
                                        ? 'selected'
                                        : '' ?>>
                                    <?= $pageSize ?> / page
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </footer>
        </section>
    </main>
    <div
        class="package-modal"
        id="photoModal"
        aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>

        <section
            class="modal-window photo-modal-window"
            role="dialog"
            aria-modal="true"
            aria-labelledby="photoModalTitle">
            <header class="modal-header">
                <h2 id="photoModalTitle">Delivery Photo</h2>

                <button
                    type="button"
                    class="modal-close"
                    data-close-modal
                    aria-label="Close">
                    &times;
                </button>
            </header>

            <div class="modal-body photo-modal-body">
                <img
                    id="fullDeliveryPhoto"
                    class="full-delivery-photo"
                    alt="Delivery photo">

                <p
                    id="photoUnavailable"
                    class="photo-unavailable"
                    hidden>
                    No delivery photo is available.
                </p>
            </div>

            <footer class="modal-footer">
                <button
                    type="button"
                    class="secondary-button"
                    data-close-modal>
                    Close
                </button>
            </footer>
        </section>
    </div>

    <div
        class="package-modal"
        id="historyModal"
        aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>

        <section
            class="modal-window history-modal-window"
            role="dialog"
            aria-modal="true"
            aria-labelledby="historyModalTitle">
            <header class="modal-header">
                <h2 id="historyModalTitle">Package History</h2>

                <button
                    type="button"
                    class="modal-close"
                    data-close-modal
                    aria-label="Close">
                    &times;
                </button>
            </header>

            <div
                class="modal-body history-modal-body"
                id="historyModalBody"></div>

            <footer class="modal-footer">
                <button
                    type="button"
                    class="secondary-button"
                    data-close-modal>
                    Close
                </button>
            </footer>
        </section>
    </div>

    <div
        class="package-modal"
        id="mapModal"
        aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>

        <section
            class="modal-window map-modal-window"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mapModalTitle">
            <header class="modal-header">
                <h2 id="mapModalTitle">Package Location</h2>

                <button
                    type="button"
                    class="modal-close"
                    data-close-modal
                    aria-label="Close">
                    &times;
                </button>
            </header>

            <div class="modal-body map-modal-body">
                <div
                    id="deliveryMap"
                    class="delivery-map"
                    aria-label="Map showing the package delivery location">
                    <div class="map-loading">
                        Click “View Package Location” to load the map.
                    </div>
                </div>

                <div
                    id="mapCoordinateSummary"
                    class="map-coordinate-summary"
                    hidden>
                    <span>
                        <strong>Latitude:</strong>
                        <span id="mapLatitudeValue">--</span>
                    </span>

                    <span>
                        <strong>Longitude:</strong>
                        <span id="mapLongitudeValue">--</span>
                    </span>
                </div>
            </div>

            <footer class="modal-footer">
                <a
                    id="openGoogleMapsLink"
                    class="open-google-maps-link"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                    hidden>
                    Open in Google Maps
                </a>

                <button
                    type="button"
                    class="secondary-button"
                    data-close-modal>
                    Close
                </button>
            </footer>
        </section>
    </div>

    <script>
        "use strict";

        const packageRows = document.querySelectorAll(".package-row");

        const photoModal = document.getElementById("photoModal");
        const historyModal = document.getElementById("historyModal");
        const mapModal = document.getElementById("mapModal");
        const mapModalTitle = document.getElementById("mapModalTitle");

        const deliveryMap = document.getElementById("deliveryMap");
        const mapCoordinateSummary =
            document.getElementById("mapCoordinateSummary");
        const mapLatitudeValue =
            document.getElementById("mapLatitudeValue");
        const mapLongitudeValue =
            document.getElementById("mapLongitudeValue");
        const openGoogleMapsLink =
            document.getElementById("openGoogleMapsLink");

        const googleMapsApiKey =
            <?= json_encode($googleMapsApiKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        let googleMapsLoadingPromise = null;
        let packageLocationMap = null;
        let packageLocationMarker = null;

        const fullDeliveryPhoto =
            document.getElementById("fullDeliveryPhoto");

        const photoUnavailable =
            document.getElementById("photoUnavailable");

        const historyModalBody =
            document.getElementById("historyModalBody");

        const packageCache = new Map();

        function escapeHtml(value) {
            const div = document.createElement("div");
            div.textContent = value ?? "";
            return div.innerHTML;
        }

        function displayValue(value) {
            if (
                value === null ||
                value === undefined ||
                String(value).trim() === ""
            ) {
                return "--";
            }

            return escapeHtml(String(value));
        }

        function getValidCoordinates(packageData) {
            const rawLatitude = packageData.latitude;
            const rawLongitude = packageData.longitude;

            if (
                rawLatitude === null ||
                rawLatitude === undefined ||
                String(rawLatitude).trim() === "" ||
                rawLongitude === null ||
                rawLongitude === undefined ||
                String(rawLongitude).trim() === ""
            ) {
                return null;
            }

            const latitude = Number(rawLatitude);
            const longitude = Number(rawLongitude);

            if (
                !Number.isFinite(latitude) ||
                !Number.isFinite(longitude) ||
                latitude < -90 ||
                latitude > 90 ||
                longitude < -180 ||
                longitude > 180
            ) {
                return null;
            }

            return {
                lat: latitude,
                lng: longitude
            };
        }

        function hasCoordinates(packageData) {
            return getValidCoordinates(packageData) !== null;
        }

        function loadGoogleMaps() {
            if (
                window.google &&
                window.google.maps
            ) {
                return Promise.resolve();
            }

            if (googleMapsLoadingPromise) {
                return googleMapsLoadingPromise;
            }

            if (!googleMapsApiKey) {
                return Promise.reject(
                    new Error(
                        "The Google Maps API key is not configured."
                    )
                );
            }

            googleMapsLoadingPromise = new Promise(
                (resolve, reject) => {
                    const callbackName =
                        "__initializePackageMapsApi";

                    window[callbackName] = () => {
                        delete window[callbackName];
                        resolve();
                    };

                    const script =
                        document.createElement("script");

                    script.src =
                        "https://maps.googleapis.com/maps/api/js" +
                        "?key=" +
                        encodeURIComponent(googleMapsApiKey) +
                        "&loading=async" +
                        "&libraries=marker" +
                        "&callback=" +
                        callbackName;

                    script.async = true;
                    script.defer = true;

                    script.onerror = () => {
                        delete window[callbackName];
                        googleMapsLoadingPromise = null;

                        reject(
                            new Error(
                                "Google Maps could not be loaded."
                            )
                        );
                    };

                    document.head.appendChild(script);
                }
            );

            return googleMapsLoadingPromise;
        }

        async function showPackageLocation(packageData) {
            const coordinates =
                getValidCoordinates(packageData);

            if (!coordinates) {
                return;
            }

            mapModalTitle.textContent =
                packageData.barcode ?
                `Package Location — ${packageData.barcode}` :
                "Package Location";

            mapLatitudeValue.textContent =
                String(coordinates.lat);

            mapLongitudeValue.textContent =
                String(coordinates.lng);

            mapCoordinateSummary.hidden = false;

            openGoogleMapsLink.href =
                "https://www.google.com/maps/search/?api=1&query=" +
                encodeURIComponent(
                    coordinates.lat + "," + coordinates.lng
                );

            openGoogleMapsLink.hidden = false;

            deliveryMap.innerHTML = `
                <div class="map-loading">
                    Loading package location...
                </div>
            `;

            openModal(mapModal);

            try {
                await loadGoogleMaps();

                const {
                    Map
                } =
                await google.maps.importLibrary("maps");

                deliveryMap.innerHTML = "";

                packageLocationMap = new Map(
                    deliveryMap, {
                        center: coordinates,
                        zoom: 18,
                        mapTypeControl: true,
                        streetViewControl: true,
                        fullscreenControl: true
                    }
                );

                packageLocationMarker =
                    new google.maps.Marker({
                        map: packageLocationMap,
                        position: coordinates,
                        title: "Package delivery location"
                    });

                requestAnimationFrame(() => {
                    packageLocationMap.setCenter(coordinates);
                });
            } catch (error) {
                console.error(
                    "Unable to load package location:",
                    error
                );

                deliveryMap.innerHTML = `
                    <div class="map-error">
                        ${escapeHtml(
                            error instanceof Error
                                ? error.message
                                : "Unable to load the package location."
                        )}
                    </div>
                `;
            }
        }

        function openModal(modal) {
            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open");
        }

        function closeModal(modal) {
            modal.classList.remove("open");
            modal.setAttribute("aria-hidden", "true");

            if (modal === mapModal) {
                mapModalTitle.textContent = "Package Location";
                openGoogleMapsLink.hidden = true;
                openGoogleMapsLink.removeAttribute("href");
                mapCoordinateSummary.hidden = true;
                mapLatitudeValue.textContent = "--";
                mapLongitudeValue.textContent = "--";
            }

            if (!document.querySelector(".package-modal.open")) {
                document.body.classList.remove("modal-open");
            }
        }

        document.querySelectorAll("[data-close-modal]").forEach((button) => {
            button.addEventListener("click", () => {
                const modal = button.closest(".package-modal");

                if (modal) {
                    closeModal(modal);
                }
            });
        });

        document.addEventListener("keydown", (event) => {
            if (event.key !== "Escape") {
                return;
            }

            document
                .querySelectorAll(".package-modal.open")
                .forEach(closeModal);
        });

        async function retrievePackage(barcode) {
            if (packageCache.has(barcode)) {
                return packageCache.get(barcode);
            }

            const response = await fetch(
                `http://localhost:3000/api/retrieve-package.php?barcode=${encodeURIComponent(barcode)}`, {
                    method: "GET",
                    headers: {
                        Accept: "application/json"
                    }
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.error || "Unable to retrieve package."
                );
            }

            packageCache.set(barcode, result.data);

            return result.data;
        }

        function createSignatureHtml(packageData) {
            if (!packageData.sig_url) {
                return `
            <div class="no-signature">
                No signature available
            </div>
        `;
            }

            return `
        <img
            class="package-signature-image"
            src="${escapeHtml(packageData.sig_url)}"
            alt="Delivery signature"
        >
    `;
        }

        function createPackageDetails(packageData) {
            const locationButton = hasCoordinates(packageData) ?
                `
            <button
                type="button"
                class="package-action-button location-button"
                data-latitude="${escapeHtml(packageData.latitude)}"
                data-longitude="${escapeHtml(packageData.longitude)}"
            >
                View Package Location
            </button>
        ` :
                "";

            const viewPhotoDisabled = packageData.photo_url ?
                "" :
                "disabled";

            return `
        <div class="package-detail-layout">
            <div class="package-detail-icon">
                <div class="package-placeholder-icon">
                    PKG
                </div>
            </div>

            <div class="package-main-details">
                <div class="package-detail-heading">
                    <h2 class="package-detail-barcode">
                        ${displayValue(packageData.barcode)}
                    </h2>

                    <span class="status status-delivered">
                        Delivered
                    </span>
                </div>

                <div class="package-detail-grid">
                    <div class="package-detail-field">
                        <strong>Delivered By:</strong>
                        <span>
                            ${displayValue(packageData.delivered_by)}
                        </span>
                    </div>

                    <div class="package-detail-field">
                        <strong>Date Delivered:</strong>
                        <span>
                            ${displayValue(packageData.delivered_date)}
                        </span>
                    </div>

                    <div class="package-detail-field">
                        <strong>Time Delivered:</strong>
                        <span>
                            ${displayValue(packageData.delivered_time)}
                        </span>
                    </div>

                    <div class="package-detail-field">
                        <strong>Delivered To:</strong>
                        <span>
                            ${displayValue(packageData.delivered_to)}
                        </span>
                    </div>

                    <div class="package-detail-field">
                        <strong>Note:</strong>
                        <span>
                            ${displayValue(packageData.comments)}
                        </span>
                    </div>

                </div>

                <div class="package-signature-section">
                    <strong>Delivery Signature:</strong>
                    ${createSignatureHtml(packageData)}
                </div>
            </div>

            <aside class="package-action-panel">
                <button
                    type="button"
                    class="package-action-button view-photo-button"
                    ${viewPhotoDisabled}
                >
                    View Photo
                </button>

                <button
                    type="button"
                    class="package-action-button history-button"
                >
                    Complete History
                </button>

                ${locationButton}

                <a
                    class="package-action-button"
                    href="/api/package-pdf.php?barcode=${encodeURIComponent(packageData.barcode)}"
                    target="_blank"
                    rel="noopener"
                >
                    Download PDF
                </a>

                <button
                    type="button"
                    class="package-close-link"
                >
                    Close
                </button>
            </aside>
        </div>
    `;
        }

        function createHistoryHtml(packageData) {
            const signatureHtml = packageData.sig_url ?
                `
            <img
                class="history-signature"
                src="${escapeHtml(packageData.sig_url)}"
                alt="Delivery signature"
            >
        ` :
                "--";

            const photoHtml = packageData.photo_url ?
                `
            <img
                class="history-photo"
                src="${escapeHtml(packageData.photo_url)}"
                alt="Delivery photo"
            >
        ` :
                "--";

            return `
        <div class="history-summary">
            <div class="history-package-icon">
                PKG
            </div>

            <div>
                <h3 class="history-barcode">
                    ${displayValue(packageData.barcode)}
                </h3>

                <div class="history-subtitle">
                    Package
                </div>
            </div>
        </div>

        <div class="history-information-grid">
            <div class="history-field">
                <strong>Delivered By:</strong>
                <span>
                    ${displayValue(packageData.delivered_by)}
                </span>
            </div>

            <div class="history-field">
                <strong>Date Delivered:</strong>
                <span>
                    ${displayValue(packageData.delivered_date)}
                </span>
            </div>

            <div class="history-field">
                <strong>Time Delivered:</strong>
                <span>
                    ${displayValue(packageData.delivered_time)}
                </span>
            </div>

            <div class="history-field">
                <strong>Note:</strong>
                <span>
                    ${displayValue(packageData.comments)}
                </span>
            </div>

            <div class="history-field">
                <strong>To:</strong>
                <span>
                    ${displayValue(packageData.delivered_to)}
                </span>
            </div>

            <div class="history-field">
                <strong>Coordinates:</strong>
                <span>
                    ${
                        hasCoordinates(packageData)
                            ? `${displayValue(packageData.latitude)}, ${displayValue(packageData.longitude)}`
                            : "--"
                    }
                </span>
            </div>

            <div class="history-field">
                <strong>Delivery Signature:</strong>
                ${signatureHtml}
            </div>

            <div class="history-field">
                <strong>Photo:</strong>
                ${photoHtml}
            </div>
        </div>
    `;
        }

        function attachDetailEvents(
            detailContainer,
            packageData,
            packageRow,
            detailRow
        ) {
            const photoButton =
                detailContainer.querySelector(".view-photo-button");

            const historyButton =
                detailContainer.querySelector(".history-button");

            const locationButton =
                detailContainer.querySelector(".location-button");

            const closeButton =
                detailContainer.querySelector(".package-close-link");

            photoButton?.addEventListener("click", (event) => {
                event.stopPropagation();

                if (packageData.photo_url) {
                    fullDeliveryPhoto.src = packageData.photo_url;
                    fullDeliveryPhoto.hidden = false;
                    photoUnavailable.hidden = true;
                } else {
                    fullDeliveryPhoto.removeAttribute("src");
                    fullDeliveryPhoto.hidden = true;
                    photoUnavailable.hidden = false;
                }

                openModal(photoModal);
            });

            historyButton?.addEventListener("click", (event) => {
                event.stopPropagation();

                historyModalBody.innerHTML =
                    createHistoryHtml(packageData);

                openModal(historyModal);
            });

            locationButton?.addEventListener("click", (event) => {
                event.stopPropagation();
                showPackageLocation(packageData);
            });

            closeButton?.addEventListener("click", (event) => {
                event.stopPropagation();

                detailRow.hidden = true;
                packageRow.classList.remove("selected");
                packageRow.setAttribute("aria-expanded", "false");
            });
        }

        async function togglePackageRow(packageRow) {
            const barcode = packageRow.dataset.barcode;
            const detailRow =
                document.getElementById(`details-${barcode}`);

            if (!detailRow) {
                return;
            }

            const isOpen = !detailRow.hidden;

            document
                .querySelectorAll(".package-detail-row")
                .forEach((row) => {
                    if (row !== detailRow) {
                        row.hidden = true;
                    }
                });

            document
                .querySelectorAll(".package-row.selected")
                .forEach((row) => {
                    if (row !== packageRow) {
                        row.classList.remove("selected");
                        row.setAttribute("aria-expanded", "false");
                    }
                });

            if (isOpen) {
                detailRow.hidden = true;
                packageRow.classList.remove("selected");
                packageRow.setAttribute("aria-expanded", "false");
                return;
            }

            detailRow.hidden = false;
            packageRow.classList.add("selected");
            packageRow.setAttribute("aria-expanded", "true");

            const detailContainer =
                detailRow.querySelector(".package-detail-content");

            detailContainer.innerHTML = `
        <div class="package-detail-loading">
            Loading package details...
        </div>
    `;

            try {
                const packageData = await retrievePackage(barcode);

                detailContainer.innerHTML =
                    createPackageDetails(packageData);

                attachDetailEvents(
                    detailContainer,
                    packageData,
                    packageRow,
                    detailRow
                );
            } catch (error) {
                detailContainer.innerHTML = `
            <div class="package-detail-error">
                ${escapeHtml(error.message)}
            </div>
        `;
            }
        }

        packageRows.forEach((packageRow) => {
            packageRow.addEventListener("click", () => {
                togglePackageRow(packageRow);
            });

            packageRow.addEventListener("keydown", (event) => {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    togglePackageRow(packageRow);
                }
            });
        });
    </script>
</body>

</html>