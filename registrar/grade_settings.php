<?php
set_time_limit(0);
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

## page header title
$general_page_title = "Grade Settings";
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

$default_get_id = get_school_year();
$select_academic_year_id = isset($_GET['academic_year']) ? trim($_GET['academic_year']) : 0;

if (!is_digit($select_academic_year_id)) {
    include HTTP_404;
    exit();
}

$select_academic_year = json_encode(array($select_academic_year_id));
$data_array = array();
$error = false;
$filter_year = array(["school_year_id" => 0, "school_year" => "Default", "sem" => "Settings"]);
if ($query = call_mysql_query("SELECT school_year_id, school_year, sem FROM school_year ORDER BY school_year_id DESC")) {
    while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
        array_push($filter_year, $data);
    }
}

$subject_hash = [];
$subject_list = [];
if ($query = call_mysql_query("SELECT subject_id,CONCAT(subject_code,' - ',subject_title) as subject_mixed,unit,status FROM subject WHERE status = '0'")) {
    while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
        $id = $data['subject_id'];
        $subject_list[] = $data;
        $subject_hash[$id] = $data;
    }
}

$subject_list = json_encode($subject_list, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
//start get subject table
$collect_db = [];
$collect_db['subject_exceptions'] = [];
$collect_db['grade_system'] = [];
$collect_db['additional'] = [];
$flag_set = [0, 0, 0];

$module = "'subject_exceptions','grade_system','additional'";
if ($query = call_mysql_query("SELECT * FROM settings WHERE school_year_id ='" . $select_academic_year_id . "' AND module IN (" . $module . ") ")) {
    while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
        $temp = [];

        if ($data['module'] == 'subject_exceptions') {
            $temp = json_decode($data['settings']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $temp = [];
            }

            $db_temp = [];
            foreach ($temp as $subj_id) {
                $id =  isset($subject_hash[$subj_id]) ? $subject_hash[$subj_id]['subject_id'] : '';
                $tmp = [];
                $tmp['subject_mixed'] = $id;
                array_push($db_temp, $tmp);
            }
            $collect_db['subject_exceptions'] =  $db_temp;
            // $collect_db['subject_exceptions'] = $temp;
            unset($temp);
            $flag_set[0] = 1;
        } else if ($data['module'] == 'grade_system') {
            $temp = json_decode($data['settings'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $temp = [];
            }
            $collect_db['grade_system'] = $temp;
            $flag_set[1] = 1;
        } else if ($data['module'] == 'additional') {
            $temp = json_decode($data['settings'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $temp = [];
            }
            $collect_db['additional'] = $temp;
            $flag_set[2] = 1;
        }
    }
}


## default
if ($query = call_mysql_query("SELECT * FROM settings WHERE school_year_id =0 ")) {
    while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
        $temp = [];
        if ($data['module'] == 'subject_exceptions') {
            $temp = json_decode($data['settings']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $temp = [];
            }
            $db_temp = [];
            foreach ($temp as $subj_id) {
                $id =  isset($subject_hash[$subj_id]) ? $subject_hash[$subj_id]['subject_id'] : '';
                $tmp = [];
                $tmp['subject_mixed'] = $id;
                array_push($db_temp, $tmp);
            }
            if (empty($collect_db['subject_exceptions'])) {
                $collect_db['subject_exceptions'] =  $db_temp;
            }
        } else if ($data['module'] == 'grade_system') {
            $temp = json_decode($data['settings'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $temp = [];
            }
            if (empty($collect_db['grade_system'])) {
                $collect_db['grade_system'] = $temp;
            }
        } else if ($data['module'] == 'additional') {
            $temp = json_decode($data['settings'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $temp = [];
            }
            if (empty($collect_db['additional'])) {
                $collect_db['additional'] = $temp;
            }
        }
        unset($temp);
    }
}

$filter_year = json_encode($filter_year);
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
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label>Fiscal Year</label>

                                            <div class="input-group mb-3">
                                                <select class="form-control" name="school_year_id" id="school_year_id" placeholder="Fiscal Year & Semester" required></select>
                                                <div class="input-group-append d-block">
                                                    <button id="btn_gen_year" type="button" class="btn btn-outline-dark btn-rounded btn-sm ml-1 " style="padding: 6px 12px !important; ">Generate</button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        echo ($select_academic_year_id == 0) ? '
                                            <div class="col-12">
                                                <div class="alert alert-warning mt-3" role="alert">
                                                    <p class="m-0"><b>Warning!</b></p>
                                                    <li class="m-0">The default settings should not be changed unless absolutely necessary.</li>
                                                    <li class="m-0">The default settings will be used if the semester doesn\'t set any configurations.</li>
                                                    <li class="m-0">The semester that depends on the default setting will also be affected if the default setting has been modified.</li>
                                                </div>
                                            </div>' : ''; ?>
                                    </div>
                                    <nav>
                                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                            <a class="nav-item nav-link active" id="nav-exception-tab" data-toggle="tab" href="#nav-exception" role="tab" aria-controls="nav-exception" aria-selected="true"> Subject Exception</a>
                                            <a class="nav-item nav-link" id="nav-grade_table-tab" data-toggle="tab" href="#nav-grade_table" role="tab" aria-controls="nav-grade_table" aria-selected="false"> Grade System Table</a>
                                            <a class="nav-item nav-link" id="nav-additional-tab" data-toggle="tab" href="#nav-additional" role="tab" aria-controls="nav-additional" aria-selected="false"> Additional</a>
                                        </div>
                                    </nav>
                                    <div class="tab-content" id="nav-myTabContent">
                                        <div class="tab-pane fade show active" id="nav-exception" role="tabpanel" aria-labelledby="nav-exception-tab">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <button id="btn_save_subject" type="button" class="btn btn-outline-danger btn-sm ml-1 mb-2 " style="padding: 6px 12px !important; "><i class="fas fa-save"></i> Save Changes</button>
                                                            <button id="btn_add_subject" type="button" class="btn btn-outline-primary btn-sm ml-1 mb-2 " style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>

                                                            <?php echo ($flag_set[0] == 0 and $select_academic_year_id != 0) ? '<div class="row"><div class="col-12"><div class="alert alert-warning mt-3" role="alert"><p class="m-0"><b>Warning!</b></p><li class="m-0">Dependent on default settings</li></div></div></div>' : ''; ?>

                                                            <div class="table-bordered " id="table_project">
                                                                <div class="table-bordered" id="subject_table"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="nav-grade_table" role="tabpanel" aria-labelledby="nav-grade_table-tab">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <button id="btn_save_grade" type="button" class="btn btn-outline-danger btn-sm ml-1 mb-2 " style="padding: 6px 12px !important; "><i class="fas fa-save"></i> Save Changes</button>
                                                            <button id="btn_add_grade" type="button" class="btn btn-outline-primary btn-sm ml-1 mb-2 " style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Row</button>
                                                            <?php echo ($flag_set[1] == 0 and $select_academic_year_id != 0) ? '<div class="row"><div class="col-12"><div class="alert alert-warning mt-3" role="alert"><p class="m-0"><b>Warning!</b></p><li class="m-0">Dependent on default settings</li></div></div></div>' : ''; ?>
                                                            <div class="table-bordered " id="div_grade_system">
                                                                <div class="table-bordered" id="tb_grade_system"></div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="nav-additional" role="tabpanel" aria-labelledby="nav-additional-tab">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <button id="btn_save_additional" type="button" class="btn btn-outline-danger btn-sm ml-1 mb-2 " style="padding: 6px 12px !important; "><i class="fas fa-save"></i> Save Changes</button>
                                                            <?php echo ($flag_set[2] == 0 and $select_academic_year_id != 0) ? '<div class="row"><div class="col-12"><div class="alert alert-warning mt-3" role="alert"><p class="m-0"><b>Warning!</b></p><li class="m-0">Dependent on default settings</li></div></div></div>' : ''; ?>
                                                            <div class="table-bordered " id="div_additional">
                                                                <div class="table-bordered" id="tb_additional"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>


                        </div> <!-- end col-->
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
        var collection_select = [];
        var list_academic_year = <?php echo $filter_year; ?>;
        var table_project = "";
        var school_year_id = <?php echo $select_academic_year_id; ?>;
        var total_record = 0;
        var check_box_all = false;
        var selected_ck = false;
        var subject_list = <?php echo $subject_list . ";\r\n"; ?>
        var table_array = <?php echo json_encode($collect_db['subject_exceptions'], JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE) . ";\r\n"; ?>
        var table_gs = <?php echo json_encode($collect_db['grade_system'], JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE) . ";\r\n"; ?>
        var table_additional = <?php echo json_encode($collect_db['additional'], JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE) . ";\r\n"; ?>
        var subject_code = [];
        var subject_array = {};
        var len = subject_list.length;
        var global_type = "subject_exceptions";
        var tb_grade_system = "";

        for (var x = 0; x < len; x++) {
            var id = subject_list[x]['subject_id'];
            var temp = {};
            temp['label'] = subject_list[x]['subject_mixed'];
            temp['value'] = subject_list[x]['subject_id'];


            subject_code.push(temp);
            subject_array[id] = subject_list[x]['subject_mixed'];
        }


        var table_for_project = new Tabulator("#subject_table", {
            printAsHtml: true,
            data: table_array,
            height: "700px",
            headerFilterPlaceholder: "",
            layout: "fitColumns",
            placeholder: "No Data Found",
            movableColumns: true,
            printConfig: {
                columnGroups: true,
                rowGroups: true,
                formatCells: false,
            },
            columnHeaderVertAlign: "bottom",
            selectableRollingSelection: false,
            columns: [{
                    title: "Subject Code & Title",
                    hozAlign: "left",
                    editor: "autocomplete",
                    editorParams: {
                        sortValuesList: "asc",
                        allowEmpty: false,
                        searchingPlaceholder: 'Type Subject',
                        showListOnEmpty: true,
                        emptyPlaceholder: "No matching results found",
                        values: subject_code,
                    },
                    field: "subject_mixed",
                    minWidth: 230,
                    headerFilter: "input",
                    formatter: "lookup",
                    formatterParams: subject_array
                },
                {
                    title: "Action",
                    hozAlign: "center",
                    minWidth: 200,
                    width: 200,
                    sorter: false,
                    formatter: "buttonCross",
                    width: 40,
                    cellClick: function(e, cell) {
                        cell.getRow().delete();
                    }
                },
                //column definition in the columns array
            ],
            rowFormatter: function(row) {
                var data = row.getData();
                row.getElement().style.backgroundColor = "#f3f791";
            },
        });

        addListener(btn_add_subject, "click", function() {
            table_for_project.addRow({}, true);
        });
        //save subject exceptions
        addListener(btn_save_subject, "click", function() {
            //table_for_project.addRow({}, true);
            global_type = "subject_exceptions";
            var data_table = table_for_project.getData();
            var send_data = [];
            var len = data_table.length || 0;
            var found_error = false;

            for (var i = 0; i < len; i++) {
                if (data_table[i]['subject_mixed'] == undefined || data_table[i]['subject_mixed'] == '') {
                    found_error = true;
                    continue;
                }

                send_data.push(data_table[i]['subject_mixed']);
            }

            if (found_error) {
                alert_notif('Please check table for empty row!', "error");
                return;
            }

            Swal.fire({
                title: 'Are you sure to save this?',
                text: '<?php echo ($select_academic_year_id == 0) ? "This is default settings that may affect other semester! \\r\\n You won\'t be able to revert this!" : "You won\'t be able to revert this!"; ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("<?php echo BASE_URL; ?>registrar/grade_settings_ajax.php", {
                            action: 'SETTING',
                            type: global_type,
                            data: JSON.stringify(send_data),
                            school_year: school_year_id
                        },
                        function(returnedData) {
                            var obj = returnedData; // JSON.parse(returnedData);
                            if (obj.result == "success") {
                                Swal.fire(
                                    'Saved!',
                                    'Data has been saved.',
                                    'success'
                                );
                            } else {
                                alert_notif("Error: " + obj.errors, "error");
                            }
                        }).fail(function() {
                        alert_notif("Request Error.", "error");
                    });
                }
            });
        });

        function mutatorSpace(value, data, type, params, component) {
            var result = "";
            if (value == undefined || value == null) {
                value = "";
            }
            value = value.toString();
            result = value.trim();
            return result;
        }

        var tb_grade_system = new Tabulator("#tb_grade_system", {
            printAsHtml: true,
            data: table_gs,
            height: "700px",
            headerFilterPlaceholder: "",
            layout: "fitColumns",
            placeholder: "No Data Found",
            movableColumns: true,
            printConfig: {
                columnGroups: true,
                rowGroups: true,
                formatCells: false,
            },
            columnHeaderVertAlign: "bottom",
            columns: [{
                    title: "Range From",
                    hozAlign: "center",
                    field: 'range_from',
                    editor: "input",
                    minWidth: 100,
                    sorter: true,
                    mutator: mutatorSpace,
                    formatter: color_edit
                },
                {
                    title: "Range To",
                    hozAlign: "center",
                    field: 'range_to',
                    editor: "input",
                    minWidth: 100,
                    sorter: true,
                    mutator: mutatorSpace,
                    formatter: color_edit
                },
                {
                    title: "Grade",
                    hozAlign: "center",
                    field: 'grade',
                    editor: "input",
                    minWidth: 100,
                    sorter: true,
                    mutator: mutatorSpace,
                    formatter: color_edit
                },
                {
                    title: "Remarks",
                    hozAlign: "center",
                    field: 'text',
                    editor: "input",
                    minWidth: 100,
                    sorter: true,
                    mutator: mutatorSpace,
                    formatter: color_edit
                },
                {
                    title: "Action",
                    hozAlign: "center",
                    minWidth: 200,
                    width: 200,
                    sorter: false,
                    formatter: "buttonCross",
                    width: 40,
                    cellClick: function(e, cell) {
                        cell.getRow().delete();
                    }
                }
            ]
        });



        addListener(btn_add_grade, "click", function() {
            tb_grade_system.addRow({}, true);
        });
        //save subject exceptions
        addListener(btn_save_grade, "click", function() {
            global_type = "grade_system";
            var data_table = tb_grade_system.getData();
            var send_data = [];
            var len = data_table.length || 0;
            var found_error = false;

            for (var i = 0; i < len; i++) {
                if (data_table[i]['range_from'] == undefined || data_table[i]['range_from'] == '') {
                    found_error = true;
                    continue;
                }

                if (data_table[i]['range_to'] == undefined || data_table[i]['range_to'] == '') {
                    found_error = true;
                    continue;
                }

                if (data_table[i]['grade'] == undefined || data_table[i]['grade'] == '') {
                    found_error = true;
                    continue;
                }

                if (data_table[i]['text'] == undefined || data_table[i]['text'] == '') {
                    found_error = true;
                    continue;
                }
            }

            if (found_error) {
                alert_notif('Please check table for empty row!', "error");
                return;
            }

            Swal.fire({
                title: 'Are you sure to save this?',
                text: '<?php echo ($select_academic_year_id == 0) ? "This is default settings that may affect other semester! \\r\\n You won\'t be able to revert this!" : "You won\'t be able to revert this!"; ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("<?php echo BASE_URL; ?>registrar/grade_settings_ajax.php", {
                            action: 'SETTING',
                            type: global_type,
                            data: JSON.stringify(data_table),
                            school_year: school_year_id
                        },
                        function(returnedData) {
                            var obj = returnedData; // JSON.parse(returnedData);
                            if (obj.result == "success") {
                                Swal.fire(
                                    'Saved!',
                                    'Data has been saved.',
                                    'success'
                                );
                            } else {
                                alert_notif("Error: " + obj.errors, "error");
                            }
                        }).fail(function() {
                        alert_notif("Request Error", "error");
                    });
                }
            });



        });

        function color_edit(cell, formatterParams, onRendered) {
            cell.getElement().style.backgroundColor = "#f3f791"; // for checking
            return cell.getValue(); //return the contents of the cell;
        }

        var tb_additional = new Tabulator("#tb_additional", {
            printAsHtml: true,
            data: table_additional,
            height: "700px",
            headerFilterPlaceholder: "",
            layout: "fitColumns",
            placeholder: "No Data Found",
            movableColumns: true,
            printConfig: {
                columnGroups: true,
                rowGroups: true,
                formatCells: false,
            },
            columnHeaderVertAlign: "bottom",
            columns: [{
                    title: "FIELD",
                    hozAlign: "center",
                    field: 'field',
                    minWidth: 150,
                    sorter: true,
                    vertAlign: "middle"
                },
                {
                    title: "VALUE",
                    hozAlign: "center",
                    field: 'value',
                    editor: "input",
                    minWidth: 150,
                    vertAlign: "middle",
                    formatter: color_edit
                },
                {
                    title: "REMARKS",
                    hozAlign: "center",
                    field: 'remarks',
                    minWidth: 100,
                    formatter: 'textarea',
                    vertAlign: "middle"
                },
            ]
        });

        addListener(btn_save_additional, "click", function() {
            global_type = "additional";
            var data_table = tb_additional.getData();
            var send_data = [];
            var len = data_table.length || 0;
            var found_error = false;
            var val = 0;

            for (var i = 0; i < len; i++) {
                if (data_table[i]['value'] == undefined) {
                    found_error = true;
                    continue;
                }
                var num = data_table[i]['value'];
                num = num.toString();
                val = num.trim();

                if (val == '') {
                    found_error = true;
                    continue;
                }

                data_table[i]['value'] = val;
            }

            if (found_error) {
                alert_notif('Please check table for empty row!', "error");
                return;
            }

            if (len == 0) {
                alert_notif('Please check table for empty row!', "error");
                return;
            }

            Swal.fire({
                title: 'Are you sure to save this?',
                text: '<?php echo ($select_academic_year_id == 0) ? "This is default settings that may affect other semester! \\r\\n You won\'t be able to revert this!" : "You won\'t be able to revert this!"; ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("<?php echo BASE_URL; ?>registrar/grade_settings_ajax.php", {
                            action: 'SETTING',
                            type: global_type,
                            data: JSON.stringify(data_table),
                            school_year: school_year_id
                        },
                        function(returnedData) {
                            var obj = returnedData; // JSON.parse(returnedData);
                            if (obj.result == "success") {
                                Swal.fire(
                                    'Saved!',
                                    'Data has been saved.',
                                    'success'
                                );
                            } else {
                                alert_notif("Error: " + obj.errors, "error");
                            }
                        }).fail(function() {
                        alert_notif("Error Encounter", "error");
                    });
                }
            });
        });

        var select_academic_year = $('#school_year_id').selectize({
            valueField: 'school_year_id',
            labelField: ['school_year'],
            searchField: ['school_year'],
            options: list_academic_year,
            items: <?php echo $select_academic_year; ?>,
            persist: false,
            maxItems: 1,
            dropdownParent: "body",
            render: {
                option: function(item, escape) {
                    return '<div> <h5 class="title">F.Y : ' + escape(item.school_year) + ' - ' + escape(item.sem) + '</h5></div>';

                },
                item: function(item, escape) {
                    return '<div> F.Y. <span class="title">' + escape(item.school_year) + ' - ' + escape(item.sem) + '</span></div>';

                }
            },
            onChange: function(value) {

            }

        });


        var btn_year = document.getElementById('btn_gen_year');
        if (btn_year) {
            addListener(btn_year, "click", function() {
                var val = select_academic_year[0].selectize.getValue();
                if (val == '') {

                } else {
                    insertParam("academic_year", val);
                }
            });
        }

        function add_overlay() {
            var body = document.querySelector('body');
            var overlay = document.querySelector('.overlay');
            if (overlay) {} else {
                var div = document.createElement('div');
                div.className = "overlay";
                body.appendChild(div);
            }
        }

        add_overlay();
        $(document).on({

            ajaxStart: function() {
                addClass(document.querySelector('body'), 'loading');
                isPaused = true;
            },
            ajaxStop: function() {
                removeClass(document.querySelector('body'), "loading");
                isPaused = false;
            }
        });



    })();

    function insertParam(key, value) {
        key = escape(key);
        value = escape(value);

        var url = window.location.href;
        var page_y = 0;
        var py = false;
        var update = false;
        if (document.documentElement) {
            page_y = document.documentElement.scrollTop;
        }

        var kvp = document.location.search.substr(1).split('&');
        if (kvp == '') {
            // document.location.search = '?' + key + '=' + value;
        } else {
            var i = kvp.length - 1;
            var x;

            while (i >= 0) {
                x = kvp[i].split('=');
                if (x[0] == 'page_y') {

                    x[1] = page_y;
                    kvp[i] = x.join('=');
                    py = true;
                }

                if (x[0] == key) {
                    x[1] = value;
                    kvp[i] = x.join('=');
                    update = true;
                }
                i--;
            }

            if (update == false) {
                kvp[kvp.length] = [key, value].join('=');
            }

        }

        if (url.indexOf('?') !== -1) {
            url = url.slice(0, url.indexOf('?'));
        }

        var separator = "?";
        var addlink = "";

        if (kvp == '') {
            url = url + "?page_y=" + page_y + "&" + key + '=' + value;;
            separator = "&";
        } else {
            addlink = separator + kvp.join('&')
        }
        //return;
        window.location = url + addlink;

    }
</script>

</html>