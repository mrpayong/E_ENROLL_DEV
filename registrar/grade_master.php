<?php
set_time_limit(0);
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;


## page header title
$general_page_title = "Grade Master";
$get_user_value = strtoupper($_GET['none'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
]; ## ['label' => $page_header_title, 'url' => '']

## verify user access
if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL . "index.php"); //balik sa login then sa login aalamain kung anung role at saang page landing dapat
    exit();
}


## fiscal year
$default_get_id = get_school_year();
$filter_year = array(array('title' => "ALL RECORDS", 'id' => array('all')));
$fy_list = array();
$fy_list['all'] = array("school_year_id" => "ALL", "school_year" => "ALL YEAR", "sem" => "SEMESTER");
$school_year = "";
$sem = "";
if ($query = call_mysql_query("SELECT school_year_id, school_year, sem FROM school_year ORDER BY school_year_id DESC")) {
    while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
        $data = array_map('strtoupper', $data);
        $fy_id = $data['school_year'] . "," . $data['sem'];
        array_push($filter_year, array('title' => $data['school_year'] . " - " . $data['sem'], 'id' => array($data['school_year'] . "," . $data['sem'])));
        $fy_list[$fy_id] = $data;
    }
}

if ($query = call_mysql_query("SELECT DISTINCT school_year, sem FROM final_grade ORDER BY school_year DESC, sem DESC")) {
    while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
        $data = array_map('strtoupper', $data);
        $fy_id = $data['school_year'] . "," . $data['sem'];
        if (!isset($fy_list[$fy_id])) {
            array_push($filter_year, array('title' => $data['school_year'] . " - " . $data['sem'], 'id' => array($data['school_year'] . "," . $data['sem'])));
            $fy_list[$fy_id] = $data;
        }
    }
}

## sort fiscal_year
usort($filter_year, function ($a, $b) {
    // Always put "ALL RECORDS" first
    if ($a['title'] === 'ALL RECORDS') return -1;
    if ($b['title'] === 'ALL RECORDS') return 1;

    // Extract year and semester
    preg_match('/(\d{4}-\d{4}),?(1ST|2ND)?/', $a['id'][0], $matchA);
    preg_match('/(\d{4}-\d{4}),?(1ST|2ND)?/', $b['id'][0], $matchB);

    $yearA = $matchA[1] ?? '';
    $yearB = $matchB[1] ?? '';

    $semA = $matchA[2] ?? '';
    $semB = $matchB[2] ?? '';

    // Compare year descending
    if ($yearA !== $yearB) {
        return strcmp($yearB, $yearA);
    }

    // Compare semester: 2ND first, then 1ST
    if ($semA !== $semB) {
        return $semA === '2ND' ? -1 : 1;
    }

    return 0;
});
$filter_year = json_encode($filter_year);


$student_class_list = json_encode([], JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
$grade_rating = array();
$grade_rating = get_graderating(0);

$grade_data_rating = array();
if (!empty($grade_rating)) {
    foreach ($grade_rating as $_data) {
        $grade_data_rating[] = $_data['grade'];
    }
}

## uploaded logs
$path = DOMAIN_PATH . "/upload/logs/summary_import_grade.log";
$result = tailCustom($path, 100);
$record = array();
if (!empty($result)) {
    $record = explode("\n", $result);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php'; ## meta
    include_once DOMAIN_PATH . '/global/include_top.php'; ## links
    ?>
</head>
<style>
    #header_table {
        font-weight: bold;
    }

    /* .swal-wide {
        width: 900px !important;
    } */

    #table_project {
        font-size: 0.70rem;
    }

    #grade_log_table {
        font-size: 0.75rem;
    }

    .info-cell .label {
        display: inline-block;
        width: 180px;
        /* adjust based on longest label */
        font-weight: 600;
    }

    .info-cell .value {
        display: inline-block;
    }
</style>

<body>
    <div class="wrapper">
        <?php
        include_once DOMAIN_PATH . '/global/sidebar.php'; ## sidebar
        ?>

        <div class="main-panel">
            <?php
            include_once DOMAIN_PATH . '/global/header.php'; ## header
            ?>

            <div class="container">
                <div class="page-inner">
                    <?php
                    include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                    ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body pt-1">

                                    <div class="table-responsive-sm mt-2">

                                        <!-- <div id="profile_form_msg" class="alert alert-info" style="display: block;font-size:10pt">
                                            <span><strong>**Notes**</strong></span>
                                            <div class="row" id="notes_list">
                                                <div class="col-md-6">
                                                    <ul class="list-unstyled mb-0" id="notes_list">
                                                        <li>
                                                            <i class="fas fa-cog"></i> <b>Academic Year</b> - Allows you to select a specific academic year and semester.
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-upload" title="Import"></i> <b>Import Bulk Grade</b> - Adds new or transferee grades only. Existing grades cannot be updated.
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-pen"></i> <b>Editing Final Grades</b> - Editing the final grade does not automatically update the rating column.
                                                        </li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <ul class="list-unstyled mb-0" id="notes_list">
                                                        <li>
                                                            <i class="fas fa-lock" title="Submitted Grade"></i> <b>Submitted Grades</b> - System-generated, and only the grade fields can be edited.
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-times" title="Uploaded Grade"></i> <b>Delete Grades</b> - Manually uploaded grades, where deletion and some field edits are allowed.
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-credit-card" title="Credit Grade"></i> <b>Credit Code</b> - Enter the course code to apply credits to the course.
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </div> -->


                                        <div class="table table_project_div  table-bordered col-xl-12 mb-4  mt-3 p-0" style="min-width:600px;overflow:auto;">
                                            <div class="row col-sm-12 col-md-12 col-lg-12 p-0 m-0">
                                                <div class="col-sm-12  col-md-12 col-lg-12 ">
                                                    <div class="mb-3">
                                                        <button id="btn_select_year" class="btn btn-outline-primary btn-rounded btn-sm ml-1"> <i class="fas fa-cog"></i> Academic Year</button>
                                                        <button id="btn_edit_grades" class="btn btn-outline-dark btn-rounded btn-sm ml-1"> <i class="fas fa-save"></i> Save Grades</button>

                                                        <button type="button" id="btn_grade_log" class="btn btn-outline-dark btn-rounded btn-sm ml-1"> <i class="fas fa-list"></i> Grade Logs</button>
                                                        <button type="button" id="btn_import_grade" class="btn btn-outline-dark btn-sm ml-1 btn-rounded "><i class="fa fa-upload"></i>&ensp;Import Bulk Grade</button>

                                                    </div>
                                                    <div id="header_table" class="col-lg-12 mt-2"></div>

                                                    <div class="table-bordered mb-3" id="table_project"></div>
                                                    <div>
                                                        <button id="download-csv-proj" class="btn btn-sm btn-label-custom btn-rounded">Download CSV</button>
                                                        <button id="download-xlsx-proj" class="btn btn-sm btn-label-custom btn-rounded">Download EXCEL</button>
                                                        <button id="print-proj" class="btn btn-sm btn-label-custom btn-rounded">Print</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div> <!-- end col-->
                    </div>

                    <!-- save grade modal -->
                    <div class="modal fade" id="save_grades" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="bulkGradeBackdropLabel">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="bulkGradeBackdropLabel">Save Grades</h5>
                                    <button type="button" class="btn-close" id="close_csv_upload" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <form autocomplete="off" id="save_grade_import" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <div class="alert alert-info mx-1 fw-bolder text-info" role="alert">
                                            <i class="fas fa-info-circle"></i>&ensp;PLEASE REVIEW THE CHANGES, ENTER A GENERAL REASON, ATTACH AN IMAGE FILE, AND THEN CONFIRM THE PROCESS.
                                        </div>
                                        <div id="save_generate_table" style="overflow:auto;"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-rounded btn-label-danger" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-rounded btn-label-custom" id="btn_save_confirm">Confirm</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- grade logs table modal -->
                    <div class="modal fade" id="table_grade_log" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="bulkGradeBackdropLabel">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="bulkGradeBackdropLabel">Grade Logs</h5>
                                    <button type="button" class="btn-close" id="close_csv_upload" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div id="grade_log_table" style="overflow:auto;"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-rounded btn-label-danger" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- import bulk grade modal -->
                    <div class="modal fade" id="import_bulk_grade" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="bulkImportBackdropLabel">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="bulkImportBackdropLabel">Grade Import</h5>
                                    <button type="button" class="btn-close" id="close_csv_upload" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <form autocomplete="off" id="import_grade_form" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <a href="<?php echo BASE_URL; ?>registrar/download.php?attach=IMP_BLK_GRD" target="_blank" class="mb-3"><i class="fas fa-download"></i>&ensp;Download Template CSV File</a>
                                            </div>
                                            <div>
                                                <a href="#" id="view_log" class="mb-3"><i class="fas fa-list"></i>&ensp;View Uploaded Logs</a>
                                            </div>
                                        </div>
                                        <div id="bulk_data">
                                            <input type="file" id="import_file_grade" class="bulk_dropify" styles="height:500px" data-default-file="" name="import_file_grade" data-allowed-file-extensions="csv" accept=".csv" required>
                                        </div>
                                        <div id="bulk_table" style="overflow:auto;"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-rounded btn-label-danger" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-rounded btn-label-custom" id="btn_import_submit">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php
            include_once DOMAIN_PATH . '/global/footer.php'; ## footer
            ?>
        </div>
    </div>
</body>
<?php
include_once DOMAIN_PATH . '/global/include_bottom.php'; ## scripts
?>
<script>
    (function() {
        let beforeunload_event = false;
        let list_academic_year = <?php echo $filter_year; ?>;
        let table_project = "";
        table_project = <?php echo $student_class_list . ";\r\n"; ?>
        let collection_select = {};
        let rating_list = <?php echo output($grade_data_rating); ?>;
        rating_list.push("DRP", "INC", "inc", "drp");
        let rating_text = rating_list.join('|');
        let rating_grade_list = <?php echo output($grade_rating) . ";\r\n"; ?>
        let table_grade_log = null;
        let generate_table = null;

        let total_record = 0;
        let load_all = 0;
        let flag_download = 0;
        let originalData = {};
        let studentList = {};

        let array_key = null;
        let columnDisplayNames = {};
        let send_data = {
            data: [],
            reason: "",
            fy_id: ""
        };

        /*** grade log table modal ***/
        $('#btn_grade_log').on('click', function() {
            $('#table_grade_log').modal('show');
            create_grade_log_table();
        });

        /*** field dropify for IMPORT GRADE ***/
        $('.bulk_dropify').dropify({
            messages: {
                'default': 'Drag and drop your CSV file here.',
                'replace': 'Drag and drop, or click to replace.',
                'remove': 'Remove',
                'error': 'Ooops, something wrong happended.'
            }
        });

        /*** import bulk grade modal ***/
        $('#btn_import_grade').on('click', function() {
            $('#import_bulk_grade').modal('show');
            $('#import_bulk_grade').find('form').trigger('reset');
            $('.dropify-clear').click();
        });

        /*** SUBMIT ***/
        $("#import_grade_form").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>registrar/grade_import_process.php",
                data: new FormData(this),
                dataType: "json",
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    $("#btn_import_submit").attr("disabled", true);
                    $('#btn_import_submit').html(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
                    $(".close_csv_upload").attr("disabled", true);
                },
                complete: function() {
                    $('#btn_import_submit').html('Submit');
                    $('#btn_import_submit').removeAttr('disabled');
                    $(".close_csv_upload").removeAttr('disabled');
                },
                success: function(output) {
                    if (output.success || output.error_id.length > 0) {
                        alert_notif("File Uploaded!");
                        $('#import_bulk_grade').modal('hide');

                        const skipped = output.skipped;
                        const inserted = output.success_insert;
                        const updated = output.success_update;
                        const total_success = (inserted + updated);
                        const total = output.total - (skipped);
                        const notinsert = total - (inserted + updated);
                        const error_list = output.error_id;
                        var error_txt = "<table class=\"table table-bordered\" style=\"max-height:300px; overflow-y: scroll;\">";
                        error_txt += "<tr><th>ID</th><th>Error Message</th></tr>";
                        if (error_list && error_list.length > 0) {
                            error_list.forEach(function(msg) {

                                var text = msg.msg

                                error_txt += "<tr>";
                                error_txt += "<td>" + msg.id + "</td>";
                                error_txt += "<td>" + text.replace(/\^/g, "<br>") + "</td>";
                                error_txt += "</tr>";
                            });
                            error_txt += "</table>";
                        } else {
                            error_txt = "";
                        }


                        var swal_html = '<div class="card-body"><table class="table table-bordered" style="width:100%"><tbody><tr><td>Total not processed:</td><td><b>' + notinsert + '</b></td></tr><tr><td><b>Total Success:</b></td><td><b>' + total_success + '</b></td></tr><tr><td><b>Total Rows:</b></td><td><b>' + total + '</b></td></tr></tbody></table>' + error_txt + '</div>';

                        Swal.fire({
                            title: "Import Status",
                            html: swal_html,
                            allowOutsideClick: false,
                            confirmButtonText: 'Close',
                            width: '800px',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                table_for_project.setData();
                            }
                        })
                        // setTimeout(function() { }, 500);
                    } else {
                        alert_notif(output.error, "error");
                    }
                },
            });

        });

        /*** VIEW UPLOADED LOGS ***/
        addListener(document.getElementById('view_log'), "click", function() {
            var json = <?php echo output($record); ?>;
            var generated_table = "<table class='table table-responsive table-bordered'><tr><th>DATE</th><th>USER</th><th>LOG</th></tr>";
            forEach(json, function(val, i) {
                var base_url = "<?php echo BASE_URL . '/upload/logs/'; ?>";
                var data = val.split("|");
                generated_table += "<tr><td>" + data[0] + "</td><td>" + data[1] + "</td><td><a href='" + base_url + data[2] + ".txt' target='_blank' download> " + data[2] + "</a></td></tr>";
            });
            generated_table += "</table>";

            var swal_html = '<div class="panel text-start"><div class="panel-heading panel-info  btn-info"> <b></b> </div> <div class="panel-body"><div style="overflow-y:auto;height:500px">' + generated_table + '</div></div></div>';
            Swal.fire({
                title: "Uploaded Logs",
                html: swal_html,
                allowOutsideClick: false,
                confirmButtonText: 'Close',
                width: '800px',
            }).then((result) => {
                if (result.isConfirmed) {}
            });

        });

        /** table */
        let table_for_project = new Tabulator("#table_project", {
            ajaxSorting: true,
            ajaxFiltering: true,
            height: "800px",
            tooltips: true,
            printAsHtml: true,
            headerFilterPlaceholder: "",
            layout: "fitDataStretch",
            placeholder: "No Data Found",
            movableColumns: true,
            selectable: false,
            ajaxURL: "<?php echo BASE_URL; ?>registrar/grade_master_table.php",
            ajaxParams: {
                load_all: 0
            },
            virtualDomBuffer: 500, // Increases buffer to prevent misalignment
            ajaxProgressiveLoad: "scroll",
            ajaxProgressiveLoadScrollMargin: 20,
            ajaxLoader: true,
            ajaxLoaderLoading: 'Fetching data from Database..',
            printConfig: {
                columnGroups: false,
                rowGroups: false,
            },
            selectableRollingSelection: false,
            paginationSize: 100,
            columns: [{
                    title: "",
                    width: 15,
                    hozAlign: "center",
                    formatter: function(cell, formatterParams) {
                        let value = cell.getValue();
                        const rowData = cell.getRow().getData();
                        if (value == 0) {
                            return `<i class="fas fa-lock" title="Grade Locked"></i>`;
                        } else if (value == 1) {
                            return `<i class="fas fa-times delete-record" style="font-size:12pt;cursor: pointer; color: #dc3545;" data-id="${rowData.id}" title="Delete Record"></i>`;
                        }
                        return value;
                    },
                    cellClick: function(e, cell) {
                        const rowData = cell.getRow().getData();
                        let rowId = rowData.hash_id;
                        let value = cell.getValue();
                        if (value == 0) {

                        } else if (value == 1) {
                            const recordId = rowData.id;

                            Swal.fire({
                                title: "Are you sure you want to delete the record?",
                                text: "You won't be able to revert this.",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#d33",
                                confirmButtonText: "Yes, delete it!"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        type: "POST",
                                        url: "<?php echo BASE_URL; ?>registrar/grade_master_process.php",
                                        data: {
                                            action: "DELETE_ID",
                                            data: JSON.stringify({
                                                final_id: rowId
                                            })
                                        },
                                        success: function(response) {
                                            if (response.msg == 'SUCCESS') {
                                                Swal.fire("Deleted!", "The record has been deleted.", "success");
                                                cell.getRow().delete();
                                                if (collection_select[rowId] != undefined) {
                                                    delete studentList[rowId];
                                                    delete collection_select[rowId];
                                                    delete originalData[rowId];
                                                }
                                            } else {
                                                alert_notif("Failed to delete the record.", "error");
                                            }
                                        },
                                        error: function() {
                                            alert_notif("Deletion Request Error", "error");
                                        }
                                    });
                                }
                            });
                        }
                    },
                    editable: false,
                    frozen: !isMobile(),
                    field: "status",
                    download: false,
                    sorter: "string",
                    headerFilter: false
                },
                {
                    title: "Final Grade",
                    hozAlign: "center",
                    frozen: !isMobile(),
                    field: "final_grade",
                    width: 100,
                    headerFilter: "input",
                    editable: isEditable,
                    editor: "input",
                    validator: customGradeValidator,
                    headerFilterLiveFilter: false,
                    editorParams: {
                        verticalNavigation: "table",
                    },
                    mutator: mutator_converted,
                    formatter: color_edit
                },
                {
                    title: "Rating",
                    hozAlign: "center",
                    field: "converted_grade",
                    frozen: !isMobile(),
                    mutator: mutator_converted,
                    width: 90,
                    headerFilter: "input",
                    editable: isEditable,
                    editor: "input",
                    validator: customGradeValidator,
                    headerFilterLiveFilter: false,
                    editorParams: {
                        verticalNavigation: "table",
                    },
                    formatter: color_edit,
                    headerFilterLiveFilter: false,
                    download: false
                },
                {
                    title: "Completion",
                    hozAlign: "center",
                    field: "completion",
                    frozen: !isMobile(),
                    width: 100,
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    editable: isEditable,
                    editor: "input",
                    validator: customGradeValidator,
                    headerFilterLiveFilter: false,
                    editorParams: {
                        verticalNavigation: "table",
                    },
                    mutator: mutator_converted,
                    formatter: color_edit
                },
                {
                    title: "Remarks",
                    hozAlign: "center",
                    field: "remarks",
                    frozen: !isMobile(),
                    width: 95,
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    editable: isEditable,
                    editor: "input",
                    validator: customGradeValidator,
                    headerFilterLiveFilter: false,
                    editorParams: {
                        verticalNavigation: "table",
                    },
                    mutator: mutator_converted,
                    formatter: color_edit
                },
                {
                    title: "Credit Code",
                    hozAlign: "center",
                    frozen: !isMobile(),
                    field: "credit_code",
                    width: 100,
                    headerFilter: "input",
                    editable: isEditableCredit,
                    editor: "input",
                    editorParams: {
                        verticalNavigation: "table",
                    },
                    headerFilterLiveFilter: false,
                    formatter: color_edit_credit,
                    cellClick: function(e, cell) {
                        if (cell.getData().school_name == "") {

                        } else {

                        }
                    },
                },
                {
                    title: "Student ID",
                    bottomCalc: record_details,
                    hozAlign: "center",
                    width: 120,
                    field: "student_id_text",
                    sorter: "string",
                    frozen: !isMobile(),
                    headerFilter: "input",
                    headerFilterLiveFilter: false
                },
                {
                    title: "Full Name",
                    hozAlign: "left",
                    width: 180,
                    formatter: "textarea",
                    editor: "input",
                    field: "student_name",
                    sorter: "string",
                    frozen: !isMobile(),
                    headerFilter: "input",
                    print: true,
                    visible: true,
                    download: false,
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade
                },
                {
                    title: "Last Name",
                    hozAlign: "left",
                    field: "lastname",
                    print: false,
                    visible: false,
                    download: true
                },
                {
                    title: "First Name",
                    hozAlign: "left",
                    field: "firstname",
                    print: false,
                    visible: false,
                    download: true
                },
                {
                    title: "Middle Name",
                    hozAlign: "left",
                    field: "middle_name",
                    print: false,
                    visible: false,
                    download: true
                },
                {
                    title: "Suffix Name",
                    hozAlign: "left",
                    field: "suffix_name",
                    print: false,
                    visible: false,
                    download: true
                },
                {
                    title: "Middle Initial",
                    hozAlign: "left",
                    width: 100,
                    field: "middle_initial",
                    print: false,
                    visible: false,
                    download: true
                },
                {
                    title: "Sex",
                    hozAlign: "center",
                    width: 100,
                    field: "gender",
                    download: true,
                    visible: false
                },
                {
                    title: "Program",
                    hozAlign: "center",
                    width: 90,
                    field: "program_code",
                    editor: "input",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade
                },
                {
                    title: "Major",
                    hozAlign: "center",
                    width: 90,
                    field: "major",
                    editor: "input",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade
                },
                {
                    title: "Year Level",
                    hozAlign: "center",
                    width: 90,
                    field: "yr_level",
                    editor: "input",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade
                },
                {
                    title: "Section",
                    hozAlign: "center",
                    width: 90,
                    field: "section_name",
                    editor: "input",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade,
                    download: false
                },
                {
                    title: "Course Code",
                    hozAlign: "left",
                    width: 120,
                    field: "subject_code",
                    editor: "input",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade
                },
                {
                    title: "Course Description ",
                    width: 250,
                    formatter: "textarea",
                    hozAlign: "left",
                    editor: "input",
                    field: "course_desc",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade
                },
                {
                    title: "Units",
                    hozAlign: "center",
                    width: 90,
                    field: "units",
                    editor: "input",
                    headerFilterFunc: "<=",
                    sorter: "number",
                    sorterParams: {
                        decimalSeperator: ".",
                    },
                    headerFilter: "input",
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade
                },
                {
                    title: "Fiscal Year",
                    hozAlign: "center",
                    width: 150,
                    field: "school_year",
                    editor: "input",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade,
                    download: false
                },
                {
                    title: "Sem",
                    hozAlign: "center",
                    width: 150,
                    field: "sem",
                    sorter: "string",
                    editor: "input",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    editable: isEditableGrade
                },
                {
                    title: "School Name",
                    minWidth: 200,
                    formatter: "textarea",
                    editor: "input",
                    editable: isEditableGrade,
                    field: "school_name",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: color_edit_grade,
                    validator: customValidator,
                    download: false
                },
                {
                    title: "Date",
                    field: "date_added",
                    width: 90,
                    formatter: "datetime",
                    formatterParams: {
                        inputFormat: "YYYY-MM-DD HH:ii",
                        outputFormat: "MM/DD/YYYY",
                        invalidPlaceholder: "(invalid date)",
                    },
                    sorter: "datetime",
                    download: false,
                    headerFilterLiveFilter: false,
                },

            ],
            cellEdited: function(cell) {
                var rowData = cell.getData();
                var rowId = rowData.hash_id;
                var field = cell.getField();
                var newValue = cell.getValue();
                var oldValue = cell.getOldValue();

                // Store the original value once per field
                if (!originalData[rowId]) {
                    originalData[rowId] = {};
                }
                if (!(field in originalData[rowId])) {
                    originalData[rowId][field] = oldValue;
                }

                // Track full student data for display/reference
                if (!studentList[rowId]) {
                    studentList[rowId] = {
                        final_id: rowData.final_id,
                        student_name: rowData.student_name,
                        student_id_text: rowData.student_id_text,
                        school_year: rowData.school_year,
                        subject_code: rowData.subject_code,
                        sem: rowData.sem,
                        course_desc: rowData.course_desc,
                    };
                }


                // If the value returns to the original, remove it from collection_select
                if (originalData[rowId][field] === newValue) {
                    if (collection_select[rowId]) {
                        delete collection_select[rowId][field];
                        if (Object.keys(collection_select[rowId]).length === 0) {
                            delete collection_select[rowId];
                        }
                    }
                } else {
                    if (!collection_select[rowId]) {
                        collection_select[rowId] = {};
                    }
                    collection_select[rowId][field] = newValue;
                }

                if (collection_select[rowId] === undefined) {
                    delete studentList[rowId];
                    delete originalData[rowId];
                }

                if (!beforeunload_event && Object.keys(collection_select).length > 0) {
                    window.addEventListener("beforeunload", beforeunload);
                    beforeunload_event = true;
                }
            },
            downloadComplete: function() {
                removeClass(document.querySelector('body'), "loading");
            },
            ajaxResponse: function(url, params, response) {
                if (response.total_record) {
                    total_record = response.total_record;
                }
                setTimeout(function() {
                    table_for_project.getRows().forEach(row => {
                        var rowData = row.getData();
                        var rowId = rowData.hash_id;
                        if (collection_select[rowId]) {
                            Object.keys(collection_select[rowId]).forEach(field => {
                                row.update({
                                    [field]: collection_select[rowId][field]
                                }); // Restore changes
                            });
                        }
                    });

                    flag_download = 1;
                }, 1000);

                return response;
            },
            ajaxRequesting: function(url, params) {
                if (typeof this.modules.ajax.showLoader() != "undefined") {
                    this.modules.ajax.showLoader();
                }

                params['load_all'] = load_all;

            },
        });

        addListener(document.getElementById('download-csv-proj'), "click", function() {
            addClass(document.querySelector('body'), 'loading');
            table_for_project.download("csv", 'report_' + getFormattedTime() + '.csv', {
                bom: true
            });
        });

        addListener(document.getElementById('download-json-proj'), "click", function() {
            addClass(document.querySelector('body'), 'loading');
            table_for_project.download("json", 'report_' + getFormattedTime() + '.json');
        });

        addListener(document.getElementById('download-xlsx-proj'), "click", function() {
            addClass(document.querySelector('body'), 'loading');
            table_for_project.download("xlsx", 'report_' + getFormattedTime() + '.xlsx');
        });

        addListener(document.getElementById('print-proj'), "click", function() {
            table_for_project.print(false, true);
        });

        addListener(document.getElementById('btn_select_year'), "click", function() {
            open_year();
        });

        addListener(document.getElementById('btn_edit_grades'), "click", function() {
            array_key = null;
            send_data = {
                data: [],
                reason: "",
                fy_id: ""
            };

            var temp_fy = '';
            columnDisplayNames = {};

            table_for_project.getColumns().forEach(function(col) {
                var field = col.getField();
                var title = col.getDefinition().title;
                if (field) {
                    columnDisplayNames[field] = title;
                }
            });

            array_key = Object.keys(collection_select);
            if (array_key.length == 0) {
                alert_notif('No data changed', "warning");
                return;
            } else {
                generate_table = null;
                $('#save_generate_table').html("");

                // Start the new table
                generate_table = `<div class="col-12"><div class="table-responsive">
										<table class="w-100 table table-bordered" style="font-size:10pt;">
										<thead>
											<tr>
												<th>Grade ID</th>
												<th>Student Details</th>
												<th>Additional Info</th>
												<th>Old Data</th>
												<th>New Data</th>
											</tr>
										</thead>
										<tbody>
									`;

                let changes = [];

                array_key.forEach(function(rowId) {
                    console.log(studentList[rowId]);

                    var newData = collection_select[rowId];
                    const oldData = originalData[rowId];
                    const studentData = studentList[rowId];
                    temp_fy = newData.schoolyear_id;

                    let oldFields = "";
                    let newFields = "";
                    let hasChange = false;

                    Object.keys(newData).forEach(function(field) {
                        if (oldData && field in oldData && oldData[field] !== newData[field]) {
                            const displayField = columnDisplayNames[field] || field;
                            oldFields += `<b>${displayField}:</b> ${oldData[field]}<br>`;
                            newFields += `<b>${displayField}:</b> ${newData[field]}<br>`;
                            hasChange = true;
                        }
                    });

                    if (hasChange) {
                        changes.push(true);
                        generate_table += `<tr>
												<td> ${studentData.final_id || 'Unknown'}</td>
												<td class="text-start">
													${studentData.student_id_text || 'Unknown'}<br>
													${studentData.student_name  || 'Unknown'}<br>
												</td>
												<td class="text-start info-cell">
                                                    <div><span class="label">Course Code:</span> <span class="value">${studentData.subject_code || 'Unknown'}</span></div>
                                                    <div><span class="label">Course Description:</span> <span class="value">${studentData.course_desc || 'Unknown'}</span></div>
                                                    <div><span class="label">School Year:</span> <span class="value">${studentData.school_year || 'Unknown'}</span></div>
                                                    <div><span class="label">Semester:</span> <span class="value">${studentData.sem || 'Unknown'}</span></div>
												</td>
												<td class="text-start">${oldFields}</td>
												<td class="text-start">${newFields}</td>
											</tr>`;
                        newData['final_grade_id'] = rowId;
                        send_data.data.push(newData);
                    } else {
                        changes.push(false);
                    }
                });

                if (changes.every(val => val === false)) {
                    alert_notif('No data changed', "warning");
                    return;
                }

                send_data.fy_id = temp_fy;

                generate_table += `</tbody></table>
                                    </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold" for="reason_area">General Reason 
                                                <small class='text-muted'></small>
                                            </label>
                                            <textarea class="form-control" name="reason_area" id="reason_area" rows="3" required></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold" for="file_upload">Attached Image File</label>
                                            <input type="file" name="file_upload" accept=".jpeg,.png,.jpg,.gif" class="form-control" id="file_upload" required>
                                        </div>
									</div>`;

                $('#save_generate_table').html(generate_table);
                $('#save_grades').modal('show');
                $('#save_grades').find('form').trigger('reset');
                return;
            }
        });


        $("#save_grade_import").on("submit", function(e) {
            e.preventDefault();

            const reason = document.querySelector('#reason_area').value;
            const file_upload = document.querySelector('#file_upload');

            const formData = new FormData();
            formData.append('reason', reason);

            if (file_upload.files.length > 0) {
                formData.append('file_upload', file_upload.files[0]);
            }
            formData.append('data', JSON.stringify(send_data));
            formData.append('action', 'SAVE_GRADE');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>registrar/grade_master_process.php",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    var obj = response;
                    if (obj.result == "success") {
                        if (obj.msg != "") {

                            $('#save_grades').modal('hide');

                            let generate_table = "<div class=\"col-12 table-responsive\"><table class=\"table table-bordered w-100\">";
                            generate_table += "<thead><tr><th>STUDENT ID</th><th>STUDENT NAME</th><th>CHANGES</th><th>STATUS</th></tr></thead><tbody>";

                            // Loop over each student in array_key
                            array_key.forEach(function(rowId) {
                                const newData = collection_select[rowId];
                                const oldData = originalData[rowId];
                                const studentData = studentList[rowId];
                                let temp_fy = newData.schoolyear_id;

                                let oldFields = "";
                                let newFields = "";
                                let hasChange = false;

                                // Compare old data with new data for each field
                                Object.keys(newData).forEach(function(field) {
                                    if (oldData && field in oldData && oldData[field] !== newData[field]) {
                                        const displayField = columnDisplayNames[field] || field;
                                        oldFields += `<b>${displayField}:</b> ${oldData[field]}<br>`;
                                        newFields += `<b>${displayField}:</b> ${newData[field]}<br>`;
                                        hasChange = true;
                                    }
                                });

                                // If changes are detected, add them to the table
                                if (hasChange) {
                                    const status = obj.msg && obj.msg[rowId] && obj.msg[rowId]['result'] ? obj.msg[rowId]['result'] : 'ERROR';

                                    generate_table += `
											<tr>
												<td>${studentData.student_id_text || 'Unknown'}</td>
												<td>${studentData.student_name || 'Unknown'}</td>
												<td><strong>Old:</strong><br>${oldFields}<br><strong>New:</strong><br>${newFields}</td>
												<td>${status}</td>
											</tr>
										`;
                                }
                            });

                            generate_table += "</tbody></table></div>";

                            Swal.fire({
                                customClass: 'swal-wide',
                                title: 'Student Grades Updated',
                                icon: 'info',
                                html: generate_table,
                                showCancelButton: false,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Close',
                                width: "1000px"
                            });

                            table_for_project.setData();
                            collection_select = [];
                            beforeunload_event = false;
                            window.removeEventListener('beforeunload', beforeunload);
                        }

                    } else {
                        alert_notif(obj.errors, "error");
                    }
                },
                failure: function(response) {
                    console.log(response);
                    return;
                    alert_notif("Saving Request Error", "error");
                }
            });
        });

        function color_edit(cell, formatterParams, onRendered) {
            if (cell.getData().final_grade_id != "") {
                cell.getElement().style.backgroundColor = "#f3f791";
            }
            return cell.getValue();
        }

        function color_edit_credit(cell, formatterParams, onRendered) {
            if (cell.getData().school_name != "") {
                cell.getElement().style.backgroundColor = "#f3f791";
            }
            return cell.getValue();
        }

        function color_edit_grade(cell, formatterParams, onRendered) {
            cell.getElement().style.whiteSpace = "pre-wrap";
            if (cell.getData().status == '1') {
                cell.getElement().style.backgroundColor = "#f3f791";
            }
            return cell.getValue();
        }

        function isEditable(cell) {
            if (cell.getData().final_grade_id == "") {
                return false;
            } else {
                return true;
            }
        }

        function isEditableGrade(cell) {
            if (cell.getData().status == '1') {
                return true;
            } else {
                return false;
            }
        }

        function isEditableCredit(cell) {
            if (cell.getData().school_name == "") {
                return false;
            } else {
                return true;
            }
        }

        function mutator_converted(value, data, type, params, component) {
            var grade = typeof value === 'string' ? value.toUpperCase() : value;
            return grade;
        }

        function isMobile() {
            return window.innerWidth <= 920;
        }

        function customValidator(cell, value) {
            if (typeof value === "string") {
                value = value.trim();
            }

            if (cell.getField() == 'school_name') return true;

            if (value === "") {
                return false;
            }

            if (!isNaN(value)) {
                let num = Number(value);
                return Number.isInteger(num) && num >= 0 && num <= 100;
            }

            return true;
        }

        function customGradeValidator(cell, value) {
            if (cell.getField() == 'completion' && value == '') {
                return true;
            }
            if (value === "PASSED") return true;
            if (value === "FAILED") return true;
            if (value === "INC") return true;
            if (value === "LOA") return true;
            if (value === "DRP") return true;
            let num = parseFloat(value);
            return !isNaN(num) && num >= 0 && num <= 100;
        };

        function record_details(values, data, calcParams) {
            if (values && values.length) {
                // document.getElementById("pager").textContent = values.length + ' of ' + total_record;
                return values.length + ' of ' + total_record;
            }
        }

        function create_grade_log_table() {
            //destroy table if already created
            if (table_grade_log) table_grade_log.destroy();
            let total_record_log = 0;

            table_grade_log = new Tabulator("#grade_log_table", {
                ajaxSorting: true,
                ajaxFiltering: true,
                height: "600px",
                tooltips: true,
                printAsHtml: true,
                headerFilterPlaceholder: "",
                layout: "fitColumns",
                placeholder: "No Data Found",
                movableColumns: true,
                selectable: false,
                ajaxURL: "<?php echo BASE_URL; ?>registrar/grade_log_table.php",
                ajaxProgressiveLoad: "scroll",
                ajaxProgressiveLoadScrollMargin: 1,
                ajaxLoaderLoading: 'Fetching data from Database..',
                printConfig: {
                    columnGroups: false,
                    rowGroups: false,
                    formatter: false,
                },
                paginationSize: <?php echo QUERY_LIMIT; ?>,
                columns: [{
                        title: "Date",
                        bottomCalc: function(values, data, calcParams) {
                            if (values && values.length) {
                                return values.length + ' of ' + total_record_log;
                            }
                            return '0 of ' + total_record_log;
                        },
                        field: "date_added",
                        width: 175,
                        titlePrint: "Date & Time",
                        sorter: "datetime",
                        // formatter: "datetime",
                        // formatterParams: {
                        //     inputFormat: "YYYY-MM-DD HH:mm:ss",
                        //     outputFormat: "MMM DD,YYYY HH:mm:ss",
                        //     invalidPlaceholder: "(invalid date)",
                        // },
                        headerFilter: "input",
                        headerFilterLiveFilter: false
                    },
                    {
                        title: "User",
                        field: "full_name",
                        titlePrint: "User",
                        sorter: "string",
                        headerFilter: "input",
                        headerFilterLiveFilter: false
                    },
                    {
                        title: "Action",
                        field: "data_changed",
                        titlePrint: "Action",
                        sorter: "string",
                        headerFilter: "input",
                        headerFilterLiveFilter: false,
                        formatter: function(cell, formatterParams, onRendered) {
                            var string = cell.getValue();
                            var result = cell.getValue();
                            var upload_file = cell.getData().upload_file;
                            if (upload_file) {
                                result = string + " \r\n " + '<a href="<?php echo BASE_URL; ?>' + upload_file + '" target="_blank" download="" title="DOWNLOAD"><i class="fas fa-download"></i> PROOF </a>';

                            }
                            cell.getElement().style.whiteSpace = "pre-wrap";
                            return result; //return the contents of the cell;
                        }
                    },

                ],
                ajaxResponse: function(url, params, response) {
                    if (response.total_record) {
                        total_record_log = response.total_record;
                    }
                    return response;
                },
                ajaxRequesting: function(url, params) {
                    if (typeof this.modules.ajax.showLoader() != "undefined") {
                        this.modules.ajax.showLoader();
                    }
                },
            });
        }

        function open_year() {
            Swal.fire({
                title: "Fiscal Year",
                html: `<div class="swal-div"><label for="" class="swal2-input-label"><\/label><select  id="school_year_id" class="swal-select"  placeholder="" required></div>`,
                showCancelButton: true,
                confirmButtonText: "Generate",
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                showLoaderOnConfirm: true,
                showCloseButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                focusConfirm: false,
                // customClass: {
                //     content: 'text-start pt-4 ',
                //     popup: 'splash_pop p-0 col-xs-12 col-sm-8 col-lg-6',
                //     header: 'splash_swal_header ',
                //     title: 'splash_title',
                //     footer: 'pb-1 pl-1 pr-1',
                // },
                // footer: `    `,
                backdrop: 'rgba(19, 14, 14, 0.3)',
                preConfirm: async () => {
                    let error_log = 'Please enter or select a Fiscal Year!';
                    const school_year_id = $('#school_year_id').val().trim(); // Get select

                    if (school_year_id === "" || school_year_id === undefined) {
                        Swal.showValidationMessage(error_log);
                        return false;
                    }

                    return {
                        school_year_id: school_year_id
                    }
                },
                onBeforeOpen: () => {
                    // Initialize Selectize after modal is open
                    var select_academic_year = $('#school_year_id').selectize({
                        valueField: 'id',
                        labelField: ['title'],
                        searchField: ['title'],
                        options: list_academic_year,
                        items: ["all"],
                        persist: false,
                        maxItems: 1,
                        dropdownParent: "body",
                        render: {
                            option: function(item, escape) {
                                return '<div style="text-align:center;"><h5 class="title">F.Y. : ' + escape(item.title) + '</h5></div>';
                            },
                            item: function(item, escape) {
                                return '<div style="text-align:center;">F.Y. <span class="title">' + escape(item.title) + '</span></div>';
                            }
                        },
                        onChange: function(value) {
                            //insertParam("academic_year",value);
                        }

                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.school_year_id === "all") {
                        load_all = 0;
                        table_for_project.clearFilter();
                        // table_for_project.setData();
                    } else {
                        load_all = 1;

                        var match = result.value.school_year_id.match(/^(\d{4}-\d{4}),\s*(.*)$/);
                        if (match) {
                            var academicYear = match[1];
                            var semester = match[2];

                            table_for_project.setFilter([{
                                    field: "school_year",
                                    type: "=",
                                    value: academicYear
                                },
                                {
                                    field: "sem",
                                    type: "=",
                                    value: semester
                                }
                            ]);
                        }
                    }
                }
            });
        }

        add_overlay();

        function add_overlay() {
            var body = document.querySelector('body');
            var overlay = document.querySelector('.overlay');
            if (overlay) {} else {
                var div = document.createElement('div');
                div.className = "overlay";
                body.appendChild(div);
            }
        }

        function beforeunload(e) {
            var confirmationMessage = 'It looks like you have been editing something. ' +
                'If you leave before saving, your changes will be lost.';

            (e || window.event).returnValue = confirmationMessage; //Gecko + IE
            return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
        }
    })();
</script>

</html>