
<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Department";
$get_user_value = strtoupper($_GET['none'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
];

if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL);
    exit();
}

$departments = array();
$sql_depts = "SELECT department_id, department FROM departments";
if($sql_depts = call_mysql_query($sql_depts)){
    while($dept = call_mysql_fetch_array($sql_depts)){
        array_push($departments, $dept);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Use only for purple badge, as NiceAdmin/Bootstrap doesn't have this by default */
        .badge-purple {
            background: #f3e8ff !important;
            color: #a259e6 !important;
        }
    </style>
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
                <main id="main">
                    <section class="section">

                        <div class="row justify-content-center m-0">
                            <section class="card shadow-sm  p-0" style="margin:auto;">
                                <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                    <label class="fw-semibold text-white mb-3 mb-md-0 fs-5">Department Table</label>
                                    <button
                                        id="addDepartmentBtn"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#departmentModal"
                                        class="btn btn-info btn-sm fs-6 fw-semibold rounded-3 d-flex align-items-center">
                                        <i class="bi bi-building-add me-2"></i> Add Department
                                    </button>
                                </header>
                                <div class="table-responsive px-3 pb-4 pt-1 mt-3" style="min-height: 40rem;">
                                    <div id="department-table" class="table-bordered border rounded"></div>
                                </div>

                            </section>
                        </div>
                    </section>
                </main>

                <!-- Modal for Add Department -->
                <section>
                    <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-modal="true" role="dialog">
                        <div class="modal-dialog" role="document">
                        <form class="modal-content" id="departmentForm" autocomplete="off">
                            <header class="modal-header py-2 bg-primary text-white">
                            <h2 class="modal-title fs-5" id="departmentModalLabel">Add Department</h2>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </header>
                            <section class="modal-body">
                            <div class="mb-3">
                                <label for="departmentName" class="form-label">Department Name</label>
                                <input type="text" class="form-control" id="departmentName" name="departmentName" required>
                            </div>
                            <div class="mb-3">
                                <label for="deptHead" class="form-label">Dean</label>
                                <select class="form-select" id="deptHead" name="deptHead" required>
                                    <option value="">Choose a Dean</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="codeName" class="form-label">Department Code</label>
                                <input type="text" class="form-control" id="codeName" name="codeName" required>
                            </div>
                            </section>
                            <footer class="modal-footer py-1">
                            <button type="submit" class="btn btn-primary">Submit</button>
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="cancelDepartmentBtn">Cancel</button>
                            </footer>
                        </form>
                        </div>
                    </div>
                </section>

                <!-- modal for edit department -->
                <section>
                    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-modal="true" role="dialog">
                        <div class="modal-dialog" role="document">
                        <form class="modal-content" id="editDepartmentForm" autocomplete="off">
                            <header class="modal-header py-2 bg-primary text-white">
                            <h2 class="modal-title bg-primary text-light fs-5" id="editDepartmentModalTitle"></h2>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </header>
                            <section class="modal-body">
                            <div class="mb-3">
                                <label for="departmentNewName" class="form-label">Department Name</label>
                                <input type="text" class="form-control" id="departmentNewName" name="departmentNewName" required>
                            </div>
                            <div class="mb-3">
                                <label for="editDeptHead" class="form-label">Dean</label>
                                <select class="form-select" id="editDeptHead" name="editDeptHead" required>
                                    <option id="editDeptHeadId" value="">Choose a Dean</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="newCodeName" class="form-label">Department Code</label>
                                <input type="text" class="form-control" id="newCodeName" name="newCodeName" required>
                            </div>
                            </section>
                            <footer class="modal-footer py-1">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="cancelDepartmentBtn">Cancel</button>
                            </footer>
                        </form>
                        </div>
                    </div>
                </section>

                <!-- add program  -->
                <section>
                    <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <form class="modal-content" id="programForm" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <h5 class="modal-title" id="programModalLabel">Add Program</h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control bg-secondary text-black" id="department" name="department" readOnly required>
                                    </div>

                                    <div class="mb-3 d-flex flex-row gap-2 w-100">
                                        <div class="d-flex flex-column gap-2 w-50">
                                            <label for="programName" class="form-label">Program Name</label>
                                            <input type="text" class="form-control" id="programName" name="programName" required>
                                        </div>
                                        <div class="d-flex flex-column gap-2 w-50">
                                            <label for="programCode" class="form-label">Program Code</label>
                                            <input type="text" class="form-control" id="programCode" name="programCode" required>
                                        </div>
                                    </div>



                                    <div class="mb-3">
                                        <label for="major" class="form-label">Major</label>
                                        <input type="text" class="form-control" id="major" name="major">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- add major -->
                <section>
                    <div class="modal fade" id="majorModal" tabindex="-1" aria-labelledby="majorModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <form class="modal-content" id="majorForm" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <h5 class="modal-title" id="majorModalLabel">Add major</h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">

                                    <div class="mb-3 d-flex flex-row gap-2 w-100">
                                        <div class="d-flex flex-column gap-2 w-50">
                                            <label for="programName-major" class="form-label">Program Name</label>
                                            <input type="text" class="form-control bg-secondary text-black" id="programName-major" name="programName-major" readOnly required>
                                        </div>
                                        <div class="d-flex flex-column gap-2 w-50">
                                            <label for="programCode-major" class="form-label">Program Name</label>
                                            <input type="text" class="form-control bg-secondary text-black" id="programCode-major" name="programCode-major" readOnly required>
                                        </div>
                                    </div>



                                    <div class="mb-3">
                                        <label for="major" class="form-label">Major</label>
                                        <input type="text" placeholder="e.g. Mathematics, English, Filipino"class="form-control" id="major" name="major" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            
                <!-- update program -->
                <section>
                    <div class="modal fade" id="editProgramModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <form class="modal-content" id="editProgramModalForm" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <h5 class="modal-title" id="editProgramModalLabel"></h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>


                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <select id="departmentProgram" name="department" class=" text-black" required></select>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="newCode" class="form-label">Program Code</label>
                                            <input type="text" class="form-control" id="newCode" name="newCode" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="newProgram" class="form-label">Program Name</label>
                                            <input type="text" class="form-control" id="newProgram" name="newProgram" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="newMajor" class="form-label">Major</label>
                                        <input type="text" class="form-control" id="newMajor" name="newMajor">
                                    </div>
                                </div>



                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <?php include_once FOOTER_PATH; ?>
    </div>
</div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
        // Modal logic
        var departmentModal = new bootstrap.Modal(document.getElementById('departmentModal'));
        var cancelBtn = document.getElementById('cancelDepartmentBtn');
        var form = document.getElementById('departmentForm');
        let swalOpenLock = false;
        let submitLock = false;

        if(cancelBtn) cancelBtn.addEventListener('click', function () {
            departmentModal.hide();
        });

        function loadingAPIrequest(status){
            if(status === true){
                swal({
                    title: "Loading",
                    icon: 'info',
                    text: "Please wait",
                    button:false,
                    closeOnClickOutside: false,
                    closeOnEsc: false
                });
            }
            if(status === false){
                swal.close();
            }
        }

        const departments = <?php echo json_encode($departments); ?>
        
        function populateDepartmentDropdown(selector, selectedId = null) {
            const $dropdown = $(selector);

            $.ajax({
                url: "<?php echo BASE_URL; ?>/registrar/actions/fetchDeptForProgram.php",
                method: "GET",
                dataType: "json",
                success: function(res) {
                if (!res || res.status !== true || !Array.isArray(res.data)) return;

                // destroy old selectize
                if ($dropdown[0].selectize) {
                    $dropdown[0].selectize.destroy();
                }

                $dropdown.empty();
                $dropdown.append('<option value="" selected disabled>Select Department</option>');

                res.data.forEach(function(item){
                    $dropdown.append(
                    $('<option>', {
                        value: item.department_id,
                        text: item.department
                    })
                    );
                });

                $dropdown.selectize({
                    allowEmptyOption: true,
                    create: false,
                    sortField: 'text'
                });

                const selectize = $dropdown[0].selectize;
                selectize.clear(true);

                if (selectedId) {
                    selectize.setValue(String(selectedId), true);
                }
                },
                error: function() {
                swal({
                    title: "Error",
                    icon: "error",
                    text: "Failed to load departments.",
                    button: true
                });
                }
            });
        }


        function actionsFormatter(cell) {
            const row = cell.getRow().getData();

            // if(row.major === "") {
            //     return `
            //     <button data-id="${row.program_id}" class="btn btn-sm btn-primary me-2 btn-major fs-6" title="Add Major"><i class="bi bi-plus-circle"></i> Add Major</button>
            // `;
            // }
            return `
                <button data-id="${row.program_id}" class="btn btn-sm btn-primary me-2 btn-major fs-6" title="Add Major"><i class="bi bi-plus-circle"></i> Add Major</button>
                <button data-id="${row.program_id}" data-major="${row.major}" class="btn btn-sm btn-warning update-prog-btn fs-6" title="Update Program"><i class="bi bi-pencil-square"></i> Update Program</button>
            `;
        }

        const departmentTable = new Tabulator("#department-table", {
            ajaxURL: "<?php echo BASE_URL; ?>registrar/actions/fetchDepartment.php",
            ajaxConfig: "GET",            
            layout: "fitDataStretch",
            movableColumns: true,
            ajaxFiltering: true,
            ajaxSorting: true,
            headerFilterPlaceholder: "Search",
            placeholder: "No Data Found",
            pagination: "remote",
            paginationSize: 10,
            groupStartOpen: false,
            groupBy: function(data){
                return data.department
            },
            groupHeader: function(value, count, data, group) {
                const dept = data[0];
                return `
                    <span class="text-black fw-bold">${dept.department} (${dept.code_name})</span>
                    <span class="ms-2">Dean: ${dept.dean}</span>
                    <button data-id="${dept.department_id}" class="btn btn-sm btn-success ms-2 fs-6 add-btn"><i class="bi bi-plus-circle-dotted"></i> Add Program</button>
                    <button data-id="${dept.department_id}" class="btn btn-sm btn-primary ms-2 fs-6 edit-dept-btn"><i class="bi bi-pencil-square"></i> Update Department</button>
                `;
            },
            columns: [
                {
                    title: "Actions",
                    field: "actions",
                    headerSort: false,
                    headerFilter: false,
                    headerHozAlign: "center",
                    hozAlign: "center",
                    frozen: !isMobile(),
                    formatter: actionsFormatter,
                },
                {
                    title: "Program Code",
                    field: "short_name",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    hozAlign: "center",
                },
                {
                    title: "Program Name",
                    field: "program",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    hozAlign: "left",
                },
                {
                    title: "Major",
                    field: "major",
                    headerFilter: "input",
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    hozAlign: "center",
                },
                {
                    title: "Department",
                    field: "department",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    headerHozAlign: "center",
                    hozAlign: "center",
                },
                {
                    title: "Department Code",
                    field: "code_name",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    headerHozAlign: "center",
                    hozAlign: "center",
                },
            ]
        });


 
        let department_id = '';
        let departmentNewName = '';
        let program_id = '';
        let oldMajor = '';
        // set up data for Edit form
        document.querySelector("#department-table").addEventListener("click", function(e){
            const editBtn = e.target.closest(".edit-dept-btn")
            const addProgram = e.target.closest(".add-btn")
            const addMajor = e.target.closest(".btn-major")
            const updateProg = e.target.closest('.update-prog-btn');
            if(editBtn){
                const rowId = editBtn.getAttribute('data-id');
                
                const row = departmentTable.getRows().find(r => r.getData().department_id == rowId);
                if(!row) {
                    swal({
                        title: "Something went wrong.",
                        icon: "error",
                        text: "Can't find this department. Possible network disruption.",
                        timer: 5000,
                        button: true
                    })
                    return;
                }

                const rowData = row.getData();
                const newName = document.getElementById('departmentNewName').value = rowData.department;
                document.getElementById('newCodeName').value = rowData.code_name;
                DeanData('#editDeptHead', rowData.user_id);
                document.getElementById('editDepartmentModalTitle').textContent = `Update ${rowData.department}`; 
                department_id = rowId;
                departmentNewName = newName;
                $('#editDepartmentModal').modal('show');

            }
            if(addProgram){
                const rowId = addProgram.getAttribute('data-id');

                const row = departmentTable.getRows().find(r => r.getData().department_id == rowId);
                if(!row) {
                    swal({
                        title: "Something went wrong.",
                        icon: "error",
                        text: "Can't find this department",
                        timer: 5000,
                        button: true
                    })
                    return;
                }

                const rowData = row.getData();

                document.getElementById('department').value = rowData.department;
                department_id = rowData.department_id;
                console.log('row data: ', row.getData());
                // $('#archiveDepartmentModal').modal('show');
                $('#programModal').modal('show');
                return;
            }
            if(addMajor){
                const rowId = addMajor.getAttribute('data-id');

                const row = departmentTable.getRows().find(r => r.getData().program_id == rowId);
                if(!row) {
                    swal({
                        title: "Something went wrong.",
                        icon: "error",
                        text: "Can't find this department",
                        timer: 5000,
                        button: true
                    })
                    return;
                }

                const rowData = row.getData();

                document.getElementById('programName-major').value = rowData.program;
                document.getElementById('programCode-major').value = rowData.short_name;
                program_id = rowData.program_id
                console.log('row data: ', row.getData(), "ROW ID: ", rowId);
                $('#majorModal').modal('show');
                return;
            }
            if(updateProg){
                const rowId = updateProg.getAttribute('data-id');
                const majorValue = updateProg.getAttribute('data-major');

                const row = departmentTable.getRows().find(r => {
                    const data = r.getData();
                    return data.program_id == rowId && data.major == majorValue;
                });
                if(!row) {
                    swal({
                        title: "Something went wrong.",
                        icon: "error",
                        text: "Can't find this program. Possible network disruption.",
                        button: true
                    })
                    return;
                }

                const rowData = row.getData();

                console.log('row data: ', rowData);
                document.getElementById('newProgram').value = rowData.program;
                document.getElementById('newCode').value = rowData.short_name;
                document.getElementById('newMajor').value = rowData.major;
                oldMajor = rowData.major;
                // department_id = rowData.department_id;
                populateDepartmentDropdown('#departmentProgram', String(rowData.department_id));
                document.getElementById('editProgramModalLabel').textContent = `Update ${rowData.program}`; 
                program_id = rowData.program_id;
                $('#editProgramModal').modal('show');

            }
        })


        // create
        $('#programForm').on('submit', function(e){
            e.preventDefault();
            const formData = jQuery('#programForm').serializeArray();

            const newData = [
                {
                    name: "submitProgram",
                    value: "createProgram"
                },
                {
                    name: "department_id",
                    value: department_id
                }
            ]

            const postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>registrar/actions/program_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: loadingAPIrequest(true),
                complete: loadingAPIrequest(false),
                success: function(data){
                    if(data){
                        swal.close();
                        if(data.status === true && data.code === 200){
                            swal({
                                title: "Program created!",
                                text: "Program has been created successfully!",
                                icon: "success",
                                timer: 3000,
                                button: false,
                            }).then(function(){
                                $('#programForm')[0].reset();
                                $('#programModal').modal('hide');
                                departmentTable.setData();
                            })
                        }
                        if(data.status === false && data.code === 501){
                            swal({
                                title: "Failed to create program.",
                                text: data.msg_response,
                                icon: "error",
                                button: true,
                            })
                        }
                        if(data.status === false && data.code === 502){
                            swal({
                                title: "Failed to create program.",
                                text: "Program name or code already exist.",
                                icon: "error",
                                button: true,
                            }).then(function(){
                                document.getElementById('programCode').value = "";
                                document.getElementById('programName').value = "";
                            })
                        }
                        if(data.status === false && data.code === 500){
                            swal({
                                title: "An error occured.",
                                text: "You're good, unkown error that needs consulting has occured. Consult support at MISD is advised.",
                                icon: "error",
                                button: true,
                            })
                        }
                    }
                },
                error: function(xhr, status, error){
                    swal({
                        title: "Error",
                        icon: "error",
                        text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                        button: true
                    });
                }
            })
        })

        // add major
        $('#majorForm').on('submit', function(e){
            e.preventDefault();
            const formData = jQuery('#majorForm').serializeArray();

            const newData = [
                {
                    name: "submitProgram",
                    value: "addMajor"
                },
                {
                    name: "programId",
                    value: program_id
                }
            ]

            const postData = formData.concat(newData);
            console.log('postData: ', postData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>registrar/actions/program_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: loadingAPIrequest(true),
                complete: loadingAPIrequest(false),
                success: function(data){
                    if(data){
                        swal.close();
                        if(data.msg_status === true && data.code === 200){
                            swal({
                                title: "Major added!",
                                text: data.msg_response,
                                icon: "success",
                                timer: 3000,
                                button: false,
                            }).then(function(){
                                $('#majorForm')[0].reset();
                                $('#majorModal').modal('hide');
                                departmentTable.setData();
                            })
                        }
                        if(data.msg_status === false && data.code === 505){
                            swal({
                                title: "Failed to add",
                                text: data.msg_response,
                                icon: "error",
                                button: true,
                            })
                        }
                    }
                },
                error: function(xhr, status, error){
                    swal({
                        title: "Error",
                        icon: "error",
                        text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                        button: true
                    });
                }
            })
        })

        //  create department
        DeanData('#deptHead'); 
        $(function(){
            $('#departmentForm').on('submit', function(e){
                e.preventDefault();

                const formData = jQuery('#departmentForm').serializeArray();


                const newData = [{
                    name: 'departmentSubmit',
                    value: 'createDepartment'
                }];

                
                const postData = formData.concat(newData);

                $.ajax({
                    url: "<?php echo BASE_URL ?>/registrar/actions/department_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: loadingAPIrequest(true),
                    complete: loadingAPIrequest(false),
                    success: function(data){
                        swal.close(); //close loading
                        if(data){
                            if(data.code === 200 && data.msg_status === true){
                                swal({
                                    title: "Department Created",
                                    icon: "success",
                                    text: data.msg_response,
                                    button: false,
                                    timer: 3000,
                                }).then(function(){
                                    $('#departmentModal').modal('hide');
                                    $('#departmentForm')[0].reset();
                                    departmentTable.setData();
                                });
                            };
                            if(data.code === 501 && data.msg_status === false){
                                // Require fields error hanlder
                                swal({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 5000,
                                })
                            };
                            if(data.code === 502 && data.msg_status === false){
                                // Department duplicate error handler
                                swal({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 5000,
                                })
                            };
                            if(data.code === 404 && data.msg_status === false){
                                //  caught error in try-catch handler
                                swal({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: "Failed to execute action.",
                                    button: true,
                                    timer: 5000,
                                })
                            };
                            if(data.code === 503 && data.msg_status === false){
                                // Failed query handler
                                swal({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: "Action was unsuccessful",
                                    button: true,
                                    timer: 5000,
                                })
                                console.error(data.msg_response);
                            };
                            if(data.code === 504 && data.msg_status === false){
                                // dean already assigned
                                swal({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 5000,
                                })
                            };
                            if(data.code === 500 && data.msg_status === false){
                                // POST method and requeste condition error handler
                                swal({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 5000,
                                })
                            };
                        } else {
                            swal({
                                title: "Failed creating department",
                                icon: "error",
                                text: "Network/System disruption occured",
                                button: true,
                            })
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
                    
                });

            })
        });

        // edit department
        $(function(){
            $('#editDepartmentForm').on('submit', function(e){
                e.preventDefault();
                let formData = jQuery('#editDepartmentForm').serializeArray();
                

                const newData = [
                    {
                        name: "departmentSubmit",
                        value: "updateDepartment"
                    },
                    {
                        name: 'department_id',
                        value: department_id
                    },
                ];

                const postData = formData.concat(newData);

                
                $.ajax({
                    url: "<?php echo BASE_URL; ?>registrar/actions/department_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: loadingAPIrequest(true),
                    complete: loadingAPIrequest(false),
                    success: function(data){
                        if(data){
                            if(data.code === 200 && data.msg_status === true){
                                swal({
                                    title: "Department Updated!",
                                    icon: "success",
                                    text: data.msg_response,
                                    button: false,
                                    timer: 2000
                                }).then(function(){
                                    $('#editDepartmentModal').modal('hide');
                                    $('#editDepartmentForm')[0].reset();
                                    departmentTable.setData();
                                });
                            };
                            if(data.code === 501 && data.msg_status === false){
                                // Require fields error hanlder
                                swal({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 500 && data.msg_status === false){
                                // Require fields error hanlder
                                swal({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 503 && data.msg_status === false){
                                swal({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 502 && data.msg_status === false){
                                swal({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 404 && data.msg_status === false){
                                swal({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 400 && data.msg_status === false){
                                swal({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 504 && data.msg_status === false){
                                swal({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    button: true,
                                    timer: 2000,
                                })
                            };
                        } else {
                            swal({
                                title: "Failed updating department",
                                icon: "error",
                                text: "Network/System disruption occured",
                                button: true,
                            })
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
        })

        function DeanData(selectSelector, selectedDeanId = null){
            $.ajax({
                url: "<?php echo BASE_URL;?>/registrar/actions/fetchDean.php",
                method: "GET",
                dataType: "json",
                success: function(data){
                    var $deanSelect = $(selectSelector);
                    $deanSelect.empty();
                    $deanSelect.append('<option value="">Choose a Dean</option>');
                    data.forEach(function(dean){
                        $deanSelect.append(
                            $('<option>', {
                                value: dean.user_id,
                                text: dean.name,
                                selected: dean.user_id == selectedDeanId
                            })
                        );
                    });

                    if(selectedDeanId){
                        $deanSelect.val(selectedDeanId)
                    }
                },
                error: function(){
                    swal({
                        title: "Error",
                        icon: "error",
                        text: "Failed to load deans",
                        button:true
                    });
                },
            })
        }

        $('#departmentModal').on('show.bs.modal', function(){
            DeanData();
        })

        document.addEventListener('keydown', function (e) {
            const swalVisible = document.querySelector('.swal-overlay--show-modal');
            if (e.key === 'Enter' && (swalOpenLock || swalVisible)) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        $('#editProgramModalForm').on('submit', function(e){
            e.preventDefault();
            if (submitLock) return;
            submitLock = true;

            const formData = jQuery('#editProgramModalForm').serializeArray();

            const newData = [
                {
                    name: "submitProgram",
                    value: "editProgram"
                },
                {
                    name: "programId",
                    value: program_id
                },
                // {
                //     name: "newDepartment",
                //     value: department_id
                // },
                {
                    name: "oldMajor",
                    value: oldMajor
                }
            ]

            const postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>registrar/actions/program_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: loadingAPIrequest(true),
                complete: function() {loadingAPIrequest(false); submitLock = false;},
                success: function(data){
                    if(data){
                        swal.close();
                        if(data.msg_status === true && data.code === 200){
                            swalOpenLock = true;
                            if (document.activeElement) document.activeElement.blur();
                            swal({
                                title: "Updated Successfully!",
                                text: data.msg_response,
                                icon: "success",
                                timer: 3000,
                                button: false,
                                closeOnEsc: false,
                            }).then(function(){
                                swalOpenLock = false;
                                populateDepartmentDropdown('#departmentProgram');
                                $('#editProgramModalForm')[0].reset();
                                $('#editProgramModal').modal('hide');
                                departmentTable.setData();
                            })
                        }
                        if(data.msg_status === false && data.code === 501){
                            swal({
                                title: "Failed to update.",
                                text: data.msg_response,
                                icon: "error",
                                button: true,
                            })
                        }
                        if(data.msg_status === false && data.code === 502){
                            swal({
                                title: "Failed to update.",
                                text: data.msg_response,
                                icon: "error",
                                button: true,
                            })
                        }
                        if(data.msg_status === false && data.code === 503){
                            swal({
                                title: "Failed to update.",
                                text: data.msg_response,
                                icon: "error",
                                button: true,
                            })
                        }
                        if(data.msg_status === false && data.code === 504){
                            swal({
                                title: "Failed to update.",
                                text: data.msg_response,
                                icon: "error",
                                button: true,
                            })
                        }
                        if(data.msg_status === false && data.code === 500){
                            swal({
                                title: "An error occured.",
                                text: "You're good, unkown error that needs consulting has occured. Consult support at MISD is advised.",
                                icon: "error",
                                button: true,
                            })
                        }
                    }
                },
                error: function(xhr, status, error){
                    swal({
                        title: "Error",
                        icon: "error",
                        text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                        button: true
                    });
                }
            })
        })
});
</script>
</html>