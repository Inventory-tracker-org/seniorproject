<?php
include_once("../config.php");
check_auth();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Asset Management</title>
    <!-- Including jQuery is required. -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script type="text/javascript" src="script.js"></script>
    <?php include_once("../navbar.php"); ?>
</head>
<style>
    * {
        margin: 0;
    }

    .asset-search {
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        background-color: white;
        border: lightgray 1px solid;
        border-radius: 30px;
        margin: 20px auto auto;
        padding: 10px 50px;
        width: 60rem;
    }

    #submit-btn:hover {
        transform: translateY(-2px);
    }

    .search-input {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        transition: border-color 0.3s ease;
        background: #f8fafc;
    }

    .search-input:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
    }

    #dept-id-search,
    #bldg-id-search,
    #status-filter,
    #price-value,
    #price-filter,
    .filter-select {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        cursor: pointer;
    }

    #price-filter:focus,
    #status-filter:focus,
    .filter-select:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
    }

    .search-button {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .search-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
</style>

<body>
    <div class="has-search">
        <?php
        $search = isset($_GET['search']) ? $_GET['search'] : 'all';
        if (isset($_GET['query'])) {
            $search = $_GET['query'];
        }
        $offset = isset($_GET['offset']) ? $_GET['offset'] : 1;
        $category = isset($_GET['categories']) ? $_GET['categories'] : 'assets';
        $status = isset($_GET['statusFilter']) ? $_GET['statusFilter'] : 'In Service';
        $bldg_id_search = isset($_GET['bldg_id_search']) ? $_GET['bldg_id_search'] : '';
        if ($search === NULL) {
            $offset = 1;
        }
        ?>
        <!-- Search box. -->
        <div class="asset-search">
            <input class="search-input" type="hidden" name="offset" id="offset" value="<?= $offset ?>">
            <div class="section">
                <label class='search-label' id='search-label'></label>
                <input class="search-input" type="text" name="search" id="search" value="<?= $search ?>" placeholder="Search for an asset..." style="padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                <div style="display:grid;">
                    <label class="search-label">Search Type</label>
                    <select class="filter-select" name="categories" id="categories">
                        <option value="assets">Assets</option>
                        <option value="buildings">Buildings</option>
                        <option value="departments">Departments</option>
                        <option value="users">Users</option>
                    </select>
                </div>
            </div>
            <div class="section filter-bldg">
                <label class="filter-bldg">Building ID</label>
                <input type="number" class="filter-bldg" id="bldg-id-search" name="bldg-id-search" placeholder="Enter Building ID" value="<?= $bldg_id_search ?>" />
            </div>
            <div class="section filter-assets">
                <label class="filter-assets">Status</label>
                <select class="filter-assets" name="status-filter" id="status-filter">
                    <option value="6">Old Management</option>
                    <option value="4">Management</option>
                    <!-- <option value="Disposed">Disposed</option> -->
                </select>
            </div>
            <!--
            <div class="section filter-assets">

                <label class="filter-assets">Price Amount</label>
                <div class='price'>
                    <select class="filter-assets" name="price-operator" id="price-filter">
                        <option value=">">&gt;</option>
                        <option value="<">&lt;</option>
                        <option value="=">=</option>
                        <option value=">=">&ge;</option>
                        <option value="<=">&le;</option>
                    </select>
                    <input type="number" class="filter-assets" id="price-value" name="price-value" placeholder="Enter price" />
                </div>
            </div>
            <div class="section filter-assets">

                <label class="filter-assets">Department ID</label>
                <input type="text" class="filter-assets" id="dept-id-search" name="dept-id-search" placeholder="Enter Department ID" />
            </div>
-->
            <div class="section">

                <button class="search-button" id="search-btn">Search</button>
            </div>
            <!-- <button class="filter-assets search-button" id="audit-btn" name="audit">Audit</button> -->


        </div>
        <div style="position: absolute;left: 50%;transform: translate(-50%,-50%);width:max-content;">

            <div class='checkbox-wrapper-13'>

                <label for="asset_name" class="filter-assets" id="asset_name_label">Asset Name</label>
                <input type="checkbox" class="filter-assets" id="asset_name" value="asset_name" name="asset_name">

                <label for="dept_id" class="filter-assets" id="dept_id_label">Department ID</label>
                <input type="checkbox" class="filter-assets" id="dept_id" value="dept_id" name="dept_id">
                <label for="room_tag" class="filter-assets" id="room_tag_label">Room Tag</label>
                <input type="checkbox" class="filter-assets" id="room_tag" value="room_tag" name="room_tag">
                <label for="room_loc" class="filter-room" id="room_loc_label">Room Number</label>
                <input type="checkbox" id="room_loc" class="filter-room" value="room_loc" name="room_loc">
                <label for="asset_sn" class="filter-assets" id="asset_sn_label">Serial Number</label>
                <input type="checkbox" class="filter-assets" id="asset_sn" value="asset_sn" name="asset_sn">
                <label for="asset_price" class="filter-assets" id="asset_price_label">Cost</label>
                <input type="checkbox" class="filter-assets" id="asset_price" value="asset_price" name="asset_price">
                <label for="asset_po" class="filter-assets" id="asset_po_label">Purchase Order</label>
                <input type="checkbox" class="filter-assets" id="asset_po" value="asset_po" name="asset_po">
                <label for="bldg_id" class="filter-bldg" id="bldg_id_label">Building ID</label>
                <input type="checkbox" class="filter-bldg" id="bldg_id" value="bldg_id" name="bldg_id">
                <label for="bldg_name" class="filter-bldg" id="bldg_name_label">Building Name</label>
                <input type="checkbox" class="filter-bldg" id="bldg_name" value="bldg_name" name="bldg_name">
                <label for="notes" class="filter-assets" id="notes_label">Notes</label>
                <input type="checkbox" class="filter-assets" id="notes" value="notes" name="notes">
            </div>
        </div>
        <br>
        <!-- Suggestions will be displayed in below div. -->
        <script>
            searchTrigger();
        </script>
        <div id="display"></div>

    </div>
</body>

</html>