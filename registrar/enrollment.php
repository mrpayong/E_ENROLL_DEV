<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Enrollment";
$get_user_value = strtoupper($_GET['none'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
];

if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL);
    exit();
}

$defaultFy = "";
$fy_id = '';
$sql = "SELECT school_year, sem, school_year_id FROM school_year WHERE isDefault = 1 LIMIT 1";
if($fetchSql = call_mysql_query($sql)){
    if($data = call_mysql_fetch_array($fetchSql)){
        $defaultFy = $data['school_year']." ".$data['sem'];
        $fy_id = $data['school_year_id'];
    }
}

?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
</head>
<body>
<div class="wrapper">
    <?php include_once DOMAIN_PATH . '/global/sidebar.php';?>
    <div class="main-panel">
        <?php include_once DOMAIN_PATH . '/global/header.php';?>
        <div class="container">
            <div class="page-inner">
                <?php
                include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                ?>

                <section class="card m-0">
                    <header class="card-header bg-primary text-white rounded-2 rounded-bottom-0" 
                        style="padding:0.75rem; padding-left:1.25em; padding-bottom:0.5rem;">
                        <label class="fs-5 text-white fw-bolder">Enrollment Table</label>
                    </header>
                    <div class="card-body pt-1" style="min-height: 40rem;">
                        <div class="row">
                            <div class="d-flex flex-md-row flex-column justify-content-start align-items-center col-md-12">
                                <div class="input-group my-3 align-items-center">
                                    <label for="syDropdown" class="fw-bold me-2" id="inputGroup-sizing-default">Fiscal Year</label>
                                    <select id="syDropdown" style="width:250px;">
                                    </select>
                                    <button id="generateBtn" style="height: 2.1rem; margin-bottom:0.35rem !important;" 
                                    class="bg-success text-white border-0 rounded-end">
                                        Generate</button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <div class="table-bordered" id="enrollTable"></div>

                        </div>

                    </div>
                </section>

                <div class="modal fade" id="viewStudentInfo" tabindex="-1" aria-labelledby="viewLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <form class="modal-content">
                            <div class="modal-header bg-primary">
                                <h5 class="modal-title" id="viewLabel">Student Academic Information</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <i class="bi bi-person-badge"></i>
                                        <label for="idNumber" class="form-label">Student ID</label>
                                        <input type="text" class="form-control fw-bold" id="idNumber" name="idNumber" readOnly>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label for="student_name" class="form-label">Name</label>
                                        <input type="text" class="form-control fw-bold" id="student_name" name="student_name" readOnly>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label for="section" class="form-label">Section</label>
                                        <input type="text" class="form-control fw-bold" id="section" name="section" readOnly>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <i class="bi bi-person-badge"></i>
                                        <label for="yr_lvl" class="form-label">Year Level</label>
                                        <input type="text" class="form-control  fw-bold" id="yr_lvl" name="yr_lvl" readOnly>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label for="program" class="form-label">Program</label>
                                        <input type="text" class="form-control  fw-bold" id="program" name="program" readOnly>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label for="student_class" class="form-label">Student Classification</label>
                                        <input type="text" class="form-control  fw-bold" id="student_class" name="student_class" readOnly>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-md-12">
                                        <label for="fiscal" class="form-label">Fiscal Year</label>
                                        <input type="text" class="form-control  fw-bold" id="fiscal" name="fiscal" readOnly>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="addUnitsModal" tabindex="-1" aria-labelledby="addUnitLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form class="modal-content" id="AddUnitsForm">
                            <div class="modal-header bg-primary">
                                <h5 class="modal-title" id="addUnitLabel"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <i class="bi bi-person-badge"></i>
                                        <label for="required_units" class="form-label">Required Units</label>
                                        <input type="number" class="form-control fw-bold" id="required_units" name="required_units" required readonly>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label for="additional_units" class="form-label">Units To Add</label>
                                        <input type="number" class="form-control fw-bold" id="additional_units" name="additional_units" required>
                                        <p class="text-secondary">For this semester only</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-md btn-primary">Submit</button>
                                <button type="button" class="btn btn-md btn-danger" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>


            </div>
        </div>

        <?php include_once FOOTER_PATH; ?>
    </div>
</div>


</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
(function(){
    function loadingAPIrequest(status){
        if(status === true){
            swal({
                title: "Loading",
                icon: 'info',
                text: "Please wait",
                buttons:false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });
        }
        if(status === false){
            swal.close();
        }

    }

    const actionButtons = function(cell){
        const student_id = cell.getRow().getData().student_id;
        const stnd_class = cell.getRow().getData().student_classification;

        let action = ``;
        if(stnd_class === "Regular"){
            action += `
            <div class="d-flex justify-content-evenly">
                <button class="btn btn-info btn-sm view-btn" style="color:black !important;" data-id="${student_id}"><i class="fas fa-eye"></i> View</button>
            </div>
            `;
        }
        if(stnd_class === "Irregular"){
            action += `
            <div class="d-flex justify-content-evenly gap-1">
                <button class="btn btn-info btn-sm view-btn" style="color:black !important;" data-id="${student_id}"><i class="fas fa-eye"></i> View</button>
                <button class="btn btn-secondary btn-sm modify-btn" style="color:black !important;" data-id="${student_id}"><i class="fas fa-edit"></i> Modify Units</button>
            </div>
            `;
        }
  
        return action;
    };
    
    const enrollTable = new Tabulator("#enrollTable", {
        ajaxURL: "<?php echo BASE_URL; ?>registrar/actions/fetchEnrollees.php",
        ajaxConfig: "GET",
        ajaxFiltering: true,
        layout:"fitDataStretch",
        ajaxSorting: true,
        placeholder: "No Data Available",
        pagination: "remote",
        headerFilterPlaceholder: "Search",
        paginationSize: 10,
        minHeight:500,
        columns:[
            {
                title:"Student ID", 
                field:"student_id_no", 
                headerFilter:"input",
                hozAlign: "center",
            },
            {
                title:"Name", 
                field:"name", 
                headerFilter:"input",
                hozAlign: "center",
            },
            {
                title:"Section", 
                field:"section", 
                headerFilter:"input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title:"Program", 
                field:"program", 
                headerFilter:"input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title:"Curriculum", 
                field:"curriculum_code", 
                headerFilter:"input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title:"Year Level", 
                field:"year_level", 
                headerFilter:"input",
                hozAlign: "center",
                formatter: function(cell){
                    console.log(typeof cell.getData().year_level)
                    const yr_lvl = cell.getData().year_level;
                    switch(yr_lvl){
                        case 1:
                            return "1st Year";
                            break;
                        case 2:
                            return "2nd Year";
                            break;
                        case 3:
                            return "3rd Year";
                            break;
                        case 4:
                            return "4th Year";
                            break;
                        default:
                            return "No year level";
                    }
                }
            },
            {
                title:"Earned Units", 
                field: "earned_units_sem",
                headerFilter:"input",
                hozAlign: "center",
                formatter: function(cell){
                    const data = cell.getValue();
                    return data !== null ? data : "No grade yet";
                }
            },
            {
                title:"Required Units", 
                field: "required_units_sem",
                headerFilter:"input",
                hozAlign: "center",
                formatter: function(cell){
                    const data = cell.getValue();
                    console.log('req uints', data)
                    return data !== null ? data : "No assigned curriculum yet";
                }
            },
            {
                title:"Student Classification", 
                field: "student_classification",
                headerFilter:"input",
                hozAlign: "center"
            },
            {
                title: "Actions",
                formatter:actionButtons,
                hozAlign: "center",
                headerHozAlign: "center",
                minWidth: "200rem"
            }
        ],
    })

    function populateSYDropdown(selectedId = null) {
        const $sy = $('#syDropdown');

        $.ajax({
            url: "<?php echo BASE_URL; ?>/registrar/actions/fetchFiscalYear.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
            if (!res || !Array.isArray(res.data)) return;

            // destroy previous selectize if exists
            if ($sy[0].selectize) $sy[0].selectize.destroy();

            $sy.empty();
            $sy.append('<option value="">Select Fiscal Year</option>');
            let defaultId = null;

            res.data.forEach(function(row) {
                console.log("fy: ", row)
                const label = `${row.school_year} ${row.sem}`;
                $sy.append(
                    $('<option>', { value: row.school_year_id, text: label })
                );
                if (Number(row.isDefault) === 1) {
                    defaultId = row.school_year_id;
                }
            });

            $sy.selectize({
                allowEmptyOption: true,
                create: false,
                sortField: 'text',
                placeholder: 'Select Fiscal Year'
            });

            const selectize = $sy[0].selectize;
            selectize.clear(true);
            if (defaultId !== null) {
                selectize.setValue(String(defaultId), true);
            }
            },
            error: function() {
            swal({
                title: "Error",
                icon: "error",
                text: "Failed to load fiscal years.",
                button: true
            });
            }
        });
    }

    populateSYDropdown('syDropdown');
    
    function formatYear(yr_lvl){
        switch(yr_lvl){
            case 1:
                return "1st Year";
                break;
            case 2:
                return "2nd Year";
                break;
            case 3:
                return "3rd Year";
                break;
            case 4:
                return "4th Year";
                break;
            default:
                return "No year level";
        }
    }

    // const FY = <?php echo json_encode($defaultFy); ?>;
    // (function FyInput(){
    //     document.getElementById('fyInput').value = FY ?? "No Fiscal Year";
    // })();

    $('#syDropdown').on('change', function(){
        const fyId = $(this).val() || '';
        enrollTable.setData("<?php echo BASE_URL; ?>registrar/actions/fetchEnrollees.php", {
            school_year_id: $('#syDropdown').val()
        });
    });

    let student_id_no = "";
    let yr_level = "";
    let load_flag = "";
    let sy_id = "";
    let curr_id = "";
    function underOverLoad(){
        return swal({
            title: "Student Study Load",
            text: "Should this student be underload or overload?",
            buttons: {
                cancel: "Cancel",
                under: { text: "Underload", value: "under" },
                over: { text: "Overload", value: "over" }
            }
        })
    }

    document.querySelector('#enrollTable').addEventListener('click', function(e){
        e.preventDefault();
        const view = e.target.closest('.view-btn');
        const mod = e.target.closest('.modify-btn');
        if(view){
            const btn_id = view.getAttribute('data-id');
            const row = enrollTable.getRows().find(r => r.getData().student_id == btn_id);



            const rowData = row.getData();
            document.getElementById('idNumber').value = rowData.student_id_no;
            document.getElementById('student_name').value = rowData.name;
            document.getElementById('section').value = rowData.section;
            document.getElementById('yr_lvl').value = formatYear(rowData.year_level);
            document.getElementById('program').value = rowData.program;
            document.getElementById('fiscal').value = rowData.fiscal_year;

            $('#viewStudentInfo').modal('show');
        }

        if(mod){
            const btn_id = mod.getAttribute('data-id');
            const row = enrollTable.getRows().find(r => r.getData().student_id == btn_id);

            const rowData = row.getData();
            console.log('row: ', rowData);

            underOverLoad().then(function(loadStatus){
                if(loadStatus === "over"){
                    student_id_no = rowData.student_id_no;
                    yr_level = rowData.year_level;
                    load_flag = 0;
                    sy_id = rowData.school_year_id;
                    curr_id = rowData.curriculum_id;
                    document.getElementById('addUnitLabel').textContent = `${rowData.name}`;
                    document.getElementById('required_units').value = Number(rowData.required_units_sem)
                    $('#addUnitsModal').modal('show');
                }
                if(loadStatus === "under"){
                    student_id_no = rowData.student_id_no;
                    yr_level = rowData.year_level;
                    load_flag = 0;
                    sy_id = rowData.school_year_id;
                    curr_id = rowData.curriculum_id;
                }
            });
            return;


        }
    })


    $('#AddUnitsForm').on('submit', function(e){
        e.preventDefault();
        const formData = jQuery("#AddUnitsForm").serializeArray();

        const newData = [
            {
                name: "submitAddUnits",
                value: "createUnits"
            },
            {
                name: "student_id_no",
                value: student_id_no
            },
            {
                name: "yr_lvl",
                value: yr_level
            },
            {
                name: "load_flag",
                value: load_flag
            },
            {
                name: "school_year_id",
                value: sy_id
            },
            {
                name: "curriculum_id",
                value: curr_id
            }
        ];
        
        const postData = formData.concat(newData);
        $.ajax({
            url:"<?php echo BASE_URL; ?>registrar/actions/enroll_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data){
                    if(data.code === 200 && data.msg_status === true){
                        swal({
                            title: "Success",
                            icon: "success",
                            text: data.msg_response,
                            button:false,
                            timer: 2000
                        }).then(function(){
                            $('#addUnitsModal').modal('hide');
                            $('#AddUnitsForm')[0].reset();
                            enrollTable.setData();
                        })
                    }
                    if(data.code === 501 && data.msg_status === false){
                        swal({
                            title: "Failed to add units.",
                            icon: "error",
                            text: data.msg_response,
                            button:true,
                        })
                    }
                    if(data.code === 502 && data.msg_status === false){
                        swal({
                            title: "Failed to add units.",
                            icon: "error",
                            text: data.msg_response,
                            button:true,
                        })
                    }
                    if(data.code === 503 && data.msg_status === false){
                        swal({
                            title: "Failed to add units.",
                            icon: "error",
                            text: data.msg_response,
                            button:true,
                        })
                    }
                    if(data.code === 500 && data.msg_status === false){
                        swal({
                            title: "Failed to add units.",
                            icon: "error",
                            text: data.msg_response,
                            button:true,
                        })
                    }
                }
            },
            error: function(xhr, status, error){
                swal.close();
                swal({
                    title: "Error",
                    icon: "error",
                    text: "Network/Server error occured",
                    button:true
                })
            }
        })
    })
})();
</script>
</html>
