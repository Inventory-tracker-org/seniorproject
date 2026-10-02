//Getting value from "ajax.php".
function fill(Value) {
//Assigning value to "search" div in "search.php" file.
    $('#search').val(Value);
//Hiding "display" div in "search.php" file.
    $('#display').hide();
}
function searchTrigger() {
    //Assigning search box value to javascript variable named as "name".
    var search = $('#search').val();
    var offset = $('#offset').val();
    var categories = $('#categories').val();
    var statusFilter = $('#status-filter').val();
    var price = $('#price-value').val();
    var price_operation = $('#price-filter').val();
    var box_name = $('#asset_name').prop('checked');
    var dept_id = $('#dept_id').prop('checked');
    var dept_id_search = $('#dept-id-search').val();
    var room_tag = $('#room_tag').prop('checked');
    var room_loc = $('#room_loc').prop('checked');
    var asset_sn = $('#asset_sn').prop('checked');
    var asset_price = $('#asset_price').prop('checked');
    var asset_po = $('#asset_po').prop('checked');
    var bldg_id = $('#bldg_id').prop('checked');
    var bldg_name = $('#bldg_name').prop('checked');
    var bldg_id_search = $('#bldg-id-search').val();
    var notes = $('#notes').prop('checked');

    //Validating, if "name" is empty.
    if (name == "") {
        //Assigning empty value to "display" div in "search.php" file.
        name = "all";
        //$("#display").html("");

    }
    if (name.length >= 3) {
        //AJAX is called.
        $.ajax({
            //AJAX type is "Post".
            type: "POST",
            //Data will be sent to "ajax.php".
            url: "Ajax.php",
            //Data, that will be sent to "ajax.php".
            data: {
                offset: offset,
                categories: categories,
                statusFilter: statusFilter,
                box_name: box_name,
                asset_price: price,
                price_operation: price_operation,
                dept_id: dept_id,
                dept_id_search: dept_id_search,
                room_tag: room_tag,
                room_loc: room_loc,
                asset_sn: asset_sn,
                asset_price_check: asset_price,
                asset_po: asset_po,
                bldg_id: bldg_id,
                bldg_name: bldg_name,
                bldg_id_search: bldg_id_search,
                notes: notes,

                //Assigning value of "name" into "search" variable.
                search: search
            },
            //If result found, this funtion will be called.
            success: function(html) {
                //Assigning result to "display" div in "search.php" file.
                $("#display").html(html).show();
            }
        });
    }
}
function auditTrigger() {
    var search = $('#search').val();
    var statusFilter = $('#status-filter').val();
    var price = $('#price-value').val();
    var price_operation = $('#price-filter').val();
    var box_name = $('#asset_name').prop('checked');
    var dept_id = $('#dept_id').prop('checked');
    var dept_id_search = $('#dept-id-search').val();
    var room_tag = $('#room_tag').prop('checked');
    var room_loc = $('#room_loc').prop('checked');
    var asset_sn = $('#asset_sn').prop('checked');
    var asset_price = $('#asset_price').prop('checked');
    var asset_po = $('#asset_po').prop('checked');
    var bldg_id = $('#bldg_id').prop('checked');
    var bldg_name = $('#bldg_name').prop('checked');
    var bldg_id_search = $('#bldg-id-search').val();
    var audit = true;

    $.ajax({
        type: "POST",
        url: "Ajax.php",
        data: {
            statusFilter: statusFilter,
            box_name: box_name,
            asset_price: price,
            price_operation: price_operation,
            dept_id: dept_id,
            dept_id_search: dept_id_search,
            room_tag: room_tag,
            room_loc: room_loc,
            asset_sn: asset_sn,
            asset_price_check: asset_price,
            asset_po: asset_po,
            bldg_id: bldg_id,
            bldg_name: bldg_name,
            audit: audit,
            bldg_id_search: bldg_id_search,
            search: search
        },
        dataType: 'json',  
        success: function(data) {
            const jsonString = encodeURIComponent(JSON.stringify(data));

            window.location.href = "https://dataworks-7b7x.onrender.com/audit/db_audit.php";
        }
    });
}
function searchTriggerViaAjax(
    offset
) {
    var search = $('#search').val();
    var categories = $('#categories').val();
    var statusFilter = $('#status-filter').val();
    var price = $('#price-value').val();
    var price_operation = $('#price-filter').val();
    var box_name = $('#asset_name').prop('checked');
    var dept_id = $('#dept_id').prop('checked');
    var dept_id_search = $('#dept-id-search').val();
    var room_tag = $('#room_tag').prop('checked');
    var room_loc = $('#room_loc').prop('checked');
    var asset_sn = $('#asset_sn').prop('checked');
    var asset_price = $('#asset_price').prop('checked');
    var asset_po = $('#asset_po').prop('checked');
    var bldg_id = $('#bldg_id').prop('checked');
    var bldg_name = $('#bldg_name').prop('checked');
    var bldg_id_search = $('#bldg-id-search').val();
    var notes = $('#notes').prop('checked');
    $.ajax({
        //AJAX type is "Post".
        type: "POST",
        //Data will be sent to "ajax.php".
        url: "Ajax.php",
        //Data, that will be sent to "ajax.php".
        data: {
            offset: offset,
            categories: categories,
            statusFilter: statusFilter,
            box_name: box_name,
            asset_price: price,
            price_operation: price_operation,
            dept_id: dept_id,
            dept_id_search: dept_id_search,
            room_tag: room_tag,
            room_loc: room_loc,
            asset_sn: asset_sn,
            asset_price_check: asset_price,
            asset_po: asset_po,
            bldg_id: bldg_id,
            bldg_name: bldg_name,
            bldg_id_search: bldg_id_search,
            notes: notes,

            //Assigning value of "name" into "search" variable.
            search: search
        },
        //If result found, this funtion will be called.
        success: function(html) {
            //Assigning result to "display" div in "search.php" file.
            $("#display").html(html).show();
        }
    });
}

$(document).ready(function() {
    //On pressing a key on "Search box" in "search.php" file. This function will be called.
    $('#search').on('blur', function() {
        $('#offset').val(1);
    });
    $('#search-btn').click(function() {
        $('#offset').val(1);
    });

    $("#search").on("keydown", function(e) {
        if (e.key === "Enter") {
            searchTrigger();
        }
    });
    $("#categories").change(searchTrigger);
    $('#search-btn').click(searchTrigger);

    $('#audit-btn').click(auditTrigger);


    const status = localStorage.getItem('status-filter');
    if (status) {
        $('#status-filter').val(status);
    }

    $('#status-filter').on('change', function () {
        localStorage.setItem('status-filter', $(this).val());
    });

    const category = localStorage.getItem('categories');  
    if (category) {
        $('#categories').val(category);
    }

    $('#categories').on('change', function () {
        localStorage.setItem('categories', $(this).val());
    });
    const price = localStorage.getItem('price-value');  
    if (price) {
        $('#price-value').val(price);
    }

    $('#price-value').on('change', function () {
        localStorage.setItem('price-value', $(this).val());
    });
    const price_operation = localStorage.getItem('price-filter');  
    if (price_operation) {
        $('#price-filter').val(price_operation);
    }

    $('#price-filter').on('change', function () {
        localStorage.setItem('price-filter', $(this).val());
    });
    const dept_id_search = localStorage.getItem('dept-id-search');
    if (dept_id_search) {
        $('#dept-id-search').val(dept_id_search);
    }

    $('#dept-id-search').on('change', function () {
        localStorage.setItem('dept-id-search', $(this).val());
    });
    const bldg_id_search = localStorage.getItem('bldg-id-search');
    if (bldg_id_search) {
        $('#bldg-id-search').val(bldg_id_search);
    }

    $('#bldg-id-search').on('change', function () {
        localStorage.setItem('bldg-id-search', $(this).val());
    });

    keepCheckboxValues('asset_name', '#asset_name');
    keepCheckboxValues('dept_id', '#dept_id');
    keepCheckboxValues('room_tag', '#room_tag');
    keepCheckboxValues('room_loc', '#room_loc');
    keepCheckboxValues('asset_sn', '#asset_sn');
    keepCheckboxValues('asset_price', '#asset_price');
    keepCheckboxValues('asset_po', '#asset_po');
    keepCheckboxValues('bldg_id', '#bldg_id');
    keepCheckboxValues('bldg_name', '#bldg_name');
    keepCheckboxValues('notes', '#notes');


    /*
    const asset = localStorage.getItem('asset_name');
    if (asset === 'true') {
        $('#asset_name').prop('checked', true);
    }
    $('#asset_name').on('change', function () {
        localStorage.setItem('asset_name', $(this).is(':checked'));
    });
    */
});
function removeItemsFromLocalStorage() {
        localStorage.removeItem('price-value');
        localStorage.removeItem('price-filter');
        localStorage.removeItem('dept_id');
        localStorage.removeItem('asset_price');
}
function addCheckboxes(jasset_class) {   
    $(jasset_class).show();
}

function keepCheckboxValues(asset_name, jasset_id) {
    const asset = localStorage.getItem(asset_name);
    if (asset === 'true') {
        $(jasset_id).prop('checked', true);
    }
    $(jasset_id).on('change', function () {
        localStorage.setItem(asset_name, $(this).is(':checked'));
    });
}
function removeCheckbox(jasset_class) {
    $(jasset_class).hide();
}
function getCheckboxValue(asset_id) {
    searchTrigger();
}

